<?php

namespace Drupal\openplus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openplus\Util\ConfigUtil;

/**
 * Configure example settings for this site.
 */
class OpenplusSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openplus_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openplus.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openplus.settings');

    $form['org_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization name'),
      '#description' => $this->t('Organization name to show on by-line (under the H1).'),
      '#required' => TRUE,
      '#default_value' => is_null($config['org_name']) ? 'DrupalWxT' : $config['org_name'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('openplus.settings')
      ->set('openplus_settings', $values)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
