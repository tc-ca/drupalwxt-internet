<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides Blazy utilities.
 */
class BlazyUtil {

  /**
   * Generates an SVG Placeholder.
   *
   * @param string $width
   *   The image width.
   * @param string $height
   *   The image height.
   *
   * @return string
   *   Returns a string containing an SVG.
   */
  public static function generatePlaceholder($width, $height): string {
    return 'data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D\'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg\' viewBox%3D\'0 0 ' . $width . ' ' . $height . '\'%2F%3E';
  }

  /**
   * Returns the sanitized attributes for user-defined (UGC Blazy Filter).
   *
   * When IMG and IFRAME are allowed for untrusted users, trojan horses are
   * welcome. Hence sanitize attributes relevant for BlazyFilter. The rest
   * should be taken care of by HTML filters after Blazy.
   *
   * @param array $attributes
   *   The given attributes to sanitize.
   *
   * @return array
   *   The sanitized $attributes suitable for UGC, such as Blazy filter.
   */
  public static function sanitize(array $attributes = []) {
    $clean_attributes = [];
    $tags = ['href', 'poster', 'src', 'about', 'data', 'action', 'formaction'];
    foreach ($attributes as $key => $value) {
      if (is_array($value)) {
        // Respects array item containing space delimited classes: aaa bbb ccc.
        $value = implode(' ', $value);
        $clean_attributes[$key] = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', explode(' ', $value));
      }
      else {
        // Since Blazy is lazyloading known URLs, sanitize attributes which
        // make no sense to stick around within IMG or IFRAME tags.
        $kid = substr($key, 0, 2) === 'on' || in_array($key, $tags);
        $key = $kid ? 'data-' . $key : $key;
        $clean_attributes[$key] = $kid ? Html::cleanCssIdentifier($value) : Html::escape($value);
      }
    }
    return $clean_attributes;
  }

  /**
   * Returns the URI from the given image URL, relevant for unmanaged files.
   */
  public static function buildUri($image_url) {
    if (!UrlHelper::isExternal($image_url) && $normal_path = UrlHelper::parse($image_url)['path']) {
      $public_path = Settings::get('file_public_path');

      // Only concerns for the correct URI, not image URL which is already being
      // displayed via SRC attribute. Don't bother language prefixes for IMG.
      if ($public_path && strpos($normal_path, $public_path) !== FALSE) {
        $rel_path = str_replace($public_path, '', $normal_path);
        return file_build_uri($rel_path);
      }
    }
    return FALSE;
  }

  /**
   * Determines whether the URI has a valid scheme for file API operations.
   *
   * This is just a wrapper around
   * Drupal\Core\StreamWrapper\StreamWrapperManager::isValidUri() for Drupal
   * versions >= 8.8, with a fallback to file_valid_uri() for prior Drupal
   * versions.
   *
   * @param string $uri
   *   The URI to be tested.
   *
   * @return bool
   *   TRUE if the URI is valid.
   *
   * @todo Remove this once Drupal 8.7 is no longer supported.
   */
  public static function isValidUri($uri) {
    if (version_compare(\Drupal::VERSION, '8.8', '>=')) {
      // Adds a check to pass the tests due to non-DI.
      return Blazy::streamWrapperManager() ? Blazy::streamWrapperManager()->isValidUri($uri) : FALSE;
    }
    else {
      // Because this code only runs for older Drupal versions, we do not need
      // or want IDEs or the Upgrade Status module warning people about this
      // deprecated code usage. Setting the function name dynamically
      // circumvents those warnings.
      $function = 'file_valid_uri';
      return $function($uri);
    }
  }

  /**
   * Provides image url based on the given settings.
   */
  public static function imageUrl(array &$settings) {
    // Provides image_url, not URI, expected by lazyload.
    $uri = $settings['uri'];
    $image_url = self::isValidUri($uri) ? self::transformRelative($uri) : $uri;
    $settings['image_url'] = empty($settings['image_url']) ? $image_url : $settings['image_url'];

    // Image style modifier can be multi-style images such as GridStack.
    if (!empty($settings['image_style']) && ($style = ImageStyle::load($settings['image_style']))) {
      $settings['image_url'] = self::transformRelative($uri, $style);
      $settings['cache_tags'] = $style->getCacheTags();

      // Only re-calculate dimensions if not cropped, nor already set.
      if (empty($settings['_dimensions'])) {
        $settings = array_merge($settings, self::transformDimensions($style, $settings));
      }
    }

    // Just in case, an attempted kidding gets in the way, relevant for UGC.
    $data_uri = !empty($settings['use_data_uri']) && substr($settings['image_url'], 0, 10) === 'data:image';
    if (!empty($settings['_check_protocol']) && !$data_uri) {
      $settings['image_url'] = UrlHelper::stripDangerousProtocols($settings['image_url']);
    }
  }

  /**
   * Provides image dimensions based on the given image item.
   */
  public static function imageDimensions(array &$settings, $item = NULL, $initial = FALSE) {
    $width = $initial ? '_width' : 'width';
    $height = $initial ? '_height' : 'height';

    if (empty($settings[$width])) {
      $settings[$width] = $item && isset($item->width) ? $item->width : NULL;
      $settings[$height] = $item && isset($item->height) ? $item->height : NULL;
    }
  }

  /**
   * A wrapper for ImageStyle::transformDimensions().
   *
   * @param object $style
   *   The given image style.
   * @param array $data
   *   The data settings: _width, _height, first_uri, width, height, and uri.
   * @param bool $initial
   *   Whether particularly transforms once for all, or individually.
   *
   * @todo remove first_uri for _uri for consistency.
   */
  public static function transformDimensions($style, array $data, $initial = FALSE) {
    $width  = $initial ? '_width' : 'width';
    $height = $initial ? '_height' : 'height';
    $uri    = $initial ? (isset($data['_uri']) ? '_uri' : 'first_uri') : 'uri';
    $width  = isset($data[$width]) ? $data[$width] : NULL;
    $height = isset($data[$height]) ? $data[$height] : NULL;
    $dim    = ['width' => $width, 'height' => $height];

    // Funnily $uri is ignored at all core image effects.
    $style->transformDimensions($dim, $data[$uri]);

    // Sometimes they are string, cast them integer to reduce JS logic.
    return ['width' => (int) $dim['width'], 'height' => (int) $dim['height']];
  }

  /**
   * A wrapper for file_url_transform_relative() to pass tests anywhere else.
   */
  public static function transformRelative($uri, $style = NULL) {
    $url = $style ? $style->buildUrl($uri) : file_create_url($uri);
    return file_url_transform_relative($url);
  }

}
