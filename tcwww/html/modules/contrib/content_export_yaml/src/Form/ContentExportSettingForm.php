<?php

namespace Drupal\content_export_yaml\Form;

use Drupal\content_export_yaml\ContentExport;
use Drupal\content_export_yaml\DBManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContentExportSettingForm.
 */
class ContentExportSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_export_yaml.contentexportsetting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_export_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_export_yaml.contentexportsetting');
    $form['path_export_content_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path export content folder'),
      '#description' => $this->t('This folder path where your content will store , Example : /sites/default/files'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('path_export_content_folder'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('content_export_yaml.contentexportsetting')
      ->set('path_export_content_folder', $form_state->getValue('path_export_content_folder'))
      ->save();
  }

}
