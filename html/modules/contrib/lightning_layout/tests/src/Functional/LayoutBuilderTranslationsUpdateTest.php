<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @group lightning_layout
 */
class LayoutBuilderTranslationsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/2.0.0.php.gz',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // The landing page content type's third-party settings are not relevant to
    // the test, and will fail config schema checks if they are present (since
    // they contain settings for Lightning Workflow).
    $this->config('node.type.landing_page')
      ->set('third_party_settings', [])
      ->save();
  }

  /**
   * Tests that layout_builder_st is not installed if Language is absent.
   */
  public function testWithoutLanguage() {
    $this->runUpdates();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('layout_builder_st'));
  }

  /**
   * Tests that layout_builder_st is installed if Language is present.
   */
  public function testWithLanguage() {
    $this->container->get('module_installer')->install(['language']);
    // Reset the container to account for changes made by installing Language.
    $this->resetAll();

    $this->runUpdates();
    $this->assertTrue($this->container->get('module_handler')->moduleExists('layout_builder_st'));
  }

}
