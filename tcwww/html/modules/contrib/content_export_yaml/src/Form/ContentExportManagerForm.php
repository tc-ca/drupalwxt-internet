<?php

namespace Drupal\content_export_yaml\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\FileStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\Markup;
/**
 * Class ConfigImportForm.
 */
class ContentExportManagerForm extends FormBase {



//  /**
//   * {@inheritdoc}
//   */
  protected function getEditableConfigNames() {
    return [
      'content_export_yaml.manage_content_yaml',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_content_yaml_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      $settings = $this->config('content_export_yaml.contentexportsetting');
      $export = \Drupal::service('content_export_yaml.manager');
      $query = $this->getRequest()->query->all();
      $path = $settings->get('path_export_content_folder');
      $form_state->setMethod('GET');
      if(isset($query['file_single'])){
        $result  = DRUPAL_ROOT.$query['file_single'];
        $config_name = basename($result,'.yml') ;
        $bundle = $this->_getBundleName($result) ;
        $entity_type = $this->_getEntityType($result);
        $stat = $export->import($config_name,$entity_type,$bundle);
        if($stat){
        \Drupal::messenger()->addMessage(t("Imported Successfully"), 'status');
        }
        return new RedirectResponse(Url::fromRoute('content_export_yaml.manage_content_yaml_form')->toString());   
      }
      if(isset($query['delete'])){
        $filepath  = DRUPAL_ROOT.$query['delete'];
        $status = false ;
        if (is_file($filepath)){ $status = unlink($filepath);}
        if($status){
          \Drupal::messenger()->addMessage(t("File ".$query['delete']." deleted Successfully"), 'status');
        }
        return new RedirectResponse(Url::fromRoute('content_export_yaml.manage_content_yaml_form')->toString());   
      }

      if(isset($query['op'])
      && $query['op']=='Import all'){
      $path = ($query['path']) ? $path."/".$query['path'] : $path ;
      $config_path = DRUPAL_ROOT .$path;
      $results = $this->_readDirectory($config_path,'yml');
      $batch = [
        'title' => $this->t('Import Content From yml...'),
        'operations' => [],
        'init_message' => $this->t('Starting ..'),
        'progress_message' => $this->t('Processd @current out of @total.'),
        'error_message' => $this->t('An error occurred during processing.'),
        'finished' => '\Drupal\content_export_yaml\Form\ContentExportManagerForm::importFinishedCallback',
      ];
            if(isset($query['key']) && $query['key'] !=''){
                    foreach ($results as $key => $result){
                      $config_name = basename($result,'.yml') ;
                      $filter = $query['key'] ;
                      if ((is_string($filter) && strpos($config_name, $filter) !== false)) {
                        $batch['operations'][] = [$this->importElement($result), []];
                      }
                        
                    }
            }else{
                foreach ($results as $key => $result){
                    $batch['operations'][] = [$this->importElement($result), []];
                  
                }
            }
            batch_set($batch);
            return batch_process(Url::fromRoute('content_export_yaml.manage_content_yaml_form')->toString());
            //return new RedirectResponse(Url::fromRoute('content_export_yaml.manage_content_yaml_form')->toString());   
      }
      $form['key'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Search by file name'),
          '#attributes' => ['name' => 'key'],
          '#default_value' => isset($query['key'])?$query['key']:'',
          '#description' => 'Make empty to get all'
      ];
      $form['path'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Folder path '),
          '#attributes' => ['name' => 'path'],
          '#description' => 'if Empty show all , For example : /node ',
          '#default_value' => isset($query['path'])?$query['path']: '' 
      ];
      $header = [
          'id' =>  t('Number'),
          'folder' => t('Folder path'),
          'file' => t('File name'),
          'status' =>  t('Status'),
          'operation' => t('Actions')
      ];
      $output = [];
      if(isset($query['op']) && $query['op']=='Search'
      && isset($query['path']) && $query['path'] !=''){
      $key = '';
        $path = $path."/".$query['path'] ;
        if(isset($query['key'])){
            $key = $query['key'] ;
        }
        $form['help'] = [
            '#type' => 'item',
            '#title' => t('Selected key and path'),
            '#markup' => 'Key:'.$key.'<br/> Path : '.$query['path'],
        ];

      }
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => 'Search',

      ];

          $filter = null ;
        
          $config_path = DRUPAL_ROOT .$path;
          $results = $this->_readDirectory($config_path,'yml');
          $form['actions']['import'] = [
              '#type' => 'submit',
              '#value' => 'Import all'
          ];
          if(isset($query['key']) && $query['key'] !=''){
              foreach ($results as $key => $result) {
                $is_ok = $export->isReadyToImport($result);
                if($is_ok){
                  $status = Markup::create('<span style="color:blue">Clean </span>') ; 
                }else{ 
                  $status = Markup::create('<span style="color:red">Yaml Content has error</span>') ;
                }

                  $config_name = basename($result,'.yml') ;
                  $root_folder = dirname($result);
                  $root_folder  = str_replace(DRUPAL_ROOT,'', $root_folder);
                  $filter = $query['key'] ;
                  if (is_string($filter) && strpos($config_name, $filter) !== false) {
                      $bundle = basename($root_folder);
                      $entity_type = basename($bundle);
                      $operations = $this->_tableActions($root_folder,$config_name);
                      $output[] = [
                              'id' => $key+1 ,
                              'folder' =>  $root_folder,
                              'file' =>  $config_name,
                              'Status' =>  $status,
                              'operation' => array('data' => array('#type' => 'operations', '#links' => $operations)),
                      ];
                  }
              }
          }else{
              foreach ($results as $key => $result) {
               
                $is_ok = $export->isReadyToImport($result);
                if($is_ok){
                  $status = Markup::create('<span style="color:blue">Clean </span>') ; 
                }else{ 
                  $status = Markup::create('<span style="color:red">Yaml Content has error</span>') ;
                }
                  $config_name = basename($result,'.yml') ;
                  $root_folder = dirname($result);
                  $root_folder  = str_replace(DRUPAL_ROOT,'', $root_folder);
                  $operations = $this->_tableActions($root_folder,$config_name);
                  $output[] = [
                          'id' => $key+1 ,
                          'folder' =>  $root_folder,
                          'file' =>  $config_name,
                          'Status' =>  $status,
                          'operation' => array('data' => array('#type' => 'operations', '#links' => $operations)),
                  ];
              }
          }
   
          $row_pagination = $this->_return_pager_for_array($output, 25);
          $form['table'] = array(
              '#type' => 'table',
              '#weight'=> 999,
              '#header' => $header,
              '#rows' => $row_pagination,
              '#empty' => $this->t('No variables found')
          );
          $form['pager'] = array(
            '#type' => 'pager',
            '#weight' => 999,
            '#quantity' => 5
          );
          $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form ;
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
   protected function _tableActions($root_folder,$config_name){
      $config_name = $root_folder."/".$config_name.".yml";
      $operations['load'] =  [
        'title' => $this->t('View diff'),
        'url' => Url::fromRoute('content_export_yaml.manage_content_yaml_view',array('file_single' => $config_name)),
        'attributes' => [
            'class' => [
              'use-ajax'
            ],
            'data-dialog-options' => '{"width":700}',
            'data-dialog-type' => ['modal']
        
          ]
       ] ;
      $operations['import'] = array(
        'title' => $this->t('Import'),
        'url' =>   Url::fromRoute('content_export_yaml.manage_content_yaml_form',array('file_single' => $config_name))
      );
      $operations['remove'] = array(
        'title' => $this->t('Delete file'),
        'url' =>   Url::fromRoute('content_export_yaml.manage_content_yaml_form',array('delete' => $config_name))
      );
      return $operations ;
   }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   *
   */
  protected function importElement($result) {
    $config_name = basename($result,'.yml') ;
    $bundle = $this->_getBundleName($result) ;
    $entity_type = $this->_getEntityType($result);
    $export = \Drupal::service('content_export_yaml.manager');
    $export->import($config_name,$entity_type,$bundle); 
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
  protected  function _readDirectory($directory,$format = 'yml')
  {
      $path_file = [];
      if (is_dir($directory)) {
          $it = scandir($directory);
          if (!empty($it)) {
              foreach ($it as $fileinfo) {
                  $element =  $directory . "/" . $fileinfo;
                  if (is_dir($element) && substr($fileinfo, 0, strlen('.')) !== '.') {
                      $childs = $this->_readDirectory($element,$format);
                      $path_file = array_merge($childs , $path_file);
                  }else{
                      if ($fileinfo && strpos($fileinfo, '.'.$format) !== FALSE) {
                          if (file_exists($element)) {
                              $path_file[] =  $directory . "/" . $fileinfo;
                          }
                      }
                  }
              }
          }
      }else{
          drupal_set_message(t('No permission to read directory ' . $directory), 'error');
          @chmod($directory  , 0777);
      }
      return $path_file;
  }
  protected function _return_pager_for_array($items, $num_page) {
    $rows = [];
    // Get total items count
    $total = count($items);
    // Get the number of the current page
    $current_page = pager_default_initialize($total, $num_page);
    // Split an array into chunks
    $chunks = array_chunk($items, $num_page,TRUE);
    // Return current group item
    $current_page_items = $chunks[$current_page];
   
    return $current_page_items;
  }
   /**
   *
   */
  public static function importFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message = t('items successfully processed');
      drupal_set_message($message);
    }
    return new RedirectResponse(Url::fromRoute('content_export_yaml.manage_content_yaml_form')->toString());   
  }
}
