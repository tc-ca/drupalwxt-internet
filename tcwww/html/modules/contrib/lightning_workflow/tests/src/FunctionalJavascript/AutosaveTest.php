<?php

namespace Drupal\Tests\lightning_workflow\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Lightning Workflow's integration with Autosave Form.
 *
 * @group lightning_workflow
 */
class AutosaveTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'autosave_form',
    'lightning_workflow',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'moderated',
      'third_party_settings' => [
        'lightning_workflow' => [
          'autosave' => TRUE,
          'workflow' => 'editorial',
        ],
      ],
    ]);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests that work in progress is autosaved and can be restored.
   */
  public function testAutosaveIntegration() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access content overview',
      'edit any moderated content',
      'use editorial transition create_new_draft',
    ]);
    $this->drupalLogin($account);

    $node = $this->drupalCreateNode([
      'type' => 'moderated',
      'moderation_state' => 'published',
    ]);
    $this->drupalGet('/admin/content');
    $page->clickLink($node->getTitle());
    $assert_session->elementExists('named', ['link', 'edit-form'])->click();

    // Wait for an initial autosave before making any changes.
    $this->waitForAutosave();

    $page->fillField('Title', 'Testing');
    $this->waitForAutosave();
    $page->clickLink('View');
    $assert_session->elementExists('named', ['link', 'edit-form'])->click();
    $button = $assert_session->waitForButton('Resume editing');
    $this->assertNotEmpty($button);
    $button->press();
    $assert_session->fieldValueEquals('Title', 'Testing');
  }

  /**
   * Waits for the current form to be autosaved.
   */
  private function waitForAutosave() {
    $element = $this->assertSession()
      ->elementExists('css', '#autosave-notification');

    $is_visible = $element->waitFor(20, function (NodeElement $element) {
      return $element->isVisible();
    });
    $this->assertTrue($is_visible);

    $is_hidden = $element->waitFor(10, function (NodeElement $element) {
      return $element->isVisible() === FALSE;
    });
    $this->assertTrue($is_hidden);
  }

}
