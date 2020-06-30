<?php

namespace Drupal\Tests\lightning_api\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests very basic administrator-facing functionality of Lightning API.
 *
 * @group lightning_api
 * @group orca_public
 */
class BasicUsageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_api'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->config('lightning_api.settings')
      ->set('entity_json', TRUE)
      ->set('bundle_docs', TRUE)
      ->save();

    $this->drupalCreateContentType(['type' => 'test']);
    $this->drupalCreateNode(['type' => 'test']);

    $this->container->get('entity_type.bundle.info')->clearCachedBundles();
  }

  /**
   * Tests API documentation and JSON representations are exposed for entities.
   */
  public function testBasicUsage() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $page->clickLink('View JSON');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/admin/structure/types');
    $this->clickLink('View JSON');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/api-docs');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/admin/structure/types');
    $this->clickLink('View API documentation');
    $assert_session->statusCodeEquals(200);
  }

}
