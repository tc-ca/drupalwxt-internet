<?php

namespace Drupal\tocify\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldType(
 *   id = "tocify",
 *   module = "tocify",
 *   label = @Translation("Table of contents (tocify)"),
 *   description = @Translation("This field renders a table of contents using tocify."),
 *   default_widget = "tocify_widget",
 *   default_formatter = "tocify_formatter",
 *   cardinality = 1,
 * )
 */

class TocifyField extends FieldItemBase {

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = [];

    $properties['tocify_enable'] = DataDefinition::create('boolean')
        ->setLabel(t('Enable tocify'))
        ->setDescription(t('Enables automatic table of contents using tocify'));

    $options = getTocifyOptions();
    foreach ($options as $key => $option) {
      $properties[$key] = DataDefinition::create('string')
        ->setLabel($option['title'])
        ->setDescription($option['desc']);
    }

    return $properties;
  }

  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [];

    $columns['tocify_enable'] = [
      'description' => 'Flag to control whether tocify is on or off.',
      'type' => 'int',
      'size' => 'tiny',
      'unsigned' => TRUE,
      'default' => 0,
    ];

    $options = getTocifyOptions();
    foreach ($options as $key => $option) {
      $columns[$key] = [ 
        'description' => $option['desc'],
        'type' => 'varchar',
        'length' => 64,
      ];
    }

    $schema = array(
      'columns' => $columns,
      'indexes' => [],
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'tocify_enable' => FALSE,
      'options' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();
    $options = getTocifyOptions();
    
    // set up options for checkboxes
    $checkbox_options = [];
    foreach ($options as $key => $option) {
      $checkbox_options[$key] = $option['title'];
    }

    $element['options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Allow user edit'),
      '#description' => t('Select the options that users are allowed to configure.'),
      '#default_value' => !empty($settings['options']) ? $settings['options'] : [],
      '#options' => $checkbox_options,
    ];

    return $element;
  }
  
  public function isEmpty() {
    $enabled = $this->get('tocify_enable')->getValue();
    $theme = $this->get('_theme')->getValue();
    return empty($enabled) && empty($theme);
  }

}
