<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;
use Drupal\image\Entity\ImageStyle;

/**
 * Implements BlazyBreakpointInterface.
 *
 * @todo deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.0. Do not
 *   use it instead.
 * @see https://www.drupal.org/node/3105243
 */
class BlazyBreakpoint {

  /**
   * Provides re-usable breakpoint data-attributes.
   *
   * These attributes can be applied to either IMG or DIV as CSS background.
   *
   * $settings['breakpoints'] must contain: xs, sm, md, lg breakpoints with
   * the expected keys: width, image_style.
   *
   * @param array $attributes
   *   The attributes being modified.
   * @param array $settings
   *   The given settings being modified.
   *
   * @see self::preprocessBlazy()
   */
  public static function attributes(array &$attributes, array &$settings) {
    // Only provide multi-serving image URLs if breakpoints are provided.
    if (empty($settings['breakpoints'])) {
      return;
    }

    $srcset = $json = [];
    // https://css-tricks.com/sometimes-sizes-is-quite-important/
    // For older iOS devices that don't support w descriptors in srcset, the
    // first source item in the list will be used.
    $settings['breakpoints'] = array_reverse($settings['breakpoints']);
    foreach ($settings['breakpoints'] as $key => $breakpoint) {
      if (!($style = ImageStyle::load($breakpoint['image_style']))) {
        continue;
      }

      // Supports multi-breakpoint aspect ratio with irregular sizes.
      // Yet, only provide individual dimensions if not already set.
      // See Drupal\blazy\BlazyFormatter::setImageDimensions().
      $width = self::widthFromDescriptors($breakpoint['width']);
      if ($width && !empty($settings['_breakpoint_ratio']) && empty($settings['blazy_data']['dimensions'])) {
        $dimensions = BlazyUtil::transformDimensions($style, $settings);

        $json[$width] = round((($dimensions['height'] / $dimensions['width']) * 100), 2);
      }

      $url = BlazyUtil::transformRelative($settings['uri'], $style);
      $settings['breakpoints'][$key]['url'] = $url;

      // Still working with GridStack multi-image-style per grid box at 2019.
      if (!empty($settings['background'])) {
        $attributes['data-src-' . $key] = $url;
      }
      else {
        $width = trim($breakpoint['width']);
        $width = is_numeric($width) ? $width . 'w' : $width;
        $srcset[] = $url . ' ' . $width;
      }
    }

    if ($srcset) {
      $settings['srcset'] = implode(', ', $srcset);

      $attributes['srcset'] = '';
      $attributes['data-srcset'] = $settings['srcset'];
      $attributes['sizes'] = '100w';

      if (!empty($settings['sizes'])) {
        $attributes['sizes'] = trim($settings['sizes']);
        $settings['_sizes'] = $settings['sizes'];
        unset($attributes['height'], $attributes['width']);
      }
    }

    if ($json) {
      $settings['blazy_data']['dimensions'] = $json;
    }
  }

  /**
   * Gets the numeric "width" part from a descriptor.
   */
  public static function widthFromDescriptors($descriptor = '') {
    // Dynamic multi-serving aspect ratio with backward compatibility.
    $descriptor = trim($descriptor);
    if (is_numeric($descriptor)) {
      return (int) $descriptor;
    }

    // Cleanup w descriptor to fetch numerical width for JS aspect ratio.
    $width = strpos($descriptor, "w") !== FALSE ? str_replace('w', '', $descriptor) : $descriptor;

    // If both w and x descriptors are provided.
    if (strpos($descriptor, " ") !== FALSE) {
      // If the position is expected: 640w 2x.
      list($width, $px) = array_pad(array_map('trim', explode(" ", $width, 2)), 2, NULL);

      // If the position is reversed: 2x 640w.
      if (is_numeric($px) && strpos($width, "x") !== FALSE) {
        $width = $px;
      }
    }

    return is_numeric($width) ? (int) $width : FALSE;
  }

  /**
   * Cleans up empty, or not so empty, breakpoints.
   *
   * @param array $settings
   *   The settings being modified.
   */
  public static function cleanUpBreakpoints(array &$settings = []) {
    if (!empty($settings['breakpoints'])) {
      $breakpoints = array_filter(array_map('array_filter', $settings['breakpoints']));

      $settings['breakpoints'] = NestedArray::filter($breakpoints, function ($breakpoint) {
        return !(is_array($breakpoint) && (empty($breakpoint['width']) || empty($breakpoint['image_style'])));
      });
    }
  }

  /**
   * Builds breakpoints suitable for top-level [data-blazy] wrapper attributes.
   *
   * The hustle is because we need to define dimensions once, if applicable, and
   * let all images inherit. Each breakpoint image may be cropped, or scaled
   * without a crop. To set dimensions once requires all breakpoint images
   * uniformly cropped. But that is not always the case.
   *
   * @param array $settings
   *   The settings being modified.
   * @param object $item
   *   The \Drupal\image\Plugin\Field\FieldType\ImageItem item.
   */
  public static function buildDataBlazy(array &$settings, $item = NULL) {
    // Identify that Blazy can be activated by breakpoints, regardless results.
    $settings['blazy'] = TRUE;

    // Bail out if blazy_data defined at BlazyFormatter::setImageDimensions().
    // Blazy doesn't always deal with image formatters, see self::isBlazy().
    if (!empty($settings['blazy_data'])) {
      return;
    }

    // May be set at BlazyFormatter::setImageDimensions() if using formatters,
    // yet not set from non-formatters like views fields, see self::isBlazy().
    BlazyUtil::imageDimensions($settings, $item, TRUE);

    $sources = $styles = [];
    $end = end($settings['breakpoints']);

    // Check for cropped images at the 5 given styles before any hard work.
    // Ok as run once at the top container regardless of thousand of images.
    foreach ($settings['breakpoints'] as $key => $breakpoint) {
      if ($style = blazy()->isCrop($breakpoint['image_style'])) {
        $styles[$key] = $style;
      }
    }

    // Bail out if not all images are cropped at all breakpoints.
    // The site builder just don't read the performance tips section.
    if (count($styles) != count($settings['breakpoints'])) {
      return;
    }

    // We have all images cropped here.
    foreach ($settings['breakpoints'] as $key => $breakpoint) {
      if (!($width = self::widthFromDescriptors($breakpoint['width']))) {
        continue;
      }

      // Sets dimensions once, and let all images inherit.
      if (($style = $styles[$key]) && (!empty($settings['first_uri']) && !empty($settings['ratio']))) {
        $dimensions = BlazyUtil::transformDimensions($style, $settings, TRUE);

        $padding = round((($dimensions['height'] / $dimensions['width']) * 100), 2);
        $settings['blazy_data']['dimensions'][$width] = $padding;

        // Only set padding-bottom for the last breakpoint to avoid FOUC.
        if ($end['width'] == $breakpoint['width']) {
          $settings['padding_bottom'] = $padding;
        }
      }

      // If BG, provide [data-src-BREAKPOINT], regardless uri or ratio.
      if (!empty($settings['background'])) {
        $sources[] = ['width' => (int) $width, 'src' => 'data-src-' . $key];
      }
    }

    // Supported modules can add blazy_data as [data-blazy] to the container.
    // This also informs individual images to not work with dimensions any more
    // as _all_ breakpoint image styles contain 'crop'.
    // As of Blazy v1.6.0 applied to BG only.
    if ($sources) {
      $settings['blazy_data']['breakpoints'] = $sources;
    }
  }

}
