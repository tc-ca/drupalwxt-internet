<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Layout Builder integration with Lightning's bundled content types.
 *
 * @group lightning_layout
 * @group orca_public
 */
class LayoutBuilderIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_landing_page',
    'lightning_page',
    'lightning_roles',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests Layout Builder integration with Lightning's bundled content types.
   */
  public function testLayoutBuilderIntegration() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser();
    $account->addRole('landing_page_creator');
    $account->addRole('page_creator');
    $account->save();
    $this->drupalLogin($account);

    $node = $this->drupalCreateNode(['type' => 'landing_page']);
    $this->drupalGet($node->toUrl());
    $assert_session->elementExists('named', ['link', 'Layout']);

    $this->drupalGet('/node/add/page');
    $page->fillField('Title', "Aesop's Fables");
    $page->fillField('Body', 'Misery loves company.');
    $page->selectFieldOption('Layout', 'Two-column');
    $page->pressButton('Save');
    $assert_session->elementExists('css', '.layout--twocol-section');
  }

}
