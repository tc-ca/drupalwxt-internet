<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the Lightning Layout update to install Layout Library.
 *
 * @group lightning_layout
 *
 * @see \lightning_layout_update_8013()
 */
class Update8013Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/Update8013-d8.8.5-lightning_layout-1.x.php.gz',
    ];
  }

  /**
   * Test that the updates install Layout Library.
   */
  public function testUpdate() {
    $this->assertFalse($this->container->get('module_handler')->moduleExists('layout_library'));
    $this->runUpdates();
    $this->assertTrue($this->container->get('module_handler')->moduleExists('layout_library'));
  }

}
