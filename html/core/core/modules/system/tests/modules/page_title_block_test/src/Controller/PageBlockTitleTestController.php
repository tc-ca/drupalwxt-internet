<?php

namespace Drupal\page_title_block_test\Controller;

use Drupal\Component\Utility\Html;

/**
 * A test controller.
 */
class PageBlockTitleTestController {

  /**
   * Returns a page with the page title block embedded.
   */
  public function testPage() {
    /** @var \Drupal\Core\Block\TitleBlockPluginInterface $block */
    $block = \Drupal::service('plugin.manager.block')->createInstance('page_title_block');

    if ($set_title = \Drupal::state()->get('page_title_block_test.set_title')) {
      $block->setTitle($set_title);
    }
    if ($render_array_title = \Drupal::state()->get('page_title_block_test.render_array_title')) {
      $build['#title'] = Html::escape($render_array_title);
    }

    // Nest the block lower than the top-level title.
    $build[] = $block->build();
    return $build;
  }

}
