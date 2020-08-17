<?php

namespace Drupal\op_wxt\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\Links as BootstrapLinks;

/**
 * Pre-processes variables for the "links" theme hook 
 * - overrides wxt_bootstrap preprocess to add graceful handling of more languages
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("links")
 */
class Links extends BootstrapLinks {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    // Language Handling.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $language_prefix = \Drupal::config('language.negotiation')->get('url.prefixes');
    $variables['language'] = $language;
    $variables['language_prefix'] = $language_prefix[$language];

    if (isset($variables['links']['comment-add'])) {
      $variables['links']['comment-add']['link']['#options']['attributes']['class'][] = 'btn btn-default';
    }

    if ($variables['theme_hook_original'] == 'links__language_block__gcweb') {
      // Add 'lang' attribute on language switcher for screen readers.
      $languages = \Drupal::languageManager()->getLanguages();
      foreach ($languages as $key => $lang) {
        if (isset($variables['links'][$key])) {
          $variables['links'][$key]['link']['#options']['attributes']['lang'] = $key;
        }
      }

      foreach ($languages as $key => $lang) {
        // If not one of our national languages
        if ($key != 'en' && $key != 'fr') {
          // Remove language link if we are either not on a node 
          // or we are on a node with the translation not present
          $node = \Drupal::request()->attributes->get('node');
          if (!$node || ($node && !$node->hasTranslation($key))) {
            unset($variables['links'][$key]);
          }
        }
      }

    }
    parent::preprocess($variables, $hook, $info);
  }

}
