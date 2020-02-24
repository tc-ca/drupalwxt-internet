<?php

namespace Drupal\blazy;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A Trait common for deprecated methods for easy removal and declutter.
 *
 * @todo remove at blazy:8.x-2.1, or earlier.
 * @see https://www.drupal.org/node/3103018
 */
trait BlazyDeprecatedTrait {

  /**
   * Deprecated method.
   *
   * @deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use
   *   self::imageAttributes() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public static function buildImageAttributes(array &$variables) {
    @trigger_error('buildImageAttributes is deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use \Drupal\blazy\Blazy::imageAttributes() instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);
    self::imageAttributes($variables);
  }

  /**
   * Deprecated method.
   *
   * @deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use
   *   self::buildIframe() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public static function buildIframeAttributes(array &$variables) {
    self::buildIframe($variables);
  }

  /**
   * Deprecated method.
   *
   * @deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use
   *   self::lazyAttributes() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public static function buildLazyAttributes(array &$attributes, array $settings = []) {
    @trigger_error('buildLazyAttributes is deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use \Drupal\blazy\Blazy::lazyAttributes() instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);
    self::lazyAttributes($attributes, $settings);
  }

  /**
   * Deprecated method.
   *
   * @deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use
   *   self::aspectRatioAttributes() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public static function buildAspectRatio(array &$attributes, array $settings = []) {
    @trigger_error('buildAspectRatio is deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use \Drupal\blazy\Blazy::aspectRatioAttributes() instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);
    self::aspectRatioAttributes($attributes, $settings);
  }

  /**
   * Deprecated method.
   *
   * @deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use
   *   self::urlAndDimensions() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public static function buildUrlAndDimensions(array &$settings, $item = NULL) {
    @trigger_error('buildUrlAndDimensions is deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.1. Use \Drupal\blazy\Blazy::urlAndDimensions() instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);
    self::urlAndDimensions($settings, $item);
  }

  /**
   * Implements hook_field_formatter_info_alter().
   *
   * @todo remove from blazy:8.x-2.1 for
   *   \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatter.
   * @see https://www.drupal.org/node/3103018
   */
  public static function fieldFormatterInfoAlter(array &$info) {
    // Supports optional Media Entity via VEM/VEF if available.
    $common = [
      'description' => new TranslatableMarkup('Displays lazyloaded images, or iframes, for VEF/ ME.'),
      'quickedit'   => ['editor' => 'disabled'],
      'provider'    => 'blazy',
    ];

    $info['blazy_file'] = $common + [
      'id'          => 'blazy_file',
      'label'       => new TranslatableMarkup('Blazy Image with VEF (deprecated)'),
      'class'       => 'Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatter',
      'field_types' => ['entity_reference', 'image'],
    ];

    $info['blazy_video'] = $common + [
      'id'          => 'blazy_video',
      'label'       => new TranslatableMarkup('Blazy Video (deprecated)'),
      'class'       => 'Drupal\blazy\Plugin\Field\FieldFormatter\BlazyVideoFormatter',
      'field_types' => ['video_embed_field'],
    ];
  }

}
