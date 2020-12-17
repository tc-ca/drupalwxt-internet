<?php

namespace Drupal\tocify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'tocify_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "tocify_formatter",
 *   label = @Translation("Table of contents (tocify)"),
 *   field_types = {
 *     "tocify"
 *   }
 * )
 */

class TocifyFieldFormatter extends FormatterBase {

  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element = [];

    // do nothing if toc is disabled 
    if (!isset($items[0]->tocify_enable) || !$items[0]->tocify_enable) {
      return $element;
    }

    foreach ($items as $delta => $item) {
      // get the options enabled for users to change
      $settings = $items[$delta]->getFieldDefinition()->getSetting('options');
      // get the field defaults to use when option is not available to the user
      $defaults = $items[$delta]->getFieldDefinition()->getDefaultValueLiteral();

      $element[$delta] = [

        '#theme' => 'tableofcontents',
        '#_theme' => !empty($settings['_theme']) ? $item->_theme : $defaults[0]['_theme'],
        '#context' => !empty($settings['_context']) ? $item->_context : $defaults[0]['_context'],
        '#selectors' => !empty($settings['selectors']) ? $item->selectors : $defaults[0]['selectors'],
        '#show_and_hide' => !empty($settings['show_and_hide']) ? $this->formatBoolean($item->show_and_hide) : $this->formatBoolean($defaults[0]['show_and_hide']),
        '#show_effect' => !empty($settings['show_effect']) ? $item->show_effect : $defaults[0]['show_effect'],
        '#show_effect_speed' => !empty($settings['show_effect_speed']) ? $item->show_effect_speed : $defaults[0]['show_effect_speed'],
        '#hide_effect' => !empty($settings['hide_effect']) ? $item->hide_effect : $defaults[0]['hide_effect'],
        '#hide_effect_speed' => !empty($settings['hide_effect_speed']) ? $item->hide_effect_speed : $defaults[0]['hide_effect_speed'],
        '#smooth_scroll' => !empty($settings['smooth_scroll']) ? $this->formatBoolean($item->smooth_scroll) : $this->formatBoolean($defaults[0]['smooth_scroll']),
        '#smooth_scroll_speed' => !empty($settings['smooth_scroll_speed']) ? $item->smooth_scroll_speed : $defaults[0]['smooth_scroll_speed'],
        '#scroll_to' => !empty($settings['scroll_to']) ? (string) $item->scroll_to : $defaults[0]['scroll_to'],
        '#show_and_hide_on_scroll' => !empty($settings['show_and_hide_on_scroll']) ? $this->formatBoolean($item->show_and_hide_on_scroll) : $this->formatBoolean($defaults[0]['show_and_hide_on_scroll']),
        '#highlight_on_scroll' => !empty($settings['highlight_on_scroll']) ? $this->formatBoolean($item->highlight_on_scroll) : $this->formatBoolean($defaults[0]['highlight_on_scroll']),
        '#highlight_offset' => !empty($settings['highlight_offset']) ? (string) $item->highlight_offset : $defaults[0]['highlight_offset'],
        '#extend_page' => !empty($settings['extend_page']) ? $this->formatBoolean($item->extend_page) : $this->formatBoolean($defaults[0]['extend_page']),
        '#extend_page_offset' => !empty($settings['extend_page_offset']) ? (string) $item->extend_page_offset : (string) $defaults[0]['extend_page_offset'],
        '#history' => !empty($settings['history']) ? $this->formatBoolean($item->history) : $defaults[0]['history'],
        '#hash_generator' => !empty($settings['hash_generator']) ? $item->hash_generator : $defaults[0]['hash_generator'],
        '#highlight_default' => !empty($settings['highlight_default']) ? $this->formatBoolean($item->highlight_default) : $this->formatBoolean($defaults[0]['highlight_default']),
        '#ignore_selector' => !empty($settings['ignore_selector']) ? $item->ignore_selector : $defaults[0]['ignore_selector'],
        '#scroll_history' => !empty($settings['scroll_history']) ? $this->formatBoolean($item->scroll_history) : $this->formatBoolean($defaults[0]['scroll_history']),
        '#attached' => array(
          'library' => array(
            'tocify/tocify',
          ),
        ),
      ];
    }

    return $element;
  }

  /**
   * Format a boolean as string.
   *
   * @param bool $bool
   *   A boolean to be reformatted as string.
   *
   * @return string
   *   A string in the form of 'true' or 'false'.
   */
  private function formatBoolean($bool) {
    return $bool ? 'true' : 'false';
  }
}
