<?php

namespace Drupal\Tests\lightning_layout\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Lightning Layout's implementation of hook_layout_alter().
 *
 * @group panels
 *
 * @todo Remove when we require Panels 4.5 or later, since it implements and
 * tests this functionality itself.
 */
class LayoutAlterHookTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_discovery',
    'lightning_layout',
    'panels',
  ];

  /**
   * Tests that Panels correctly modifies layout icons.
   */
  public function testIconPath() {
    /** @var \Drupal\Core\Layout\LayoutDefinition $layout */
    $layout = $this->container->get('plugin.manager.core.layout')
      ->getDefinition('layout_onecol');

    $this->assertEmpty($layout->getIconPath());
  }

}
