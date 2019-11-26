<?php

namespace Drupal\Tests\lightning_workflow\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Lightning Workflow's integration with Moderation Sidebar.
 *
 * @group lightning_workflow
 */
class ModerationSidebarTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_workflow',
    'moderation_sidebar',
    'toolbar',
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
          'workflow' => 'editorial',
        ],
      ],
    ]);
  }

  /**
   * Tests basic Moderation Sidebar functionality.
   */
  public function testModerationSidebar() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access content overview',
      'access toolbar',
      'edit any moderated content',
      'use editorial transition publish',
      'use editorial transition review',
      'use moderation sidebar',
      'view any unpublished content',
    ]);
    $this->drupalLogin($account);

    $title = $this->drupalCreateNode(['type' => 'moderated'])->getTitle();

    $this->drupalGet('/admin/content');
    $page->clickLink($title);

    $toolbar = $assert_session->elementExists('css', '#toolbar-bar');
    $toolbar->clickLink('Tasks');

    $sidebar = $assert_session->waitForElement('css', '.moderation-sidebar-container');
    $this->assertNotEmpty($sidebar);

    $page->pressButton('Publish');
    $assert_session->pageTextContains('The moderation state has been updated.');
    $this->assertSame('Published', $assert_session->elementExists('named', ['link', 'Tasks'], $toolbar)->getAttribute('data-label'));
  }

}
