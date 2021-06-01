<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\NodeType;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests Lightning Layout's database update path.
 *
 * @group lightning_layout
 * @group lightning
 */
class UpdatePathTest extends UpdatePathTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/drupal-8.8.0-update-from-2.0.0-beta1.php.gz',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    NodeType::load('landing_page')
      ->unsetThirdPartySetting('lightning_workflow', 'workflow')
      ->save();

    $node_type = NodeType::load('page');
    if ($node_type) {
      $node_type->delete();
    }
  }

  /**
   * Tests Lightning Layout's database update path.
   */
  public function testUpdatePath() {
    $this->runUpdates();
    $this->drush('update:lightning', [], ['yes' => NULL]);
  }

}
