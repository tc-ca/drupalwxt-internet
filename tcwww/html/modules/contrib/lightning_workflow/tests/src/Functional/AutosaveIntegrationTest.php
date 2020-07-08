<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests integration with autosave_form.
 *
 * @group lightning_workflow
 */
class AutosaveIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_builder', 'lightning_workflow'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Creates the content types needed for testing.
   */
  private function createContentTypes() {
    // Explicit opt-out.
    $this->drupalCreateContentType([
      'type' => 'alpha',
      'third_party_settings' => [
        'lightning_workflow' => [
          'autosave' => FALSE,
        ],
      ],
    ]);

    // Implicit opt-in.
    $this->drupalCreateContentType([
      'type' => 'beta',
    ]);

    // Explicit opt-in.
    $this->drupalCreateContentType([
      'type' => 'charlie',
      'third_party_settings' => [
        'lightning_workflow' => [
          'autosave' => TRUE,
        ],
      ],
    ]);
  }

  /**
   * Asserts that autosave_form is configured as expected.
   */
  private function assertExpectedConfig() {
    $config = $this->config('autosave_form.settings');

    $this->assertSame(20000, $config->get('interval'));

    $node_types = $config->get('allowed_content_entity_types.node.bundles');
    $this->assertArrayNotHasKey('alpha', $node_types);
    $this->assertSame('beta', $node_types['beta']);
    $this->assertSame('charlie', $node_types['charlie']);
  }

  /**
   * Tests that autosave_form integrates with new content types.
   */
  public function testNewNodeTypeIntegration() {
    $this->container->get('module_installer')->install(['autosave_form']);
    $this->createContentTypes();
    $this->assertExpectedConfig();
  }

  /**
   * Tests that autosave_form integrates with pre-existing content types.
   */
  public function testPreExistingNodeTypeIntegration() {
    $this->createContentTypes();
    $this->container->get('module_installer')->install(['autosave_form']);
    $this->assertExpectedConfig();
  }

  /**
   * Tests that autosave_form is disabled in the Layout Builder UI.
   */
  public function testAutosaveDisabledInLayoutBuilder() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->testPreExistingNodeTypeIntegration();

    // Enable Layout Builder for the content type.
    LayoutBuilderEntityViewDisplay::load('node.charlie.default')
      ->enableLayoutBuilder()
      ->setOverridable(TRUE)
      ->save();

    $this->drupalLogin($this->rootUser);

    $node = $this->drupalCreateNode([
      'type' => 'charlie',
    ]);
    $this->drupalGet($node->toUrl('edit-form'));
    $assert_session->statusCodeEquals(200);
    $this->assertArrayHasKey('autosaveForm', $this->getJsSettings());

    $page->clickLink('Layout');
    $assert_session->statusCodeEquals(200);
    $this->assertArrayNotHasKey('autosaveForm', $this->getJsSettings());
  }

  /**
   * Returns the JavaScript drupalSettings object.
   *
   * @return array
   *   The decoded array of JavaScript settings.
   */
  private function getJsSettings() {
    $settings = $this->assertSession()
      ->elementExists('css', '[data-drupal-selector="drupal-settings-json"]')
      ->getText();

    return Json::decode($settings);
  }

}
