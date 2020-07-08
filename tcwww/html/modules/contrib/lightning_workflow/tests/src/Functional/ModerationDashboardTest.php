<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Lightning Workflow's integration with Moderation Dashboard.
 *
 * @group lightning_workflow
 */
class ModerationDashboardTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_workflow',
    'moderation_dashboard',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // At least one moderated content type must exist in order for the dashboard
    // to be available.
    $this->drupalCreateContentType([
      'third_party_settings' => [
        'lightning_workflow' => [
          'workflow' => 'editorial',
        ],
      ],
    ]);
  }

  /**
   * Tests basic functionality of Moderation Dashboard.
   */
  public function testModerationDashboard() {
    $this->drupalPlaceBlock('local_tasks_block');

    $account = $this->drupalCreateUser([
      'use moderation dashboard',
      'view all revisions',
    ]);
    $this->drupalLogin($account);

    $this->getSession()->getPage()->clickLink('Moderation Dashboard');
    $this->assertBlock('views_block:content_moderation_dashboard_in_review-block_1');
    $this->assertBlock('views_block:content_moderation_dashboard_in_review-block_2');
    $this->assertBlock('moderation_dashboard_activity');
    $this->assertBlock('views_block:moderation_dashboard_recently_created-block_1');
    $this->assertBlock('views_block:content_moderation_dashboard_in_review-block_3');
    $this->assertBlock('views_block:moderation_dashboard_recent_changes-block_1');
    $this->assertBlock('views_block:moderation_dashboard_recent_changes-block_2');
    $this->assertBlock('views_block:moderation_dashboard_recently_created-block_2');
  }

  /**
   * Asserts the presence of a particular block by its plugin ID.
   *
   * @param string $plugin_id
   *   The block plugin ID.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The block element.
   */
  private function assertBlock($plugin_id) {
    return $this->assertSession()
      ->elementExists('css', '[data-block-plugin-id="' . $plugin_id . '"]');
  }

}
