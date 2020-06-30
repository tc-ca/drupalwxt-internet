<?php

namespace Drupal\Tests\lightning_scheduler\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\lightning_scheduler\Traits\SchedulerUiTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests Lightning Scheduler's transition handling.
 *
 * @group lightning
 * @group lightning_workflow
 * @group lightning_scheduler
 */
class TransitionTest extends WebDriverTestBase {

  use CronRunTrait {
    cronRun as traitCronRun;
  }
  use SchedulerUiTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_page',
    'lightning_scheduler',
    'lightning_workflow',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');

    $this->setUpTimeZone();

    $account = $this->createUser([
      'create page content',
      'view own unpublished content',
      'edit own page content',
      'use editorial transition create_new_draft',
      'use editorial transition review',
      'use editorial transition publish',
      'use editorial transition archive',
      'schedule editorial transition publish',
      'schedule editorial transition archive',
      'view latest version',
      'administer nodes',
    ]);
    $this->drupalLogin($account);
    $this->setTimeStep();

    $this->drupalGet('/node/add/page');
    $this->getSession()->getPage()->fillField('Title', $this->randomString());
  }

  /**
   * Tests automatically publishing a transition scheduled in the past.
   */
  public function testPublishInPast() {
    $assert_session = $this->assertSession();

    $this->createTransition('Published', time() - 10);
    $this->getSession()->getPage()->pressButton('Save');
    $this->cronRun();
    $this->drupalGet('/node/1/edit');
    $assert_session->pageTextContains('Current state Published');
    $assert_session->elementNotExists('css', '.scheduled-transition');
  }

  /**
   * Tests that invalid transitions are skipped at processing time.
   *
   * @depends testPublishInPast
   */
  public function testSkipInvalidTransition() {
    $assert_session = $this->assertSession();
    $now = time();

    $this->createTransition('Published', $now - 20);
    $this->createTransition('Archived', $now - 10);
    $this->getSession()->getPage()->pressButton('Save');
    $this->cronRun();
    $this->drupalGet('/node/1/edit');
    // It will still be in the draft state because the transition should resolve
    // to Draft -> Archived, which doesn't exist.
    $assert_session->pageTextContains('Current state Draft');
    $assert_session->elementNotExists('css', '.scheduled-transition');
  }

  /**
   * Tests that completed transitions are deleted.
   */
  public function testClearCompletedTransitions() {
    $page = $this->getSession()->getPage();
    $now = time();

    $page->selectFieldOption('moderation_state[0][state]', 'In review');
    $page->pressButton('Save');
    $this->drupalGet('/node/1/edit');
    $this->createTransition('Published', $now + 8);
    $page->pressButton('Save');
    $this->setRequestTime($now + 10);
    $this->cronRun();
    $this->drupalGet('/node/1/edit');
    $page->selectFieldOption('moderation_state[0][state]', 'Archived');
    $page->pressButton('Save');
    $this->cronRun();
    $this->drupalGet('/node/1/edit');
    $this->assertSession()->pageTextContains('Current state Archived');
  }

  /**
   * Tests automatically publishing a pending revision.
   */
  public function testPublishPendingRevision() {
    $page = $this->getSession()->getPage();
    $now = time();

    $this->container->get('module_installer')->install(['views']);

    $page->selectFieldOption('moderation_state[0][state]', 'Published');
    $page->clickLink('Promotion options');
    $page->checkField('Promoted to front page');
    $page->pressButton('Save');
    $this->drupalGet('/node/1/edit');
    $page->fillField('Title', 'MC Hammer');
    $page->selectFieldOption('moderation_state[0][state]', 'Draft');
    $this->createTransition('Published', $now + 8);
    $page->pressButton('Save');
    $this->setRequestTime($now + 10);
    $this->cronRun();
    $this->drupalGet('/node');
    $this->assertSession()->linkExists('MC Hammer');
  }

  /**
   * Tests automatically publishing, and then unpublishing, in the future.
   */
  public function testScheduledPublishAndUnpublishInFuture() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'edit own page content',
      'schedule editorial transition archive',
      'schedule editorial transition publish',
      'use editorial transition archive',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition review',
      'view latest version',
      'view own unpublished content',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $page->fillField('Title', 'Schedule This');

    $now = time();
    $this->createTransition('Published', $now + 10);
    $this->createTransition('Archived', $now + 20);
    $page->pressButton('Save');

    $this->cronRun($now + 12);
    $this->cronRun($now + 22);

    $this->drupalGet('/node/1/edit');
    $assert_session->pageTextContains('Current state Archived');
    $assert_session->elementNotExists('css', '.scheduled-transition');
  }

  /**
   * Runs cron, forcing Drupal to use a particular request time.
   *
   * @param int $time
   *   The request time at which cron will think it is being run.
   */
  protected function cronRun($time = NULL) {
    if (isset($time)) {
      $this->container->get('state')
        ->set('lightning_scheduler.request_time', $time);
    }
    $this->traitCronRun();
  }

}
