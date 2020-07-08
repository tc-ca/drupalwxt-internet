<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests lightning_workflow_update_8006().
 *
 * @group lightning_workflow
 */
class Update8006Test extends UpdatePathTestBase {

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
    $this->databaseDumpFiles[] = __DIR__ . '/../../fixtures/Update8006Test.php.gz';
  }

  /**
   * Tests lightning_workflow_update_8006().
   */
  public function test() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $storage->load('moderation_history');
    $this->assertInstanceOf(View::class, $view);
    $display = $view->getDisplay('default');
    $this->assertArrayHasKey('moderation_state', $display['display_options']['relationships']);
    $field = $display['display_options']['fields']['moderation_state'];
    $this->assertSame('content_moderation_state_field_revision', $field['table']);
    $this->assertSame('moderation_state', $field['relationship']);
    $this->assertSame('content_moderation_state', $field['entity_type']);
    $this->assertSame('moderation_state', $field['entity_field']);

    $this->runUpdates();

    $storage->resetCache(['moderation_history']);
    $view = $storage->load('moderation_history');
    $this->assertInstanceOf(View::class, $view);
    $display = $view->getDisplay('default');
    $this->assertArrayNotHasKey('moderation_state', $display['display_options']['relationships']);
    $field = $display['display_options']['fields']['moderation_state'];
    $this->assertSame('node_field_revision', $field['table']);
    $this->assertSame('none', $field['relationship']);
    $this->assertSame('node', $field['entity_type']);
    $this->assertArrayNotHasKey('entity_field', $field);
  }

}
