<?php

namespace Drupal\Tests\lightning_layout\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lightning_layout
 */
class HooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_layout',
  ];

  public function testBlockAlter() {
    $blocks = [
      'entity_block:node:uid' => [],
    ];
    lightning_layout_block_alter($blocks);
  }

}
