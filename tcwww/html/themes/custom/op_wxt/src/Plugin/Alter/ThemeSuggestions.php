<?php

namespace Drupal\op_wxt\Plugin\Alter;

use Drupal\wxt_bootstrap\Plugin\Alter\ThemeSuggestions as BootstrapThemeSuggestions;
use Drupal\block\Entity\Block;
use Drupal\bootstrap\Utility\Variables;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("theme_suggestions")
 */
class ThemeSuggestions extends BootstrapThemeSuggestions {

  /**
   * {@inheritdoc}
   */
  public function alter(&$suggestions, &$context1 = NULL, &$hook = NULL) {
    $variables = Variables::create($context1);

    /** @var \Drupal\wxt_library\LibraryService $wxt */
    $wxt = \Drupal::service('wxt_library.service_wxt');
    $wxt_active = $wxt->getLibraryName();

    switch ($hook) {

      case 'form':
        if ($variables['element']['#form_id'] == 'custom_search_block_form') {
          $suggestions[] = 'form__custom_search_block_form';
          $suggestions[] = 'form__custom_search_block_form__' . $wxt_active;
        }
        break;
    }

    parent::alter($suggestions, $context1, $hook);
  }

}
