<?php

namespace Drupal\tocify\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TocifyFieldWidget' widget.
 *
 * @FieldWidget(
 *   id = "tocify_widget",
 *   label = @Translation("Table of Contents (tocify)"),
 *   description = @Translation("Use to configure Tocify ToC"),
 *   field_types = {
 *     "tocify",
 *   }
 * )
 */

class TocifyFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */

  public function formElement( FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // @TODO figure out why this makes both the widget default value and node form work
    //if ($this->isDefaultValueWidget($form_state)) {
    //  $item = $items[$delta];
    //  $value = $item->toArray();
    //}

    $element += [
      '#type' => 'fieldset',
    ];

    $element['tocify_enable'] = [ 
      '#type' => 'checkbox',
      '#title' => $this->t('Enable table of contents'),
      '#description' => $this->t('Generate a table of contents automatically.'),
      '#default_value' => isset($items[$delta]->tocify_enable) ? $items[$delta]->tocify_enable : 0,
    ];

    $defaults = \Drupal::config('tocify.settings');
    $settings = array_filter($this->getFieldSetting('options'));

    $options = getTocifyOptions();
    foreach ($options as $key => $option) {
      if (isset($settings[$key]) || $this->isDefaultValueWidget($form_state)) {
        $element[$key] = array(
          '#type' => 'textfield',
          '#title' => $option['title'],
          '#description' => $option['desc'],
          '#default_value' => isset($items[$delta]->$key) ? $items[$delta]->$key : $defaults->get($key),
          '#maxlength' => 64,
          '#size' => 64,
          '#weight' => '0',
        );
   
        // enable ajax states if not on field config page 
        if (!$this->isDefaultValueWidget($form_state)) {
          $element[$key]['#states'] = [
            'visible' => [
              ':input[name*="tocify_enable"]' => ['checked' => TRUE],
            ],
          ];
        }
      }
    }

    return $element;
  }
}
