<?php

namespace Drupal\content_export_yaml\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;
use Symfony\Component\Yaml\Parser;
/**
 * Class ContentExportController.
 *
 * @package Drupal\content_export_yaml\Controller
 */
class ContentExportController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Handles html requests.
   *
   * @return string Return Hello string.
   */
  public function view() {
    $query = \Drupal::request()->query->all();
    if( $query['file_single']){
    $result = $query['file_single'] ;
    $id = basename($query['file_single'],'.yml') ;
    $bundle = $this->_getBundleName($result) ;
    $entity_type = $this->_getEntityType($result);
    $object_file = $this->_getObjectinFile($result) ;

    ///in database
    $entity_list_name = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    if(in_array($entity_type ,$entity_list_name)){
     $item['is_entity'] = true;
     $bundle_list_name = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
     if(in_array($bundle,array_keys($bundle_list_name))){
         $item['is_bundle'] = true;
         $object = \Drupal::entityTypeManager()->getStorage($entity_type)->load($id); 
         if($object){
          $item['database']['id'] = ($object)? $object->id() : "";
          $item['database']['label'] = ($object)? $object->label() : "";
          $item['database']['created'] = ($object && $object->created)? date("Y-m-d H:i:s",$object->created->value) : "";
          $item['database']['changed'] = ($object && $object->changed)? date("Y-m-d H:i:s",$object->changed->value) : "";
          $item['database']['uid'] =   ($object && $object->uid)? $object->uid->target_id : "";
         }
         if($object_file){
          $item['file']['id'] = ($object_file)? $object_file->id() : "";
          $item['file']['label'] = ($object_file)? $object_file->label() : "";
          $item['file']['created'] = ($object_file && $object_file->created)? date("Y-m-d H:i:s",$object_file->created->value) : "";
          $item['file']['changed'] = ($object_file && $object_file->changed)? date("Y-m-d H:i:s",$object_file->changed->value) : "";
          $item['file']['uid'] =   ($object_file && $object_file->uid)? $object_file->uid->target_id : "";
         }
     } 
    }

    $item['file_path'] = $query['file_single'];
    $item['entity_type'] = $entity_type;
    $item['bundle'] = $bundle;
    $output = [
      '#theme' => 'view_template_item',
      '#item' => $item,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $output;
    }else{
      return [
        '#markup' => $this->t('Item not found.'),
      ];
    }
  }
  protected function _getBundleName($result){
    $root_folder = dirname($result);
    return basename($root_folder);
   }
  protected function _getEntityType($result){
    $root_folder_1 = dirname($result);
    $root_folder = dirname($root_folder_1);
    return basename($root_folder);
  }
  protected function _getObjectinFile($file){
    $file = DRUPAL_ROOT.$file ; 
    if (file_exists($file)) {
      $yaml_object = file_get_contents($file, FILE_USE_INCLUDE_PATH);
      $parsed = new Parser();
      $object = $parsed->parse($yaml_object, SymfonyYaml::PARSE_OBJECT);
      if (is_object($object)) {
         return $object ;
      }
    }
    return FALSE ;
  }
}
