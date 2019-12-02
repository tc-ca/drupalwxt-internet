<?php

namespace Drupal\entity_field_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Node Field' condition.
 *
 * @Condition(
 *   id = "node_field",
 *   label = @Translation("Node Field"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       required = TRUE,
 *       label = @Translation("node")
 *     )
 *   }
 * )
 */
class NodeField extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a new ExampleCondition instance.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get all Node types.
    $node_types = NodeType::loadMultiple();

    $options = [
      '' => $this->t('None'),
    ];
    foreach ($node_types as $node_type) {
      $fields = [];
      foreach (\Drupal::service('entity.manager')->getFieldDefinitions('node', $node_type->get('type')) as $field_definition) {
        $value = $node_type->get('type') . '|' . $field_definition->getName();
        $label = (string) $field_definition->getLabel();
        $fields[$value] = $label;
      }

      $options[$node_type->get('name')] = $fields;
    }
    ksort($options);

    $form['node_type_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Node Type and Field'),
      '#options' => $options,
      '#default_value' => $this->configuration['entity_bundle'] . '|' . $this->configuration['field'],
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
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('node_type_field')) {
      $this->configuration['entity_type_id'] = 'node';
      $this->configuration['entity_bundle'] = $this->getNodeType($form_state->getValue('node_type_field'));
      $this->configuration['field'] = $this->getFieldName($form_state->getValue('node_type_field'));

      $this->configuration['value_source'] = $form_state->getValue('value_source');
      $this->configuration['value'] = $form_state->getValue('value');
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type_id' => 'node',
      'entity_bundle' => '',
      'field' => '',
      'value_source' => 'null',
      'value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate() {
    if (empty($this->configuration['field']) && !$this->isNegated()) {
      return TRUE;
    }

    $entity_type_id = $this->configuration['entity_type_id'];
    $entity_bundle = $this->configuration['entity_bundle'];
    $field = $this->configuration['field'];

    $entity = $this->getContextValue($entity_type_id);

    if (is_subclass_of($entity, 'Drupal\Core\Entity\ContentEntityBase') && $entity->getEntityTypeId() === $entity_type_id && $entity->getType() === $entity_bundle) {
      $value = $entity->get($field)->getValue();

      $value_to_compare = NULL;

      // Structured data.
      if (is_array($value)) {
        if (!empty($value)) {
          $value_to_compare = $value[0]['value'];
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
   * Provides a human readable summary of the condition's configuration.
   */
  public function summary() {
    // Entity Type.
    $entity_type_id = $this->configuration['entity_type_id'];
    $entity_type_definition = \Drupal::service('entity.manager')->getDefinition($entity_type_id);

    // Entity Bundle.
    $entity_bundle = $this->configuration['entity_bundle'];

    // Field.
    $field = $this->configuration['field'];

    // Get Field label.
    foreach (\Drupal::service('entity.manager')->getFieldDefinitions($entity_type_id, $entity_bundle) as $field_definition) {
      if ($field_definition->getName() === $field) {
        $field_label = (string) $field_definition->getLabel();
      }
    }

    return t('@entity_type "@entity_bundle" field "@field" is "@value"', [
      '@entity_type' => $entity_type_definition->getLabel(),
      '@entity_bundle' => $entity_bundle,
      '@field' => $field_label,
      '@value' => $this->configuration['value_source'] === 'null' ? 'is NULL' : $this->configuration['value'],
    ]);
  }

  /**
   * Get the Node Type from a $node_type_field.
   *
   * @param string $node_type_field
   *   A value containing the node type and field, separated by |.
   *
   * @return string
   *   The Node Type machine name.
   */
  private function getNodeType($node_type_field) {
    if ($node_type_field) {
      $node_type_field_exploded = explode('|', $node_type_field);
      return $node_type_field_exploded[0];
    }
  }

  /**
   * Get the Field Name from a $node_type_field.
   *
   * @param string $node_type_field
   *   A value containing the node type and field, separated by |.
   *
   * @return string
   *   The field machine name.
   */
  private function getFieldName($node_type_field) {
    if ($node_type_field) {
      $node_type_field_exploded = explode('|', $node_type_field);
      return $node_type_field_exploded[1];
    }
  }

}
