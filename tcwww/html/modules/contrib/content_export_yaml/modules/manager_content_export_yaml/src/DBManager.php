<?php
/**
 * Created by PhpStorm.
 * User: ringier
 * Date: 1/11/17
 * Time: 1:51 PM
 */

namespace Drupal\content_export_yaml;

use Drupal\content_export_yaml\ManageExport;

class DBManager extends ManageExport {

    protected $database;

    public function __construct()
    {
        $this->database = \Drupal::database();
    }

    public function is_exist($id, $entity , $bundle)
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
    public function insert($fields)
    {
        $return_value = NULL;
        try {
            $return_value = $this->database->insert('content_export_yaml')
                ->fields($fields)
                ->execute();
        } catch (Exception $e) {
            drupal_set_message(t('db_insert failed. Message = %message, query= %query', array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
        }
        return $return_value;
    }

  function load_exported_all() {
    $config = \Drupal::config('content_export_yaml.contentexportsetting');
    $themes_str = $config->get('path_export_content_folder');
    $items = [];
    if ($themes_str) {
      $result = $this->listFolderFiles(DRUPAL_ROOT . $themes_str);
      foreach ($result as $key => $item_entity_type) {
        foreach ($item_entity_type as $key => $item_bundle) {
          foreach ($item_bundle as $key => $item) {
            $items[] = $item;
            if(is_array($item)&&!empty($item) && isset($item['entity'])){
              $entity_object = $item['entity'] ;
              if(is_object($entity_object)){
              $entity = $entity_object->getEntityTypeId();
              $dbstatus = $this->is_exist($entity_object->id(),$entity,$entity_object->bundle());
              if(empty($dbstatus)){
                $fields = array(
                  'entity_id'=> $entity_object->id() ,
                  'entity_type' => $entity,
                  'bundle' => $entity_object->bundle(),
                  'label' => $entity_object->label(),
                  'file' => $item['path']
                );
                $this->insert($fields);
              }
              }
            }
          }
        }
      }
    }
    return $items;
  }
  function importByFilePath($file) {
    $parsed = new Parser();
    $path_file = DRUPAL_ROOT . $file;
    $status = 0;
    if (file_exists($path_file)) {
      $item_yaml = file_get_contents($path_file, FILE_USE_INCLUDE_PATH);
      $item_object = $parsed->parse($item_yaml, SymfonyYaml::PARSE_OBJECT);
      if (is_object($item_object)) {
        $new_item = $item_object->createDuplicate();
        $status = $new_item->save();
        if ($status == 1) {
          $entity_type = $new_item->getEntityTypeId();
          $type = $new_item->bundle();
          $id = $new_item->id();
          $this->export($id, $entity_type, $type);
          $this->delete($file);
        }
      }
    }
    return $status;
  }

  function is_exist_node($id,$bundle,$entity){
    $bundle_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('bundle');
    $id_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('id');
    return \Drupal::entityTypeManager()
      ->getStorage($entity)
      ->loadByProperties([
        $id_label => $id,
        $bundle_label => $bundle
      ]);
  }


} 