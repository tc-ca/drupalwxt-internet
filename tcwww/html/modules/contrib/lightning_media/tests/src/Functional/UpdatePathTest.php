<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests Lightning Media's database update path.
 *
 * @group lightning_media
 * @group lightning
 */
class UpdatePathTest extends UpdatePathTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/1.0.0-rc2.php.gz',
    ];
  }

  /**
   * Tests Lightning Media's database update path.
   */
  public function testUpdatePath() {
    $this->getRandomGenerator()
      ->image('public://star_0.png', '16x16', '16x16');

    $this->container->get('plugin.cache_clearer')->clearCachedDefinitions();
    $this->runUpdates();
    $this->drush('update:lightning', [], ['yes' => NULL]);
  }

}
