<?php

namespace Drupal\Tests\lightning_api\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the Lightning API update to uninstall Simple OAuth Extras.
 *
 * @group lightning_api
 *
 * @see \lightning_api_update_8401()
 */
class Update8401Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
      __DIR__ . '/../../fixtures/4.4.0-drupal-8.8.0.bare.standard.update-8401.php.gz',
    ];
  }

  /**
   * Tests that the update uninstalls simple_oauth_extras.
   */
  public function testUpdate() {
    // openapi_redoc is long gone, so prevent the update system from complaining
    // about that.
    $this->container->get('keyvalue')
      ->get('system.schema')
      ->delete('openapi_redoc');

    $this->assertTrue($this->container->get('module_handler')->moduleExists('simple_oauth_extras'));
    $this->runUpdates();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('simple_oauth_extras'));
  }

}
