<?php

namespace Drupal\openplus\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Provides a 'Node has term' condition without specifying bundle - adapted from entity_field_condition.
 *
 * @Condition(
 *   id = "op_node_has_term",
 *   label = @Translation("Node has term"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       required = TRUE,
 *       label = @Translation("node")
 *     )
 *   }
 * )
 */
class NodeHasTerm extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  use \Drupal\webform\Element\WebformTermReferenceTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new NodeHasTerm instance.
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = ['_none' => $this->t('- Select -')];
    $all_vocabs = taxonomy_vocabulary_get_names();
    $vocabularies = [];
    foreach ($all_vocabs as $vid) {
      $vocab = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vid);
      $options[$vid] = $vocab->label();
    }

    $values = $form_state->getValues();

    // some forms have different keys for the visibility conditions    
    if (isset($values['visibility'])) {
      $form_key = 'visibility';
    }
    else {
      $form_key = 'conditions';
    }
    $selected = isset($values[$form_key]['op_node_has_term']['vocabulary']) ? $values[$form_key]['op_node_has_term']['vocabulary'] : NULL;

    if ($selected) {
      $vocab = $selected; 
    }
    elseif (!empty($this->configuration['vocabulary'])) {
      $vocab = $this->configuration['vocabulary'];
    }
    else {
      $vocab = '';
    }

    // Load vocabularies 
    $form['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#options' => $options,
      '#default_value' => isset($this->configuration['vocabulary']) ? $this->configuration['vocabulary'] : 0,
      '#ajax' => array(
        'callback' => [$this, 'listTermsCallback'], 
        'wrapper' => 'dropdown-terms-replace',
        //'event' => 'change',
      ),
    ];

    $form['terms'] = [
      '#type' => 'webform_term_select',
      '#title' => $this->t('Terms'),
      '#multiple' => TRUE,
      '#prefix' => '<div id="dropdown-terms-replace">', 
      '#suffix' => '</div>',
      //'#required' => TRUE,
      '#tree_delimiter' => '-',
      '#breadcrumb_delimiter' => '',
      //'#states' => array(
      //  'invisible' => array(
      //    ':input[id="edit-conditions-gcext-node-has-term-vocabulary"]' => array('value' => '_none'),
      // ),
      // 'optional' => array(
      //   ':input[id="edit-conditions-gcext-node-has-term-vocabulary"]' => array('value' => '_none'),
      // ),
      //),
      '#vocabulary' => $vocab, 
      '#default_value' => $this->configuration['terms'],
    ];

// Not sure if we keep this and use it if webform not installed or just add webform as a dependency
/*
    $form['terms'] = array(
      '#type' => 'select', 
      '#title' => $this->t('Select terms'),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#prefix' => '<div id="dropdown-terms-replace">', 
      '#suffix' => '</div>',
      '#options' => $term_options, 
      //'#default_value' => NULL !== $form_state->getValue('terms') ? $form_state->getValue('terms') : $this->configuration['terms'],
      '#default_value' => $this->configuration['terms'],
    );
*/

    return parent::buildConfigurationForm($form, $form_state);
  }

  public function getTerms($vocabulary) {

    // @TODO USE WEBFORM TRAIT TO DO THIS FOR THE - formatting
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary);

    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name . ' (' . $term->tid . ')';
    }
    asort($options);

    return $options;
  }

  /**
   * Called via Ajax to populate the terms from the field.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form value field structure.
   */
  public function listTermsCallback(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_id = $values['id'];
    // some forms have different keys for the visibility conditions    
    if (isset($values['visibility'])) {
      $form_key = 'visibility';
    }
    else {
      $form_key = 'conditions';
    }

    $selected = $values[$form_key]['op_node_has_term']['vocabulary'];
    //$form['conditions']['op_node_has_term']['terms']['#options'] = $this->getTerms($selected); 
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $element = $form[$form_key]['op_node_has_term']['terms'];
    $element['#vocabulary'] = $selected;
    $form[$form_key]['op_node_has_term']['terms']['#options'] = self::getOptionsTree($element, $language); 

    return $form[$form_key]['op_node_has_term']['terms'];
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
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['vocabulary'] = $form_state->getValue('vocabulary') != '_none' ? $form_state->getValue('vocabulary') : NULL;
    $this->configuration['terms'] = $form_state->getValue('vocabulary') != '_none' ? $form_state->getValue('terms') : NULL;
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [
      'vocabulary' => NULL,
      'terms' => NULL,
    ];

    return $configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if ((empty($this->configuration['vocabulary']) || empty($this->configuration['terms'])) && !$this->isNegated()) {
      return TRUE;
    }

    // terms to look for
    $termIds = $this->configuration['terms'];

    $node = $this->getContextValue('node');

    foreach ($node->getFields() as $field) {
      // Only look for fields that are entity reference fields.
      $field_definition = $field->getFieldDefinition();
      //if ($field instanceof EntityReferenceFieldItemListInterface) {
      if ($field_definition->getType() == 'entity_reference') {

        // Get the field settings.
        $target_type = $field_definition->getSetting('target_type');
        // Check that the field targets are taxonomy terms.
        if ($target_type == 'taxonomy_term') {
          $handler_settings = $field_definition->getSetting('handler_settings');
          // if the field references the bundle configured in our condition
          if (in_array($this->configuration['vocabulary'], $handler_settings['target_bundles'])) {
            $field_name = $field->getName();
            $field_value = $node->get($field_name)->getValue();
            foreach ($field_value as $value) {
              // if the field value is in our configured set of terms
              if (in_array($value['target_id'], $this->configuration['terms'])) { 
                return TRUE;
              }
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['vocabulary'])) {
      $vocab = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($this->configuration['vocabulary']);
      $vocab_label = $vocab->label();
  
      if (!empty($this->configuration['terms'])) {
        $terms = [];
        foreach ($this->configuration['terms'] as $tid) {
          $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
          $terms[] = $term->label();
        }
  
        return t('Node has tags from @vocab: @terms.', [
          '@vocab' => $vocab_label,
          '@terms' => implode(', ', $terms),
        ]);
      }
    }
  }

}
