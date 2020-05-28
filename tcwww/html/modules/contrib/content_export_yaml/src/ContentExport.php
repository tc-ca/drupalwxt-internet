<?php

namespace Drupal\content_export_yaml;


use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Created by PhpStorm.
 * User: USER
 * Date: 11/13/18
 * Time: 2:04 PM
 */
class ContentExport
{

    public $logger;
    public $db;

    public function __construct()
    {
        $this->logger = \Drupal::logger('content_export_yaml');
    }

    function content_type_list()
    {
        $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
        $options = [];
        foreach ($node_types as $node_type) {
            $options[$node_type->id()] = $node_type->label();
        }
        return $options;
    }

    /**
     * Get all entity by entity
     */
    function load_entity_list($entity, $bundle, $ranges_nid = [])
    {
        $id_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('id');
        // print_r(\Drupal::entityTypeManager()->getDefinition($entity)->getKeys());
        $bundle_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('bundle');

        $factory = \Drupal::entityTypeManager()->getStorage($entity)->getQuery();
        if ($bundle_label != "") {
            $factory->condition($bundle_label, $bundle);
        }
        if (!empty($ranges_nid) && isset($ranges_nid[0]) && isset($ranges_nid[1])) {
            if(is_string($ranges_nid[0]) && is_string($ranges_nid[1])
            ){
                if($ranges_nid[0] == $ranges_nid[1]){
                      $factory->condition($id_label, ($ranges_nid[1]) , '=');
                }else{
                    if(is_numeric($ranges_nid[0]) && is_numeric($ranges_nid[1])){
                        $factory->condition($id_label, $ranges_nid, 'BETWEEN');
                    }else{
                        return [];
                    }
                }

            }
        }
        return $factory->execute();
    }

    /**
     * Get one Entity  exported
     * @param $entity String node  eg : /taxonomy_term /...
     */
    function load_entity_config_list_with_path($entity, $bundle, $ranges_nid = [], $path = NULL)
    {
        $items = [];
        if ($path) {
            $themes_str = $path;
        } else {
            $config = \Drupal::config('content_export_yaml.contentexportsetting');
            $themes_str = $config->get('path_export_content_folder');
        }

        if ($themes_str) {
            if (empty($ranges_nid)) {
                if ($bundle) {
                    $items = $this->readDirectory($themes_str . "/" . $entity . "/" . $bundle);
                } else {
                    $items = $this->readDirectory($themes_str . "/" . $entity);
                }
                foreach ($items as $key => $file) {
                    if (file_exists($file)) {
                        $items[$key] = file_get_contents($file, FILE_USE_INCLUDE_PATH);
                    } else {
                        $this->logger->error('File  not find exist : ' . $file);
                    }
                }
            } else {
                for ($i = $ranges_nid[0]; $i < $ranges_nid[0] + 1; $i++) {
                    if ($bundle) {
                        $file = DRUPAL_ROOT . '/' . $themes_str . "/" . $entity . "/" . $bundle . "/" . $i . ".yml";
                    } else {
                        $file = DRUPAL_ROOT . '/' . $themes_str . "/" . $entity . "/" . $i . ".yml";
                    }
                    if (file_exists($file)) {
                        $items[$i] = file_get_contents($file, FILE_USE_INCLUDE_PATH);
                    } else {
                        $this->logger->error('File  not find exist : ' . $file);
                    }
                }
            }
        } else {
            $this->logger->error('Path directory empty ');
        }
        return $items;
    }


    /***
     *  Get All Entity Exported
     */
    function load_entity_config_list($entity, $bundle = NULL, $ranges_nid = [])
    {
        $items = [];
        if ($bundle == 'all') {
            $bundle = NULL;
        }
        $config = \Drupal::config('content_export_yaml.contentexportsetting');
        $themes_str = $config->get('path_export_content_folder');
        if ($themes_str) {
            if (empty($ranges_nid)) {
                if ($bundle) {
                    $items = $this->readDirectory($themes_str . "/" . $entity . "/" . $bundle);
                } else {
                    $items = $this->readDirectory($themes_str . "/" . $entity);
                }
                foreach ($items as $key => $file) {
                    if (file_exists($file)) {
                        $items[$key] = file_get_contents($file, FILE_USE_INCLUDE_PATH);
                    } else {
                        $this->logger->error('File  not find exist : ' . $file);
                    }
                }
            } else {
                if(is_numeric($ranges_nid[0]) && is_numeric($ranges_nid[1])) {
                    for ($i = $ranges_nid[0]; $i < $ranges_nid[1] + 1 ; $i++) {

                        if ($bundle) {
                            $file = DRUPAL_ROOT . '/' . $themes_str . "/" . $entity . "/" . $bundle . "/" . $i . ".yml";
                        } else {
                            $file = DRUPAL_ROOT . '/' . $themes_str . "/" . $entity . "/" . $i . ".yml";
                        }
                        if (file_exists($file)) {
                            $items[$i] = file_get_contents($file, FILE_USE_INCLUDE_PATH);
                        } else {
                            $this->logger->warning('File  not find exist : ' . $file);
                        }
                    }
                }else{
                    if($ranges_nid[0] == $ranges_nid[1]) {
                        $file = DRUPAL_ROOT . '/' . $themes_str . "/" . $entity . "/" . $ranges_nid[1] . ".yml";
                        if (file_exists($file)) {
                            $items[$ranges_nid[0]] = file_get_contents($file, FILE_USE_INCLUDE_PATH);
                        }
                    }
                }
            }
        } else {
            $this->logger->error('Path directory empty ');
        }
        return $items;
    }

    protected function readDirectory($directory)
    {
        $path_file = [];
        if (is_dir(DRUPAL_ROOT . $directory)) {
            $it = scandir(DRUPAL_ROOT . $directory);
            if (!empty($it)) {
                foreach ($it as $fileinfo) {
                    if ($fileinfo && strpos($fileinfo, '.yml') !== FALSE) {
                        $file = DRUPAL_ROOT . $directory . "/" . $fileinfo;
                        if (file_exists($file)) {
                            $path_file[] = DRUPAL_ROOT . $directory . "/" . $fileinfo;
                        }
                    }
                }
            }
        }
        return $path_file;
    }

    function import($id, $entity)
    {
        return $this->importEntity($id, $entity);
    }

    /**
     * Import All Entity
     */
    function importEntity($yaml_object, $entity)
    {
        $parsed = new Parser();
        $node_object = $parsed->parse($yaml_object, SymfonyYaml::PARSE_OBJECT);
        if (is_object($node_object)) {
            return $this->savingEntity($node_object, $entity);
        } else {
            $this->logger->error('Failed to save item');
        }
        return FALSE;
    }

    /**
     * Save all Entity
     */
    function savingEntity($enity_clone, $entity)
    {
        $id_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('id');
        $key_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('label');
        $bundle_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('bundle');
        if ($entity == "user" && $enity_clone->id() == 1) {
            drupal_set_message(t($entity . " root user " . $enity_clone->label() . " uid=" . $enity_clone->id() . " can not update "));
            return FALSE;
        }
        if ($bundle_label == "") {
            $filter = [
                $id_label => $enity_clone->id()
            ];
        } elseif ($key_label == "") {
            $filter = [
                $id_label => $enity_clone->id(),
                $bundle_label => $enity_clone->bundle(),
            ];
        } else {
            $filter = [
                $id_label => $enity_clone->id(),
                $bundle_label => $enity_clone->bundle(),
                $key_label => $enity_clone->label(),
            ];
        }
        $entity_list = \Drupal::entityTypeManager()
            ->getStorage($entity)
            ->loadByProperties($filter);
        if (!empty($entity_list)) {

            $status = $enity_clone->save();
            if ($status == 2) {
                if ($bundle_label == "") {
                    $bundle_label = $entity;
                }else{
                    $bundle_label = $enity_clone->bundle();
                }
                drupal_set_message(t($bundle_label . " with " . $id_label . "=" . $enity_clone->id() . " update "));
            }
        } else {
            $enity_clone->{$id_label} = NULL;
            // Also handle modules that attach a UUID to the node.
            $enity_clone->uuid = \Drupal::service('uuid')->generate();
            // Anyonmymous users don't have a name.
            $enity_clone->created = time();
            //$enity_clone->uid = 0;
            $status = $enity_clone->save();
            if ($status == 1) {
                if ($bundle_label == "") {
                    $bundle_label = $entity;
                }else{
                    $bundle_label = $enity_clone->bundle();
                }
                drupal_set_message(t("New Item of " . $bundle_label . " was created "));
            }
        }

        return $status;
    }

    /****
     *  Export All entity
     */
    function export($id, $entity, $type = NULL)
    {
        $config = \Drupal::config('content_export_yaml.contentexportsetting');
        $themes_str = $config->get('path_export_content_folder');
        return $this->exportBase($id, $entity, $themes_str);

    }

    function exportBase($id, $entity, $export_path)
    {
        if (is_object($id)) {
            $item = $id;
        } else {
            $item = \Drupal::entityTypeManager()
                ->getStorage($entity)->load($id);
        }
        if (is_object($item)) {
            $yaml_content = $this->parserYAMLObject($item);
            if ($export_path) {
                $export_path = DRUPAL_ROOT . '/' . $export_path;
                $final_path = $export_path . '/' . $entity . '/' . $item->bundle();
                $status = $this->generateFile($final_path, $item->id(), $yaml_content);
                //@todo move to manager_content_export_yaml
                // if($status){
                //   $dbstatus = $this->db->is_exist($item->id(),$entity,$item->bundle());
                //   if(empty($dbstatus)){
                //     $fields = array(
                //       'entity_id'=> $item->id() ,
                //       'entity_type' => $entity,
                //       'bundle' => $item->bundle(),
                //       'label' => $item->label(),
                //       'file' => $themes_str_path.'/'.$entity.'/'.$item->bundle().'/'.$item->id().'.yml'
                //     );
                //     $this->db->insert($fields);
                //   }
                // }
                return $status;
            } else {
                $this->logger->error('Path directory empty ');
                drupal_set_message(t('Path directory empty '), 'error');

                return FALSE;
            }
        }
        return FALSE;
    }

    function exportWithPath($id, $entity, $options = [])
    {
        $config = \Drupal::config('content_export_yaml.contentexportsetting');
        $path_config = $config->get('path_export_content_folder');
        $themes_str_path = isset($options["path"]) ? $options["path"] : $path_config;
        return $this->exportBase($id, $entity, $themes_str_path);
    }

    /***
     * Convert OBJECT to YAML
     */
    function parserYAMLObject($entity)
    {
        $yaml = new Dumper(2);
        return $yaml->dump($entity, PHP_INT_MAX, 0, SymfonyYaml::DUMP_OBJECT);
    }

    /**
     * @param $directory String
     * location folder of exported entity
     * @param $filename String
     * @param  $content String
     * Yaml content
     * @return bool
     */
    function generateFile($directory, $filename, $content)
    {
        $fileSystem = \Drupal::service('file_system');
        if (!is_dir($directory)) {
            if ($fileSystem->mkdir($directory, 0777, TRUE) === FALSE) {
                $this->logger->error('Failed to create directory ' . $directory);
                drupal_set_message(t('Failed to create directory ' . $directory), 'error');
                return FALSE;
            }
        }
        if (file_put_contents($directory . '/' . $filename . '.yml', $content) === FALSE) {
            drupal_set_message(t('Failed to write file ' . $filename), 'error');
            $this->logger->error('Failed to write file ' . $filename);
            return FALSE;
        }
        if (@chmod($directory . '/' . $filename . '.html.twig', 0777)) {
            drupal_set_message(t('Failed to change permission file ' . $filename), 'error');
            $this->logger->error('Failed to change permission file ' . $filename);
        }
        return TRUE;
    }

    /**
     * download_yml
     */
    public function download_yml($yml)
    {
        $path_file = \Drupal::service('file_system')->realpath("public://temp_yml");
        $file_name = "download";
        $this->delete($path_file . "/" . $file_name . ".yml");
        $status = $this->yml_copy($file_name, $yml, $path_file);
        if ($status) {
            $file_temp = "/sites/default/files/temp_yml/" . $file_name . ".yml";
            @chmod($file_temp, 0777);
            return $file_temp;
        } else {
            drupal_set_message("failed to download", "error");
        }
    }

    /**
     * Copy yaml to another folder
     */
    function yml_copy($file_name, $file_with_path, $path_export)
    {
        $file_full_path = DRUPAL_ROOT . $file_with_path;
        $fileSystem = \Drupal::service('file_system');
        if (!is_dir($path_export)) {
            if ($fileSystem->mkdir($path_export, 0777, TRUE) === FALSE) {
                $this->logger->error('Failed to create directory ' . $path_export);
                return FALSE;
            }
        }
        if (!copy($file_full_path, $path_export . "/" . $file_name . ".yml")) {
            drupal_set_message("failed to copy $file_with_path", "error");
            return FALSE;
        } else {
            drupal_set_message("Upload Success", "status");
            @chmod($path_export . "/" . $file_name . ".yml", 0777);
            return TRUE;
        }
    }

    /**
     * delete file in folder
     * @param $file  String
     * path of file for eg : /sites/default/files/export/node/10.yml
     */
    function delete($file)
    {
        $file = DRUPAL_ROOT . $file;
        if (file_exists($file)) {
            if (is_writable($file) && @unlink($file)) {
                return TRUE;
            } else {
                $this->logger->error('File  not write : ' . $file);
                drupal_set_message('File  not write : ' . $file, 'error');

                return FALSE;
            }
        }
        return FALSE;
    }

    /***
     * get all entity expoted in folder
     */
    function listFolderFiles($dir)
    {
        $fileInfo = scandir($dir);
        $allFileLists = [];
        $parsed = new Parser();

        foreach ($fileInfo as $folder) {


            if ($folder !== '.' && $folder !== '..') {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $folder) === TRUE) {

                    $allFileLists[$folder] = $this->listFolderFiles($dir . DIRECTORY_SEPARATOR . $folder);
                } else {
                    $path_file = $dir . DIRECTORY_SEPARATOR . $folder;
                    $ext = pathinfo($path_file, PATHINFO_EXTENSION);
                    if (file_exists($path_file) && $ext == 'yml') {
                        $item_yaml = file_get_contents($path_file, FILE_USE_INCLUDE_PATH);
                        if ($item_yaml) {
                            try {
                                $item_object = \Symfony\Component\Yaml\Yaml::parse($item_yaml, SymfonyYaml::PARSE_OBJECT);
                            } catch (Exception $e) {
                                drupal_set_message("'Message: " . $e->getMessage(), "error");

                            }
                            if ($item_object && is_object($item_object)) {
                                $path = str_replace(DRUPAL_ROOT, "", $path_file);
                                $allFileLists[$folder] = [
                                    "file" => $folder,
                                    "path" => $path,
                                    "entity" => $item_object,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $allFileLists;
    }

    /***
     * get path root of exported entity
     * @param $entity Object
     * @return string
     */
    function get_export_path($entity)
    {
        if (is_object($entity)) {
            $entity_type = $entity->getEntityTypeId();
            $type = $entity->bundle();
            $config = \Drupal::config('content_export_yaml.contentexportsetting');
            $themes_str = $config->get('path_export_content_folder');
            if ($themes_str) {
                $themes_str = DRUPAL_ROOT . $themes_str;
                if ($type) {
                    $final_path = $themes_str . '/' . $entity_type . '/' . $type;
                } else {
                    $final_path = $themes_str . '/' . $entity_type;
                }
                return $final_path;
            } else {
                $this->logger->error('Path directory empty ');
                return FALSE;
            }
        }
    }

    /**
     * redirect to url
     */
    public function redirectTo($url, $lang = NULL)
    {
        global $base_url;
        $path = $base_url . '/' . $url;
        $response = new RedirectResponse($path, 302);
        $response->send();
        return;
    }
}
