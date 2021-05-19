<?php

namespace Drupal\manager_content_export_yaml\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
    return parent::buildForm($form, $form_state);
  }

}
