<?php

namespace Drupal\Tests\panelizer\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the updating of Layout IDs.
 *
 * @group panelizer
 */
class PanelizerLayoutIDUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.panelizer.minimal.php.gz',
    ];
  }

  /**
   * Test updates.
   */
  public function testUpdate() {
    $module_handler = $this->container->get('module_handler');
    $this->assertFalse($module_handler->moduleExists('layout_builder'));
    $this->assertFalse($module_handler->moduleExists('layout_library'));

    $this->runUpdates();

    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('layout_builder'));
    $this->assertTrue($module_handler->moduleExists('layout_library'));

    $this->drupalLogin($this->rootUser);
    // Defaults are not editable in Panelizer 5.x.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->clickLink('Edit', 1);
    $this->assertResponse(403);

    $this->drupalGet('node/1');
    $this->assertResponse(200);

    $this->drupalGet('node/2');
    $this->assertResponse(200);
  }

}
