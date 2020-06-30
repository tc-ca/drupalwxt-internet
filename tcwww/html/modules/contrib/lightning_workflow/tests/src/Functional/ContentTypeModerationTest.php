<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests Lightning Workflow's default handling of moderated content types.
 *
 * @group lightning_workflow
 */
class ContentTypeModerationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_roles',
    'lightning_workflow',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');

    // Allow the content view to filter by moderation state. This is done when
    // the view is first created, so we need to create a duplicate of the
    // content view in order for this to work.
    // @see lightning_workflow_view_presave()
    $original_view = View::load('content');
    $duplicate_view = $original_view->createDuplicate()->set('id', 'content');
    $original_view->delete();
    $duplicate_view->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Create a content type with moderation applied.
    $this->drupalCreateContentType([
      'type' => 'test',
      'third_party_settings' => [
        'lightning_workflow' => [
          'workflow' => 'editorial',
        ],
      ],
    ]);
  }

  /**
   * Tests adding a new content type to a workflow.
   */
  public function testSetWorkflowOnContentTypeCreation() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/structure/types/add');
    $page->fillField('Name', 'Jucketry');
    $page->fillField('type', 'jucketry');
    $assert_session->fieldValueEquals('workflow', 'Editorial');
    $assert_session->optionExists('workflow', '- None -');
    $page->pressButton('Save content type');
    $assert_session->pageTextContains('The content type Jucketry has been added.');

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $plugin */
    $plugin = $workflow->getTypePlugin();
    $this->assertTrue($plugin->appliesToEntityTypeAndBundle('node', 'jucketry'));
  }

  /**
   * Tests access to unpublished content.
   */
  public function testUnpublishedAccess() {
    $assert_session = $this->assertSession();

    $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Moderation Test 1',
      'promote' => TRUE,
      'moderation_state' => 'review',
    ]);
    $this->drupalGet('<front>');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('Moderation Test 1');

    $account = $this->drupalCreateUser([
      'access content overview',
      'view any unpublished content',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $this->getSession()->getPage()->clickLink('Moderation Test 1');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests that reviewers can access unpublished (draft) revisions.
   */
  public function testReviewerAccess() {
    $assert_session = $this->assertSession();

    $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Version 1',
      'moderation_state' => 'draft',
    ]);

    $account = $this->drupalCreateUser();
    $account->addRole('test_reviewer');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $this->getSession()->getPage()->clickLink('Version 1');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Version 1');
  }

  /**
   * Tests that reviewers can access the latest unpublished revision.
   *
   * @depends testReviewerAccess
   */
  public function testLatestUnpublishedRevisionReviewerAccess() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Version 1',
      'moderation_state' => 'draft',
    ]);

    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $page->clickLink('Version 1');
    $this->clickEditLink();
    $page->fillField('Title', 'Version 2');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save');
    $this->clickEditLink();
    $page->fillField('Title', 'Version 3');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save');

    $this->drupalLogout();
    $account = $this->drupalCreateUser();
    $account->addRole('test_reviewer');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $page->clickLink('Version 2');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Latest version');
  }

  /**
   * Tests that unmoderated content types have a "create new revision" checkbox.
   */
  public function testCreateNewRevisionCheckbox() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->drupalCreateNode([
      'type' => $this->createContentType()->id(),
      'title' => 'Deft Zebra',
    ]);
    $this->drupalGet('/admin/content');
    $this->getSession()->getPage()->clickLink('Deft Zebra');
    $this->clickEditLink();
    $assert_session->fieldExists('Create new revision');
  }

  /**
   * Tests that moderated content does not provide publish/unpublish buttons.
   */
  public function testEnableModerationForContentType() {
    $assert_session = $this->assertSession();

    $node_type = $this->createContentType()->id();

    $account = $this->drupalCreateUser([
      'administer nodes',
      "create $node_type content",
    ]);
    $this->drupalLogin($account);

    $this->drupalGet("/node/add/$node_type");
    $assert_session->buttonExists('Save');
    $assert_session->checkboxChecked('Published');
    $assert_session->buttonNotExists('Save and publish');
    $assert_session->buttonNotExists('Save as unpublished');

    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $workflow_type */
    $workflow_type = $workflow->getTypePlugin();
    $workflow_type->addEntityTypeAndBundle('node', $node_type);
    $workflow->save();

    $this->getSession()->reload();
    $assert_session->buttonExists('Save');
    $assert_session->fieldNotExists('status[value]');
    $assert_session->buttonNotExists('Save and publish');
    $assert_session->buttonNotExists('Save as unpublished');
  }

  /**
   * Tests that moderated content does not have publish/unpublish actions.
   *
   * @depends testEnableModerationForContentType
   */
  public function testContentOverviewActions() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Foo',
      'moderation_state' => 'draft',
    ]);
    $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Bar',
      'moderation_state' => 'draft',
    ]);
    $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Baz',
      'moderation_state' => 'draft',
    ]);

    $this->drupalGet('/admin/content');
    $page->selectFieldOption('moderation_state', 'Draft');

    $assert_session->elementExists('css', '.views-exposed-form .form-actions input[type = "submit"]')
      ->press();

    $assert_session->optionNotExists('Action', 'node_publish_action');
    $assert_session->optionNotExists('Action', 'node_unpublish_action');
  }

  /**
   * Clicks the "Edit" link on a canonical node page.
   */
  private function clickEditLink() {
    $this->assertSession()->elementExists('css', 'a[rel="edit-form"]')->click();
  }

}
