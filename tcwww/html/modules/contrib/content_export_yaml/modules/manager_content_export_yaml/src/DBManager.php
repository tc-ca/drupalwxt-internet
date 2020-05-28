<?php
/**
 * Created by PhpStorm.
 * User: ringier
 * Date: 1/11/17
 * Time: 1:51 PM
 */

namespace Drupal\manager_content_export_yaml;

use Drupal\content_export_yaml\ContentExport;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class DBManager extends ContentExport
{

    protected $database;

    public function __construct()
    {
        parent::__construct();
        $this->database = \Drupal::database();
    }

    public function update($imported, $entity_id)
    {
        try {
            // Connection->update()...->execute() returns the number of rows updated.
            $count = $this->database->update('content_export_yaml')
                ->fields(array('imported' => $imported))
                ->condition('entity_id', $entity_id)
                ->execute();
        } catch (\Exception $e) {
            $this->messenger()->addMessage(t('db_update failed. Message = %message, query= %query', [
                    '%message' => $e->getMessage(),
                    '%query' => $e->query_string,
                ]
            ), 'error');
        }
        return $count;
    }

    public function is_exist($id, $entity, $bundle)
    {
        return $this->database->select('content_export_yaml', 'n')
            ->fields('n', array('number', 'entity_id', 'entity_type', 'bundle'))
            ->condition('bundle', $bundle, '=')
            ->condition('entity_type', $entity, '=')
            ->condition('entity_id', $id, '=')
            ->range(0, 1)
            ->execute()
            ->fetchAllAssoc('number');

    }
    public function getContent($id)
    {
        $result = $this->database->select('content_export_yaml', 'n')
            ->fields('n', array('number', 'entity_id', 'label', 'entity_type', 'bundle', 'file','imported'))
            ->condition('entity_id', $id, '=')
            ->execute()
            ->fetchAllAssoc('entity_id');
        return reset($result);

    }

    public function delete_by_id($id)
    {
        return $this->database->delete('content_export_yaml')
            ->condition('entity_id', $id)
            ->execute();
    }

    public function delete_by_file($file)
    {
        return $this->database->delete('content_export_yaml')
            ->condition('file', $file)
            ->execute();
    }

    public function actionProcess()
    {
        $param = \Drupal::request()->query->all();
        if (isset($param['delete']) && is_numeric($param['delete'])) {
            $item = $this->getContent($param['delete']);
            if (is_object($item)) {
                $status = $this->delete($item->file);
                if ($status) {
                    $this->delete_by_id($param['delete']);
                    drupal_set_message(t('Deleted Successfully'));
                } else {
                    drupal_set_message(t('Failed to Delete'), 'error');
                }
            }
        }
        if (isset($param['import']) && is_numeric($param['import'])) {
            $item = $this->getContent($param['import']);
            if (is_object($item) && $item->imported ==0) {
                $id = $item->entity_id ;
                $bundle = $item->bundle;
                $entity_type = $item->entity_type;
                $label_value = $item->label;
                $object = $this->is_exist_entity($id, $bundle, $entity_type, $label_value );
                if(!empty($object) &&is_object(reset($object))){
                    drupal_set_message(t('Entity %entity_type  %bundle  %label already exist ', array('%entity_type' => $entity_type,
                        '%bundle' => $bundle ,
                        '%label' => $label_value
                        )), 'error');
                    $this->update(1,$id);
                }else{
                    $status = $this->importByFilePath($item->file);
                    if($status == 1){
                        drupal_set_message(t('Imported Succesfully'));
                        $this->update(1,$id);
                    }else{
                        drupal_set_message(t('Failed to importe','error'));
                    }
                }




            }
        }
    }

    public function insert($fields)
    {
        $return_value = NULL;
        try {
            $return_value = $this->database->insert('content_export_yaml')
                ->fields($fields)
                ->execute();
            \Drupal::logger("manager_content_export_yaml")->debug("export insert " . $return_value);

        } catch (Exception $e) {
            drupal_set_message(t('db_insert failed. Message = %message, query= %query', array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
        }
        return $return_value;
    }

    function load_exported_all()
    {
        $config = \Drupal::config('content_export_yaml.contentexportsetting');
        $themes_str = $config->get('path_export_content_folder');
        $items = [];
        if ($themes_str) {
            \Drupal::state()->set("date", date('d-M-Y h:i:s A'));
            $result = $this->listFolderFiles(DRUPAL_ROOT . $themes_str);
            $op =[];
            foreach ($result as $key => $item_entity_type) {
                foreach ($item_entity_type as $key => $item_bundle) {
                    foreach ($item_bundle as $key => $item) {
                        $items[] = $item;
                        if (is_array($item) && !empty($item) && isset($item['entity'])) {
                            $entity_object = $item['entity'];
                            if (is_object($entity_object)) {
                                $op[] = [
                                    $this->processCallBack($entity_object, $item),
                                    []
                                ];
                            }
                        }
                    }
                }
            }
            $batch = [
                'title' => t('Scan export content'),
                'init_message' => t('Start scan'),
                'error_message' => t('An error occured while scanning'),
                'operations' => $op,
                'finished' => $this->finishCallBack()
            ];
            batch_set($batch);
        }
        //  return null;
    }

    function importByFilePath($file)
    {
        $parsed = new Parser();
        $path_file = DRUPAL_ROOT . $file;
        $status = 0;
        if (file_exists($path_file)) {
            $item_yaml = file_get_contents($path_file, FILE_USE_INCLUDE_PATH);
            $item_object = $parsed->parse($item_yaml, SymfonyYaml::PARSE_OBJECT);
            if (is_object($item_object)){
                $new_item = $item_object->createDuplicate();
                $status = $new_item->save();
                if($status==1){
                    $this->delete($file);
                    $this->delete_by_file($file);
                }
            }
        }
        return $status;
    }

    function is_exist_entity($id, $bundle, $entity, $label_value = null)
    {
        $label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('label');
        $bundle_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('bundle');
        $id_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('id');
        $filters = [
            $id_label => $id,
            $bundle_label => $bundle
        ];
        if ($label_value) {
            $filters[$label] = $label_value;
        }
        return \Drupal::entityTypeManager()
            ->getStorage($entity)
            ->loadByProperties($filters);
    }

    public function processCallBack($entity_object, $item)
    {
        $entity = $entity_object->getEntityTypeId();
        $entity_id = $entity_object->id();
        $bundle = $entity_object->bundle();
        $label = $entity_object->label();
        $dbstatus = $this->is_exist($entity_id, $entity, $bundle);
        $object = $this->is_exist_entity($entity_id,$bundle,$entity,$label);
        $imported = 0 ;

        if(!empty($object) &&is_object(reset($object))){
            $imported = 1 ;
        }
        if (empty($dbstatus)) {
            $fields = array(
                'entity_id' => $entity_id,
                'label' => $label,
                'entity_type' => $entity,
                'bundle' => $bundle,
                'file' => $item['path'],
                'imported' => $imported
            );
            $this->insert($fields);
        }else{
            $this->update($imported,$entity_id);
        }
    }

    public function finishCallBack()
    {
        drupal_set_message(t('Scan Successfully'));
    }


} 