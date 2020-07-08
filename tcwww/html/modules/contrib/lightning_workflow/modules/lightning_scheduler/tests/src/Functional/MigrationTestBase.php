<?php

namespace Drupal\Tests\lightning_scheduler\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Base class for testing migration of old Lightning Scheduler data.
 */
abstract class MigrationTestBase extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [];

    $fixture = $this->getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz';

    // If we're on Drupal 8.8 or later, use its base fixture. Otherwise, use the
    // older 8.4 base fixture included with versions of core before 8.8.
    if (file_exists($fixture)) {
      $this->databaseDumpFiles[] = $fixture;
    }
    else {
      $this->databaseDumpFiles[] = str_replace('8.8.0', '8.4.0', $fixture);
    }
    $this->databaseDumpFiles[] = __DIR__ . '/../../fixtures/BaseFieldMigrationTest.php.gz';
  }

  /**
   * Runs a basic test of migrating old Lightning Scheduler data.
   *
   * This doesn't really test that data integrity is preserved, so subclasses
   * should override this method and call it before asserting other things.
   */
  public function test() {
    $this->runUpdates();

    $migrations = $this->container->get('state')->get('lightning_scheduler.migrations');
    $this->assertCount(2, $migrations);
    $this->assertContains('block_content', $migrations);
    $this->assertContains('node', $migrations);

    $assert = $this->assertSession();
    $url = $assert->elementExists('named', ['link', 'migrate your existing content'])->getAttribute('href');

    $this->drupalLogin($this->rootUser);
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Migrate scheduled transitions');
    $assert->elementExists('named', ['link', 'switch to maintenance mode']);
  }

  /**
   * Runs post-migration assertions for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The storage handler for the entity type.
   */
  protected function postMigration($entity_type_id) {
    // Now that a migration is completed, old base fields will no longer be
    // defined. Therefore, we need to clear the entity field cache in order to
    // properly load the changed content, and there should be pending entity
    // definition updates (the old base fields need to be uninstalled).
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $this->assertTrue(
      $this->container->get('entity.definition_update_manager')->needsUpdates()
    );

    return $this->container->get('entity_type.manager')->getStorage($entity_type_id);
  }

}
