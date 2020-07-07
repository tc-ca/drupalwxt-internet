<?php

namespace Drupal\op_wxt\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\Page as BootstrapPage;

/**
 * Pre-processes variables for the "page" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends BootstrapPage {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {

    if (\Drupal::service('path.matcher')->isFrontPage()) {
       $variables['wxt_homepage'] = TRUE;
    }

    parent::preprocess($variables, $hook, $info);
  }

}
