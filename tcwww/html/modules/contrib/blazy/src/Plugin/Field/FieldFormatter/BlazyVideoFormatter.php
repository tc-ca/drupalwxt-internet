<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\blazy\Dejavu\BlazyVideoBase;

@trigger_error('The ' . __NAMESPACE__ . '\BlazyVideoFormatter is deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatter instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);

/**
 * Plugin implementation of the 'Blazy Video' to get VEF videos.
 *
 * @todo remove prior to full release. This means Slick Video which depends
 * on VEF is deprecated for main Slick at Blazy 8.2.x with core Media only.
 * @todo make is useful for local video instead?
 */
class BlazyVideoFormatter extends BlazyVideoBase implements ContainerFactoryPluginInterface {

  use BlazyFormatterTrait;
  use BlazyFormatterViewTrait;
  use BlazyFormatterOEmbedTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return $this->commonViewElements($items, $langcode);
  }

  /**
   * Build the blazy elements.
   */
  public function buildElements(array &$build, $items) {
    $settings = $build['settings'];

    foreach ($items as $delta => $item) {
      $settings['input_url'] = strip_tags($item->value);
      $settings['delta'] = $delta;
      if (empty($settings['input_url'])) {
        continue;
      }

      $this->blazyOembed->build($settings);

      $box = ['item' => $item, 'settings' => $settings];

      // Image with responsive image, lazyLoad, and lightbox supports.
      $build[$delta] = $this->formatter->getBlazy($box);
      unset($box);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'fieldable_form' => TRUE,
      'multimedia'     => TRUE,
      'view_mode'      => $this->viewMode,
    ] + parent::getScopedFormElements();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getType() === 'video_embed_field';
  }

}
