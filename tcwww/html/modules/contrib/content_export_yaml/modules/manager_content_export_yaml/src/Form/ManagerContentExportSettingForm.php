<?php

namespace Drupal\manager_content_export_yaml\Form;

use Drupal\manager_content_export_yaml\DBManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
/**
 * Class ContentExportSettingForm.
 */
class ManagerContentExportSettingForm  extends ConfigFormBase  {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'manager_content_export_yaml.manager_content_export_yaml_setting_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manager_content_export_yaml_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

      $manager = new DBManager();
      $manager->actionProcess();
      $form = parent::buildForm($form, $form_state);
      $header = array(
          // We make it sortable by name.
          array('data' =>
              $this->t('ID'),
              'field' => 'number',
              'sort' => 'desc'
          ),
          array('data' => $this->t('Entity ID')),
          array('data' => $this->t('Label')),
          array('data' => $this->t('Entity Type')),
          array('data' => $this->t('Bundle')),
          array('data' => $this->t('Actions')),
      );

      $db = \Drupal::database();
      $query = $db->select('content_export_yaml','c');
      $query->fields('c', array('number', 'entity_id','label', 'entity_type', 'bundle','imported'));
      // The actual action of sorting the rows is here.
      $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
          ->orderByHeader($header);
      // Limit the rows to 20 for each page.
      $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
          ->limit(20);
      $result = $pager->execute();

      // Populate the rows.
      $rows = array();
      $url = Url::fromRoute('<current>');
      $url_export =  $url->getInternalPath();

      foreach($result as $row) {
          if($row->imported && $row->imported ==1){
              $import = '<b style="padding: 15px;">Imported</b>' ;
          }else{
              $import = '<a href="/'.$url_export.'?import='.$row->entity_id.'" class="button button--primary"> import </a>' ;
          }
         $rows[] = array('data' => array(
              'number' => $row->number,
              'entity_id' => $row->entity_id,
              'label' => $row->label, // This hardcoded [BLOB] is just for display purpose only.
              'entity_type' => $row->entity_type,
              'bundle' => $row->bundle,
              'actions' => t( $import.' <a href="/'.$url_export.'?delete='.$row->entity_id.'" class="button button--default"> Remove </a>')
          ));
      }
      // The table description.
      $form["title"] = array(
          '#markup' => t('<h2>List of All Exported content </h2>')
      );

      // Generate the table.
      $form['config_table'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => t('No content exported loaded in database or all are already imported , click on the button " Scan exported contents " '),
      );

      // Finally add the pager.
      $form['pager'] = array(
          '#type' => 'pager'
      );
      $form['actions']['submit']['#value'] = t(" Scan exported contents ");
    //  \Drupal::state()->set("total_row_compta", $view->total_rows);
      $date = \Drupal::state()->get("date");

      if($date==null){
          $date = date('d-M-Y h:i:s A');
      }
      $form["desc"] = array(
          '#markup' => t('Your last scan is '.$date.', please process on scanning to update the export content'),
          '#weight' => -98
      );
      $form['actions']['#weight'] = -99;

      return $form ;
  }
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $export = new DBManager() ;
        $export->load_exported_all();
    }

}
