<?php

namespace Drupal\Tests\lightning_scheduler\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\lightning_scheduler\Traits\SchedulerUiTrait;

/**
 * @group lightning
 * @group lightning_workflow
 * @group lightning_scheduler
 */
class UiTest extends WebDriverTestBase {

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
    $this->setTimeStep();
  }

  public function testUiNotPresentWithoutModeration() {
    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $node_type = $this->createContentType()->id();
    $this->drupalGet('/node/add/' . $node_type);
    $this->assertSession()->fieldNotExists('moderation_state[0][state]');
    $this->assertSession()->linkNotExists('Schedule a status change');
  }

  public function testUi() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->createUser([
      'create page content',
      'view own unpublished content',
      'edit own page content',
      'use editorial transition create_new_draft',
      'schedule editorial transition publish',
      'schedule editorial transition archive',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $page->fillField('Title', $this->randomString());

    $this->createTransition('Published', mktime(18, 0, 0, 5, 4, 2038));
    $this->createTransition('Archived', mktime(8, 57, 0, 9, 19, 2038));

    $page->pressButton('Save');
    $assert_session->elementExists('css', 'a[rel="edit-form"]')->click();
    $assert_session->pageTextContains("Change to Published on May 4, 2038 at 6:00 PM");
    $assert_session->pageTextContains("Change to Archived on September 19, 2038 at 8:57 AM");

    $page->clickLink('Remove transition to Archived on September 19, 2038 at 8:57 AM');
    $assert_session->pageTextContains("Change to Published on May 4, 2038 at 6:00 PM");
    $assert_session->pageTextNotContains("Change to Archived on September 19, 2038 at 8:57 AM");
    $assert_session->linkExists('add another');
    $page->pressButton('Save');
    $assert_session->elementExists('css', 'a[rel="edit-form"]')->click();
    $assert_session->pageTextContains("Change to Published on May 4, 2038 at 6:00 PM");
    $assert_session->pageTextNotContains("Change to Archived on September 19, 2038 at 8:57 AM");

    $this->createTransition('Archived', mktime(8, 57, 0, 9, 19, 2038), FALSE);
    $assert_session->linkExists('Cancel transition');
    $assert_session->pageTextContains("Change to Published on May 4, 2038 at 6:00 PM");
    $assert_session->pageTextNotContains("Change to Archived on September 19, 2038 at 8:57 AM");
    $page->pressButton('Save');
    $assert_session->elementExists('css', 'a[rel="edit-form"]')->click();
    $assert_session->pageTextContains("Change to Published on May 4, 2038 at 6:00 PM");
    $assert_session->pageTextNotContains("Change to Archived on September 19, 2038 at 8:57 AM");
    $page->clickLink('Remove transition to Published on May 4, 2038 at 6:00 PM');
    $assert_session->pageTextNotContains("Change to Published on May 4, 2038 at 6:00 PM");
    $assert_session->linkExists('Schedule a status change');
  }

}
