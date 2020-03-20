<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;

/**
 * Provides optional Views integration.
 */
class BlazyViews {

  /**
   * Implements hook_views_pre_render().
   */
  public static function viewsPreRender($view) {
    // Load Blazy library once, not per field, if any Blazy Views field found.
    if ($blazy = self::viewsField($view)) {
      $plugin_id = $view->getStyle()->getPluginId();
      $settings = $blazy->mergedViewsSettings();
      $load = $blazy->blazyManager()->attach($settings);

      // Enforce Blazy to work with hidden element such as with EB selection.
      // @todo refine this to selectively loadInvisible by request.
      $load['drupalSettings']['blazy']['loadInvisible'] = TRUE;
      $view->element['#attached'] = isset($view->element['#attached']) ? NestedArray::mergeDeep($view->element['#attached'], $load) : $load;

      $grid = $plugin_id == 'blazy';
      if ($options = $view->getStyle()->options) {
        $grid = empty($options['grid']) ? $grid : TRUE;
      }

      // Prevents dup [data-LIGHTBOX-gallery] if the Views style supports Grid.
      if (!$grid) {
        // @todo remove conditions when confident, kept to avoid the unexpected.
        $view->element['#attributes'] = empty($view->element['#attributes']) ? [] : $view->element['#attributes'];
        Blazy::containerAttributes($view->element['#attributes'], $settings);
      }
    }
  }

  /**
   * Returns one of the Blazy Views fields, if available.
   */
  public static function viewsField($view) {
    foreach (['file', 'media'] as $entity) {
      if (isset($view->field['blazy_' . $entity])) {
        return $view->field['blazy_' . $entity];
      }
    }
    return FALSE;
  }

  /**
   * Implements hook_preprocess_views_view().
   */
  public static function preprocessViewsView(array &$variables, $lightboxes) {
    preg_match('~blazy--(.*?)-gallery~', $variables['css_class'], $matches);
    $lightbox = $matches[1] ? str_replace('-', '_', $matches[1]) : FALSE;

    // Given blazy--photoswipe-gallery, adds the [data-photoswipe-gallery], etc.
    if ($lightbox && in_array($lightbox, $lightboxes)) {
      $settings['namespace'] = 'blazy';
      $settings['media_switch'] = $matches[1];
      // @todo remove conditions when confident, kept to avoid the unexpected.
      $variables['attributes'] = empty($variables['attributes']) ? [] : $variables['attributes'];
      Blazy::containerAttributes($variables['attributes'], $settings);
    }
  }

}
