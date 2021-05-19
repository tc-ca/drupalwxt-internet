<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Template\Attribute;

/**
 * Formatter for displaying list (text) in an HTML list.
 *
 * @FieldFormatter(
 *   id="list_string_list_class",
 *   label="List (with class)",
 *   field_types={
 *     "list_string",
 *   }
 * )
 */
class ListStringListClassFormatter extends FormatterBase {

  use ElementListClassTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $attributes = new Attribute();
    $class = $this->getSetting('class');
    if (!empty($class)) {
      $attributes->addClass($class);
    }

    $field_storage = $items->getFieldDefinition()->getFieldStorageDefinition();
    $options = options_allowed_values($field_storage);
    foreach ($items as $delta => $item) {
      $elements[$delta] = $options[$item->getValue()['value']];
    }

    return [
      [
        '#theme' => 'item_list',
        '#items' => $elements,
        '#list_type' => $this->getSetting('list_type'),
        '#attributes' => $attributes->toArray(),
      ],
    ];
  }

}
