<?php

namespace Drupal\Tests\lightning_scheduler\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\lightning_scheduler\Traits\SchedulerUiTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * @group lightning
 * @group lightning_workflow
 * @group lightning_scheduler
 */
class TransitionTest extends WebDriverTestBase {

  use CronRunTrait;
  use SchedulerUiTrait;

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

  public function testPublishInPast() {
    $assert_session = $this->assertSession();

    $this->createTransition('Published', time() - 10);
    $this->getSession()->getPage()->pressButton('Save');
    $this->cronRun();
    $this->clickEditLink();
    $assert_session->pageTextContains('Current state Published');
    $assert_session->elementNotExists('css', '.scheduled-transition');
  }

  /**
   * @depends testPublishInPast
   */
  public function testSkipInvalidTransition() {
    $assert_session = $this->assertSession();
    $now = time();

    $this->createTransition('Published', $now - 20);
    $this->createTransition('Archived', $now - 10);
    $this->getSession()->getPage()->pressButton('Save');
    $this->cronRun();
    $this->clickEditLink();
    // It will still be in the draft state because the transition should resolve
    // to Draft -> Archived, which doesn't exist.
    $assert_session->pageTextContains('Current state Draft');
    $assert_session->elementNotExists('css', '.scheduled-transition');
  }

  public function testClearCompletedTransitions() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $now = time();

    $page->selectFieldOption('moderation_state[0][state]', 'In review');
    $page->pressButton('Save');
    $this->clickEditLink();
    $this->createTransition('Published', $now + 8);
    $page->pressButton('Save');
    $this->setRequestTime($now + 10);
    $this->cronRun();
    $this->clickEditLink();
    $page->selectFieldOption('moderation_state[0][state]', 'Archived');
    $page->pressButton('Save');
    $this->cronRun();
    $this->clickEditLink();
    $assert_session->pageTextContains('Current state Archived');
  }

  public function testPublishPendingRevision() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $now = time();

    $this->container->get('module_installer')->install(['views']);

    $page->selectFieldOption('moderation_state[0][state]', 'Published');
    $page->clickLink('Promotion options');
    $page->checkField('Promoted to front page');
    $page->pressButton('Save');
    $this->clickEditLink();
    $page->fillField('Title', 'MC Hammer');
    $page->selectFieldOption('moderation_state[0][state]', 'Draft');
    $this->createTransition('Published', $now + 8);
    $page->pressButton('Save');
    $this->setRequestTime($now + 10);
    $this->cronRun();
    $this->drupalGet('/node');
    $assert_session->linkExists('MC Hammer');
  }

}
