<?php

namespace Drupal\openplus\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Provides a 'Node Field' condition without specifying bundle - adapted from entity_field_condition.
 *
 * @Condition(
 *   id = "op_node_field",
 *   label = @Translation("OP Node Field"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       required = TRUE,
 *       label = @Translation("node")
 *     )
 *   }
 * )
 */
class NodeField extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Field\FieldTypePluginManagerInterface definition.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Creates a new NodeField instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager interface.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager interface.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Load fields based on the selected entity_bundle.
    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#validated' => TRUE,
      '#options' => $this->getNodeFields(),
      '#default_value' => $this->configuration['field'],
    ];

    $form['value_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Value Source'),
      '#options' => [
        'null' => $this->t('Is NULL'),
        'specified' => $this->t('Specified'),
      ],
      '#default_value' => $this->configuration['value_source'],
    ];

    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value to be compared'),
      '#default_value' => $this->configuration['value'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Return the empty option for the select elements.
   *
   * @return array
   *   Returns the empty option for the select elements.
   */
  public function getEmptyOption() {
    return ['' => $this->t('None')];
  }

  /**
   * Return the fields for a content type.
   *
   * @param string $node_type
   *   The node type machine name.
   *
   * @return array
   *   Returns the available fields for the content type.
   */
  protected function getNodeFields() {
    $labels = $this->getEmptyOption();

    $entityFieldManager = \Drupal::service('entity_field.manager');
    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
    foreach ($node_types as $bundle) {
      $fields = $entityFieldManager->getFieldDefinitions('node', $bundle->id());
      foreach ($fields as $field_name => $field_definition) {
        if ($field_definition->getFieldStorageDefinition()->isBaseField() == FALSE) {
          $labels[$field_name] = $field_definition->getLabel() . '(' . $field_name . ':' . $field_definition->getType() . ')';
        }
      }
    }

    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['field'] = $form_state->getValue('field');
    $this->configuration['value_source'] = $form_state->getValue('value_source');
    $this->configuration['value'] = $form_state->getValue('value');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [
      'entity_type_id' => 'node',
      'field' => '',
      'value_source' => 'null',
      'value' => '',
    ];

    return $configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['field']) && !$this->isNegated()) {
      return TRUE;
    }

    $entity_type_id = $this->configuration['entity_type_id'];
    $field = $this->configuration['field'];

    /** @var \Drupal\node\Entity\Node $entity */
    $entity = $this->getContextValue($entity_type_id);

    if (is_subclass_of($entity, 'Drupal\Core\Entity\ContentEntityBase') && $entity->getEntityTypeId() === $entity_type_id && $entity->hasField($field)) {
      $value = $entity->get($field)->getValue();

      $value_to_compare = NULL;

      // Structured data.
      if (is_array($value)) {
        if (!empty($value)) {
          // Loop through each value and compare.
          foreach ($value as $value_item) {
            // Check for target_id to support references.
            if (isset($value_item['target_id'])) {
              $value_to_compare = $value_item['target_id'];
            }
            // Check for uri to support links.
            elseif (isset($value_item['uri'])) {
              $value_to_compare = $value_item['uri'];
            }
            else {
              $value_to_compare = $value_item['value'];
            }
            // Return comparison only if true.
            if ($value_to_compare === $this->configuration['value']) {
              return TRUE;
            }
          }
        }
      }
      // Default.
      else {
        $value_to_compare = $value;
      }

      // Compare if null.
      if ($this->configuration['value_source'] === 'null') {
        return is_null($value_to_compare);
      }
      // Regular comparison.
      return $value_to_compare === $this->configuration['value'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Entity Type.
    $entity_type_id = $this->configuration['entity_type_id'];
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);

    // Field.
    $field = $this->configuration['field'];
    $field_label = '';

    // Get Field label.
    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $entity_bundle) as $field_definition) {
      if ($field_definition->getName() === $field) {
        $field_label = (string) $field_definition->getLabel();
      }
    }

    return t('@entity_type field "@field" is "@value"', [
      '@entity_type' => $entity_type_definition->getLabel(),
      '@field' => $field_label,
      '@value' => $this->configuration['value_source'] === 'null' ? 'is NULL' : $this->configuration['value'],
    ]);
  }

}
