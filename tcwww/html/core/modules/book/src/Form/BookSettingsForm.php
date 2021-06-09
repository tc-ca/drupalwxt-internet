<?php

namespace Drupal\book\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure book settings for this site.
 *
 * @internal
 */
class BookSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'book_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['book.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = node_type_get_names();
    $config = $this->config('book.settings');

    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Select the content types to be made available as books.'),
    ];

    foreach ($types as $key => $type) {
      $form['book_allowed_type_' . $key] = [
        '#type' => 'checkbox',
        '#title' => $type,
        '#default_value' => $config->get('allowed_type_' . $key),
      ];
      $form['book_child_type_' . $key] = [
        '#type' => 'radios',
        '#title' => $this->t('Content type for the <em>Add child page</em> link'),
        '#default_value' => NULL !== $config->get('child_type_' . $key) ? $config->get('child_type_' . $key) : $key,
        '#options' => $types,
        '#states' => array(
          'invisible' => array(
            ':input[name="book_allowed_type_' . $key . '"]' => array('checked' => FALSE),
          ),
          'required' => array(
            ':input[name="book_allowed_type_' . $key . '"]' => array('checked' => TRUE),
          ),
        ),
      ];
    }

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
    $types = node_type_get_names();

    foreach ($types as $key => $type) {
      $this->config('book.settings')
        ->set('allowed_type_' . $key, $form_state->getValue('book_allowed_type_' . $key))
        ->set('child_type_' . $key, $form_state->getValue('book_child_type_' . $key));
    }

    $this->config('book.settings')
      ->save();

    parent::submitForm($form, $form_state);
  }

}
