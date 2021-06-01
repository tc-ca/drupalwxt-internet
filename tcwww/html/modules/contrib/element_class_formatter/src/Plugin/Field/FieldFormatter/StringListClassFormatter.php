<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Template\Attribute;

/**
 * Formatter for displaying strings in an HTML list.
 *
 * @FieldFormatter(
 *   id="string_list_class",
 *   label="List (with class)",
 *   field_types={
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class StringListClassFormatter extends FormatterBase {

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
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => '{{ value|nl2br }}',
        '#context' => ['value' => $item->value],
      ];
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
