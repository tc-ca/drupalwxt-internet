<?php

namespace Drupal\Tests\lightning_scheduler\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\lightning_scheduler\Traits\SchedulerUiTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * @group lightning_workflow
 * @group lightning_scheduler
 * @group orca_public
 */
class ScheduledTransitionTest extends BrowserTestBase {

  use CronRunTrait;
  use SchedulerUiTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_scheduler',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // The editorial workflow is packaged with Lightning Workflow, so install
    // its config but don't actually enable it since it is not a dependency.
    $this->container->get('config.installer')
      ->installDefaultConfig('module', 'lightning_workflow');

    $this->drupalCreateContentType(['type' => 'page']);

    // Due to a known core bug, rebuilding the node access table will break the
    // entity query in TransitionManager::getTransitionable(). The workaround is
    // for the query to specifically disable access checking, which it should be
    // doing anyway because transitions need to be processed irrespective of
    // user access.
    // @see https://www.drupal.org/project/drupal/issues/2823957
    node_access_rebuild();

    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $plugin */
    $plugin = $workflow->getTypePlugin();
    $plugin->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    $account = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'view own unpublished content',
      'view latest version',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archive',
      'schedule editorial transition publish',
      'schedule editorial transition archive',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Data provider for testSingleTransition().
   *
   * @return array
   *   The scenarios to test.
   */
  public function providerSingleTransition() {
    return [
      'draft to published in future' => [
        10,
        'published',
        'Published',
      ],
      'draft to archived in future' => [
        10,
        'archived',
        'Draft',
      ],
      'draft to published in past' => [
        -10,
        'published',
        'Published',
      ],
      'draft to archived in past' => [
        -10,
        'archived',
        'Draft',
      ],
    ];
  }

  /**
   * Tests a single scheduled workflow state transition.
   *
   * @param int $offset
   *   How many seconds in the past or future to schedule the transition. If
   *   negative, the transition will be in the past.
   * @param string $to_state
   *   The workflow state to transition to.
   * @param string $expected_state_label
   *   The label of the workflow state that is expected after the transition is
   *   executed.
   *
   * @dataProvider providerSingleTransition
   */
  public function testSingleTransition($offset, $to_state, $expected_state_label) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $now = time();

    $this->drupalGet('/node/add/page');
    $assert_session->statusCodeEquals(200);

    $page->fillField('Title', 'Schedule This');
    $this->setTransitionData('moderation_state[0][scheduled_transitions][data]', [
      [
        'when' => $now + $offset,
        'state' => $to_state,
      ],
    ]);
    $page->pressButton('Save');

    $assert_session->addressMatches('/^\/node\/[0-9]+$/');
    $edit_url = $this->getUrl() . '/edit';

    if ($offset > 0) {
      $this->setRequestTime($now + $offset + 2);
    }
    $this->cronRun();
    $this->drupalGet($edit_url);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains("Current state $expected_state_label");
  }

  /**
   * Tests scheduling a series of valid transitions in the future.
   */
  public function testFutureSequence() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $now = time();

    $this->drupalGet('/node/add/page');
    $assert_session->statusCodeEquals(200);

    $page->fillField('Title', 'Schedule This');
    $this->setTransitionData('moderation_state[0][scheduled_transitions][data]', [
      [
        'when' => $now + 10,
        'state' => 'published',
      ],
      [
        'when' => $now + 20,
        'state' => 'archived',
      ],
    ]);
    $page->pressButton('Save');

    $assert_session->addressMatches('/^\/node\/[0-9]+$/');
    $edit_url = $this->getUrl() . '/edit';

    $this->setRequestTime($now + 12);
    $this->cronRun();
    $this->setRequestTime($now + 24);
    $this->cronRun();

    $this->drupalGet($edit_url);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains("Current state Archived");
  }

  /**
   * Tests that a sequence that works in the future may not work in the past.
   *
   * @depends testFutureSequence
   */
  public function testInvalidPastSequence() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('/node/add/page');
    $assert_session->statusCodeEquals(200);

    $page->fillField('Title', 'Schedule This');
    $this->setTransitionData('moderation_state[0][scheduled_transitions][data]', [
      [
        'when' => time() - 20,
        'state' => 'published',
      ],
      [
        'when' => time() - 10,
        'state' => 'archived',
      ],
    ]);
    $page->pressButton('Save');

    $assert_session->addressMatches('/^\/node\/[0-9]+$/');
    $edit_url = $this->getUrl() . '/edit';

    $this->cronRun();
    $this->drupalGet($edit_url);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains("Current state Draft");
  }

  /**
   * Tests that scheduled transitions work correctly with pending revisions.
   *
   * @depends testSingleTransition
   */
  public function testSingleTransitionWithPendingRevision() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $now = time();

    $this->drupalGet('/node/add/page');
    $assert_session->statusCodeEquals(200);
    $page->fillField('Title', 'Schedule This');
    $page->selectFieldOption('moderation_state[0][state]', 'Published');
    $page->pressButton('Save');

    $assert_session->addressMatches('/^\/node\/[0-9]+$/');
    $edit_url = $this->getUrl() . '/edit';
    $this->drupalGet($edit_url);

    $page->fillField('Title', 'MC Hammer');
    $page->selectFieldOption('moderation_state[0][state]', 'Draft');

    $this->setTransitionData('moderation_state[0][scheduled_transitions][data]', [
      [
        'when' => $now + 10,
        'state' => 'published',
      ],
    ]);
    $page->pressButton('Save');

    $this->setRequestTime($now + 12);
    $this->cronRun();
    $this->drupalGet($edit_url);
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('Title', 'MC Hammer');
  }

}
