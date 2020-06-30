<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the moderation_history view.
 *
 * @group lightning_workflow
 */
class ModerationHistoryTest extends BrowserTestBase {

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
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the moderation_history view for a node with revisions.
   */
  public function testModerationHistory() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Create a content type with moderation enabled.
    $node_type = $this->createContentType([
      'third_party_settings' => [
        'lightning_workflow' => [
          'workflow' => 'editorial',
        ],
      ],
    ]);

    $user_permissions = [
      'administer nodes',
      'bypass node access',
      'use editorial transition create_new_draft',
      'use editorial transition review',
      'use editorial transition publish',
      'view all revisions',
    ];
    $user_a = $this->createUser($user_permissions, 'userA');
    $user_b = $this->createUser($user_permissions, 'userB');

    $node = $this->createNode([
      'type' => $node_type->id(),
      'title' => 'Foo',
      'moderation_state' => 'draft',
    ]);

    $timestamp_a = time();
    $timestamp_b = $timestamp_a + 10;

    // Make two revisions with two different users.
    $this->drupalLogin($user_a);
    $this->drupalGet($node->toUrl('edit-form'));
    $page->selectFieldOption('moderation_state[0][state]', 'review');
    $page->fillField('Date', date('Y-m-d', $timestamp_a));
    $page->fillField('Time', date('H:i:s', $timestamp_a));
    $page->pressButton('Save');
    $this->drupalLogout();

    $this->drupalLogin($user_b);
    $this->drupalGet($node->toUrl('edit-form'));
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('Date', date('Y-m-d', $timestamp_b));
    $page->fillField('Time', date('H:i:s', $timestamp_b));
    $page->pressButton('Save');

    $page->clickLink('History');
    $assert_session->addressEquals('/node/' . $node->id() . '/moderation-history');
    $assert_session->statusCodeEquals(200);
    $date_formatter = $this->container->get('date.formatter');
    $assert_session->pageTextContainsOnce('Set to In review on ' . $date_formatter->format($timestamp_a, 'long') . ' by ' . $user_a->getAccountName());
    $assert_session->pageTextContainsOnce('Set to Published on ' . $date_formatter->format($timestamp_b, 'long') . ' by ' . $user_b->getAccountName());
  }

}
