<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 5/22/19
 * Time: 8:28 PM
 * \Drupal::service('content_export_yaml.manager')
 */

namespace Drupal\content_export_yaml;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class ManageExport {

  private $func ;

  public function __construct() {
    $this->func = new ContentExport();
  }
  /**
   *  Export on entities
   */
  public function export($id,$entity,array $options=[]){
    $object = \Drupal::entityTypeManager()->getStorage($entity)->load($id);
    if(is_object($object)){
      return $this->func->exportWithPath($object,$entity,$options);
    }else{
      return false ;
    }
  }
  /***
   *  Export All Entities
   */
  public function exportFrom($entity,$bundle,$options=[]){
      $rangenid =[];
      if(isset($options['range'])&& isset($options['range'][0]) && isset($options['range'][1]) ){
          $rangenid[0] = $options['range'][0] ;
          $rangenid[1] = $options['range'][1] ;
      }
      $object_list = $this->func->load_entity_list($entity,$bundle,$rangenid);
      $id_label = \Drupal::entityTypeManager()->getDefinition($entity)->getKey('id');
      $status_list =[] ;
    foreach ($object_list as $id){
        $object = \Drupal::entityTypeManager()->getStorage($entity)->load($id) ;
      if(is_object($object)){
          $status = $this->func->exportWithPath($object,$entity,$options);
          $status_list[] =[
            'status' => $status,
            'label'=>$object->label(),
             $id_label => $object->id()
          ];
      }
    }
    return $status_list ;
  }
  public function exportNode($nid,$options=[]){
    return $this->func->export($nid,'node',$options);
  }
  public function exportBlock($id,$options=[]){
    return $this->func->export($id,'block_content',$options);
  }
  public function exportTerm($tid,$options=[]){
    return $this->func->export($tid,'taxonomy_term',$options);
  }
  public function exportTermAll($bundle,$options=[]){
    return $this->exportFrom('taxonomy_term',$bundle,$options);
  }
  public function exportBlockAll($bundle,$options=[]){
    return $this->exportFrom('block_content',$bundle,$options);
  }
  public function exportNodeAll($bundle,$options=[]){
    return $this->exportFrom('node',$bundle,$options);
  }
  public function exportAll($entity,$bundle,$options=[]){
    return $this->exportFrom($entity,$bundle,$options);
  }
  /***
   *  Important one entity
   */
  public function import($id,$entity,$bundle,$options=[]){
    $path = isset($options['path']) ?$options['path']:null;
    $yaml = $this->func->load_entity_config_list_with_path($entity,$bundle,[$id],$path);
    $parsed = new Parser();
    $object = $parsed->parse($yaml[$id], SymfonyYaml::PARSE_OBJECT);
    if(is_object($object)){
     return $this->func->savingEntity($object,$entity);
    }
    \Drupal::messenger()->addMessage(t('Failed to save item'), 'error');
    return false;
  }
  public function isReadyToImport($file){
    if (file_exists($file)) {
      try {
          $content = file_get_contents($file, FILE_USE_INCLUDE_PATH);
          $parsed = new Parser();
          $object = $parsed->parse($content, SymfonyYaml::PARSE_OBJECT);
          if( $object){
            return true ;
          }
      } catch (\Exception $e) {
           \Drupal::logger('content_export_yaml')->error('File yaml  has error :'.$e);
      }
     
    } 
    return false ;

  }
  /**
   *  Import all entity
   */
  public function importFrom($entity,$bundle,array $range,$options){
    $path = isset($options['path']) ?$options['path']:null;
    $yamls = $this->func->load_entity_config_list_with_path($entity,$bundle,$range,$path);
    $parsed = new Parser();
    $status_list =[];
    foreach ($yamls as $content){
      $object = $parsed->parse($content, SymfonyYaml::PARSE_OBJECT);
      if(is_object($object)){
         $status = $this->func->savingEntity($object,$entity);
        if($status !=1 || $status !=2){
            \Drupal::messenger()->addMessage(t('Failed to save item'), 'error');
        }
        $status_list[] =[
          'status' => $status,
          'label'=>$object->label(),
          'file_name' => $object->id()
        ];
      }else{
          \Drupal::messenger()->addMessage(t('Failed to convert yaml to object'), 'error');
      }
    }
    return $status_list;
  }

  public function importNode($nid,$bundle,$options=[]){
    return $this->func->import($nid,'node',$bundle,$options);
  }
  public function importBlock($id,$bundle,$options=[]){
    return $this->func->import($id,'block_content',$bundle,$options);
  }
  public function importTerm($tid,$bundle,$options=[]){
    return $this->func->import($tid,'taxonomy_term',$bundle,$options);
  }
  public function importTermAll($bundle,$options=[]){
    return $this->importFrom('taxonomy_term',$bundle,[],$options);
  }
  public function importBlockAll($bundle,$options=[]){
    return $this->importFrom('block_content',$bundle,[],$options);
  }
  public function importNodeAll($bundle,$options=[]){
    return $this->importFrom('node',$bundle,[],$options);
  }
  public function importAll($entity,$bundle,$options=[]){
    return $this->importFrom($entity,$bundle,[],$options);
  }

}