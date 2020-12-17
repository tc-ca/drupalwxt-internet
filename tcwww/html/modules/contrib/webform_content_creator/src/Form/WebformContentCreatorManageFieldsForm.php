<?php

namespace Drupal\webform_content_creator\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;

/**
 * Form handler for the Webform content creator manage fields form.
 */
class WebformContentCreatorManageFieldsForm extends EntityForm {

  const CONTENT_TYPE_FIELD = 'content_type_field';

  const FIELD_TYPE = 'field_type';

  const WEBFORM_FIELD = 'webform_field';

  const CUSTOM_CHECK = 'custom_check';

  const CUSTOM_VALUE = 'custom_value';

  const FORM_TABLE = 'table';

  /**
   * Plugin field type.
   *
   * @var object
   */
  protected $pluginFieldType;

  /**
   * Entity type manager.
   *
   * @var object
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->pluginFieldType = $container->get('plugin.manager.field.field_type');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('field_title'),
      '#help' => $this->t('Title of content created after webform submission. You may use tokens.'),
      '#description' => $this->t("Default value: webform title."),
      '#weight' => 0,
    ];
    $form['intro_text'] = [
      '#markup' => '<p>' . $this->t('You can create nodes based on webform submission values. In this page, you can do mappings between content type fields and webform submission values. You may also use tokens in custom text.') . '</p>',
    ];
    $form['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['webform_submission'],
      '#global_types' => TRUE,
      '#click_insert' => TRUE,
      '#show_restricted' => FALSE,
      '#recursion_limit' => 3,
      '#text' => $this->t('Browse available tokens'),
    ];
    // Construct table with mapping between content type and webform.
    $this->constructTable($form);
    return $form;
  }

  /**
   * Constructs table with mapping between webform and content type.
   *
   * @param array $form
   *   Form entity array.
   */
  public function constructTable(array &$form) {
    $fieldTypesDefinitions = $this->pluginFieldType->getDefinitions();
    $attributes = $this->entity->getAttributes();
    $ct = $this->entity->getContentType();
    $contentType = $this->entityTypeManager->getStorage('node_type')->load($ct);
    $nodeFilteredFieldIds = WebformContentCreatorUtilities::getContentFieldsIds($contentType);
    asort($nodeFilteredFieldIds);
    $nodeFields = WebformContentCreatorUtilities::contentTypeFields($contentType);
    $webform_id = $this->entity->getWebform();
    $webformOptions = WebformContentCreatorUtilities::getWebformElements($webform_id);

    // Table header.
    $header = [
      self::CONTENT_TYPE_FIELD => $this->t('Content type field'),
      self::FIELD_TYPE => $this->t('Field type'),
      self::CUSTOM_CHECK => $this->t('Custom'),
      self::WEBFORM_FIELD => $this->t('Webform field'),
      self::CUSTOM_VALUE => $this->t('Custom text'),
    ];
    $form[self::FORM_TABLE] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    foreach ($nodeFilteredFieldIds as $fieldId) {
      $route_parameters = [
        'node_type' => $ct,
        'field_config' => 'node.' . $ct . '.' . $fieldId,
      ];

      // Checkboxes with content type fields.
      $form[self::FORM_TABLE][$fieldId][self::CONTENT_TYPE_FIELD] = [
        '#type' => 'checkbox',
        '#default_value' => array_key_exists($fieldId, $attributes),
        '#title' => $nodeFields[$fieldId]->getLabel() . ' (' . $fieldId . ')',
      ];

      // Link to edit field settings.
      $form[self::FORM_TABLE][$fieldId][self::FIELD_TYPE] = [
        '#type' => 'link',
        '#title' => $fieldTypesDefinitions[$nodeFields[$fieldId]->getType()]['label'],
        '#url' => Url::fromRoute("entity.field_config.node_storage_edit_form", $route_parameters),
        '#options' => ['attributes' => ['title' => $this->t('Edit field settings.')]],
      ];

      // Checkbox to select between webform element/property or custom text.
      $form[self::FORM_TABLE][$fieldId][self::CUSTOM_CHECK] = [
        '#type' => 'checkbox',
        '#default_value' => array_key_exists($fieldId, $attributes) ? $attributes[$fieldId][self::CUSTOM_CHECK] : '',
        '#states' => [
          'disabled' => [
            ':input[name="' . self::FORM_TABLE . '[' . $fieldId . '][' . self::CONTENT_TYPE_FIELD . ']"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $type = !empty($attributes[$fieldId]) && $attributes[$fieldId]['type'] ? '1' : '0';
      // Select with webform elements and basic properties.
      $form[self::FORM_TABLE][$fieldId][self::WEBFORM_FIELD] = [
        '#type' => 'select',
        '#options' => $webformOptions,
        '#states' => [
          'required' => [
            ':input[name="' . self::FORM_TABLE . '[' . $fieldId . '][' . self::CONTENT_TYPE_FIELD . ']"]' => ['checked' => TRUE],
            ':input[name="' . self::FORM_TABLE . '[' . $fieldId . '][' . self::CUSTOM_CHECK . ']"]' => ['checked' => FALSE],
          ],
        ],
      ];

      if (array_key_exists($fieldId, $attributes) && !$attributes[$fieldId][self::CUSTOM_CHECK]) {
        $form[self::FORM_TABLE][$fieldId][self::WEBFORM_FIELD]['#default_value'] = $type . ',' . $attributes[$fieldId][self::WEBFORM_FIELD];
      }

      // Textarea with custom text (including tokens)
      $form[self::FORM_TABLE][$fieldId][self::CUSTOM_VALUE] = [
        '#type' => 'textarea',
        '#default_value' => array_key_exists($fieldId, $attributes) ? $attributes[$fieldId][self::CUSTOM_VALUE] : '',
      ];
    }

    // Change table position in page.
    $form[self::FORM_TABLE]['#weight'] = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $ct = $this->entity->getContentType();
    $contentType = $this->entityTypeManager->getStorage('node_type')->load($ct);
    $nodeFields = WebformContentCreatorUtilities::contentTypeFields($contentType);
    $webform_id = $this->entity->getWebform();
    $webformElementTypes = WebformContentCreatorUtilities::getWebformElementsTypes($webform_id);
    // For each table row.
    foreach ($form_state->getValue(self::FORM_TABLE) as $k => $v) {
      // Check if a content type field is selected.
      if (!$v[self::CONTENT_TYPE_FIELD]) {
        continue;
      }
      $args = explode(',', $v[self::WEBFORM_FIELD]);
      if (empty($args) || count($args) < 2) {
        continue;
      }

      $nodeFieldType = $nodeFields[$k]->getType();
      $webformOptionType = array_key_exists($args[1], $webformElementTypes) ? $webformElementTypes[$args[1]] : '';
      if ($nodeFieldType === $webformOptionType) {
        continue;
      }

      if ($nodeFieldType === 'email') {
        $form_state->setErrorByName(self::FORM_TABLE . '][' . $k . '][' . self::WEBFORM_FIELD, $this->t('Incompatible type'));
      }

      if ($webformOptionType === 'email' && (strpos($nodeFieldType, 'text') === FALSE) && (strpos($nodeFieldType, 'string') === FALSE)) {
        $form_state->setErrorByName(self::FORM_TABLE . '][' . $k . '][' . self::WEBFORM_FIELD, $this->t('Incompatible type'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $attributes = [];
    // For each table row.
    foreach ($form_state->getValue(self::FORM_TABLE) as $k => $v) {
      // Check if a content type field is selected.
      if (!$v[self::CONTENT_TYPE_FIELD]) {
        continue;
      }
      $args = explode(',', $v[self::WEBFORM_FIELD]);
      if (empty($args) || count($args) < 1) {
        continue;
      }

      $attributes[$k] = [
        'type' => explode(',', $v[self::WEBFORM_FIELD])[0] === '1',
        self::WEBFORM_FIELD => !$v[self::CUSTOM_CHECK] ? explode(',', $v[self::WEBFORM_FIELD])[1] : '',
        self::CUSTOM_CHECK => $v[self::CUSTOM_CHECK],
        self::CUSTOM_VALUE => $v[self::CUSTOM_CHECK] ? $v[self::CUSTOM_VALUE] : '',
      ];
    }

    $this->entity->set('field_title', $form_state->getValue('title'));
    $this->entity->set('elements', $attributes);
    $status = $this->entity->save();
    $this->entity->statusMessage($status);
    $form_state->setRedirect('entity.webform_content_creator.collection');
  }

  /**
   * Helper function to check whether a Webform content creator entity exists.
   *
   * @param mixed $id
   *   Entity id.
   *
   * @return bool
   *   True if entity already exists.
   */
  public function exist($id) {
    return WebformContentCreatorUtilities::existsWebformContentCreatorEntity($id);
  }

}
