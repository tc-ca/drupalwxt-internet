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
      '#default_value' => is_null($config->get('org_name')) ? 'DrupalWxT' : $config->get('org_name'),
    ];

    $form['drone_default_contacts'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drone default contacts'),
      '#description' => $this->t('Comma separated list of emails to send drone the form to in case of a problem.'),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#default_value' => is_null($config->get('drone_default_contacts')) ? NULL : $config->get('drone_default_contacts'),

    ];

    $form['news_check_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('News check cron interval'),
      '#description' => $this->t('Determines how frequently the site will check canada.ca for news updates and purge the home page in varnish.'),
      '#options' => [0 => $this->t('Never'), 3600 => $this->t('Hourly'), 900 => $this->t('Every 15 minutes')],
      '#required' => TRUE,
      '#default_value' => is_null($config->get('news_check_interval')) ? 3600 : $config->get('news_check_interval'),
    ];

    $form['news_check_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('News check url'),
      '#description' => $this->t('The url to hit to check for news.'),
      '#required' => TRUE,
      '#default_value' => is_null($config->get('news_check_url')) ? NULL : $config->get('news_check_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $org_value = $form_state->getValue('org_name');
    $drone_value = $form_state->getValue('drone_default_contacts');
    $interval_value = $form_state->getValue('news_check_interval');
    $url_value = $form_state->getValue('news_check_url');
    $this->config('openplus.settings')
      ->set('org_name', $org_value)
      ->set('drone_default_contacts', $drone_value)
      ->set('news_check_interval', $interval_value)
      ->set('news_check_url', $url_value)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
