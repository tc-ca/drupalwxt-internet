<?php

namespace Drupal\mini_layouts\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class MiniLayoutForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form['#tree'] = TRUE;
    $form['admin_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative Label'),
      '#default_value' => $entity->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => $this->t('The admin label for this mini layout. This will be shown when managing blocks but will not be shown to end users.'),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => [
        'exists' => ['\Drupal\mini_layouts\Entity\MiniLayout', 'load'],
        'source' => ['admin_label'],
      ],
    ];

    $form['category'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category'),
      '#default_value' => $entity->category,
    ];

    if (!is_array($form_state->get('required_context'))) {
      $form_state->set('required_context', $entity->required_context);
    }
    $required_context = $form_state->get('required_context');

    $context_type_options = [];
    $types = \Drupal::typedDataManager()->getDefinitions();
    foreach ($types as $type => $definition) {
      $category = new TranslatableMarkup('Data');
      if (!empty($definition['deriver']) && !empty($types[$definition['id']])) {
        $category = $types[$definition['id']]['label'];
      }
      $context_type_options[(string) $category][$type] = $definition['label'];
    }

    $form['required_context_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Required Context'),
    ];
    $form['required_context_wrapper']['required_context'] = [
      '#prefix' => '<div id="required-context-table-wrapper">',
      '#suffix' => '</div>',
      '#parents' => ['required_context'],
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Machine-name'),
        $this->t('Type'),
        $this->t('Required'),
        $this->t('Operations'),
      ]
    ];
    foreach ($required_context as $machine_name => $info) {
      $row = [];
      $row['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#title_display' => 'invisible',
        '#default_value' => $info['label'],
      ];
      $row['machine_name'] = [
        '#type' => 'machine_name',
        '#title' => $this->t('Machine Name'),
        '#title_display' => 'invisible',
        '#default_value' => $machine_name,
        '#machine_name' => [
          'source' => [ 'required_context_wrapper', 'required_context', $machine_name, 'label'],
          'exists' => [ static::class, 'requiredContextMachineNameExists' ],
          'standalone' => TRUE,
        ],
        '#disabled' => TRUE,
      ];
      $row['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#title_display' => 'invisible',
        '#options' => $context_type_options,
        '#default_value' => $info['type']
      ];
      $row['required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Required'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($info['required']),
      ];
      $row['operations'] = [
        '#type' => 'container',
        'remove' => [
          '#type' => 'submit',
          '#name' => 'remove_'.$machine_name,
          '#rc_machine_name' => $machine_name,
          '#value' => $this->t('Remove'),
          '#limit_validation_errors' => [],
          '#ajax' => [
            'wrapper' => 'required-context-table-wrapper',
            'callback' => [static::class, 'formAjaxReloadRequiredContext'],
          ],
          '#submit' => [
            '::formSubmitRemoveRequiredContext',
          ],
        ],
      ];

      $form['required_context_wrapper']['required_context'][$machine_name] = $row;
    }

    $row = [];
    $row['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#title_display' => 'invisible',
    ];
    $row['machine_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine Name'),
      '#title_display' => 'invisible',
      '#required' => FALSE,
      '#machine_name' => [
        'source' => [ 'required_context_wrapper', 'required_context', '_add_new', 'label'],
        'exists' => [ static::class, 'requiredContextMachineNameExists' ],
        'standalone' => TRUE,
      ],
    ];
    $row['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#title_display' => 'invisible',
      '#options' => $context_type_options,
    ];
    $row['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#title_display' => 'invisible',
      '#default_value' => TRUE,
    ];
    $row['operations'] = [
      '#type' => 'container',
      'add' => [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#limit_validation_errors' => [
          ['required_context', '_add_new'],
        ],
        '#ajax' => [
          'wrapper' => 'required-context-table-wrapper',
          'callback' => [static::class, 'formAjaxReloadRequiredContext'],
        ],
        '#validate' => [
          '::formValidateAddRequiredContext',
        ],
        '#submit' => [
          '::formSubmitAddRequiredContext',
        ],
      ],
    ];
    $form['required_context_wrapper']['required_context']['_add_new'] = $row;

    return $form;
  }

  /**
   * Validate the information entered for the new context.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function formValidateAddRequiredContext($form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['required_context', '_add_new']);
    $row = &$form['required_context_wrapper']['required_context']['_add_new'];
    if (empty($values['machine_name'])) {
      $form_state->setError($row['machine_name'], new TranslatableMarkup('Context requires a unique machine name.'));
    }
    if (empty($values['label'])) {
      $form_state->setError($row['label'], new TranslatableMarkup('Context requires a label.'));
    }
  }

  /**
   * Submit to add a required context.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function formSubmitAddRequiredContext($form, FormStateInterface $form_state) {
    $rc = $form_state->get('required_context');

    $values = $form_state->getValue(['required_context', '_add_new']);
    $rc[$values['machine_name']] = [
      'label' => $values['label'],
      'machine_name' => $values['machine_name'],
      'type' => $values['type'],
      'required' => !empty($values['required']),
    ];
    $form_state->set('required_context', $rc);
    $form_state->setRebuild(TRUE);

    $user_input = &$form_state->getUserInput();
    unset($user_input['required_context']['_add_new']);
  }

  /**
   * Submit to remove a required context.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function formSubmitRemoveRequiredContext($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $rc = $form_state->get('required_context');
    unset($rc[$button['#rc_machine_name']]);
    $form_state->set('required_context', $rc);
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();

    return $return;
  }

  /**
   * Ajax callback to reload the required context.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function formAjaxReloadRequiredContext($form, FormStateInterface $form_state) {
    return $form['required_context_wrapper']['required_context'];
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    $entity->required_context = $form_state->get('required_context');
    foreach ($form_state->getValue('required_context') as $machine_name => $values) {
      if ($machine_name == '_add_new') {
        continue;
      }

      $entity->required_context[$machine_name]['label'] = $values['label'];
      $entity->required_context[$machine_name]['type'] = $values['type'];
      $entity->required_context[$machine_name]['required'] = !empty($values['required']);
    }
  }

  /**
   * Check whether the machine name of a required context exists already.
   *
   * @param $value
   * @param $element
   * @param $form_state
   *
   * @return boolean
   */
  public static function requiredContextMachineNameExists($value, $element, FormStateInterface $form_state) {
    $required_context = $form_state->get('required_context');
    return !empty($required_context[$value]) && !in_array($value, $element['#parents']);
  }
}
