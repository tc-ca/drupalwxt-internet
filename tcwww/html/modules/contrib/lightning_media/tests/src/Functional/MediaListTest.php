<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the administrative media list.
 *
 * @group lightning_media
 */
class MediaListTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_media_instagram',
    'lightning_media_twitter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the administrative media list page.
   */
  public function testMediaList() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access media overview',
      'delete any media',
    ]);
    $this->drupalLogin($account);

    /** @var \Drupal\media\MediaInterface $media */
    Media::create(['bundle' => 'tweet'])
      ->setName("I'm a tweet")
      ->set('embed_code', 'https://twitter.com/50NerdsofGrey/status/757319527151636480')
      ->set('field_media_in_library', TRUE)
      ->setPublished()
      ->save();

    Media::create(['bundle' => 'instagram'])
      ->setName("I'm an instagram")
      ->set('embed_code', 'https://www.instagram.com/p/BaecNGYAYyP/')
      ->set('field_media_in_library', TRUE)
      ->setPublished()
      ->save();

    $this->drupalGet('/admin/content/media');
    $page->clickLink('Table');
    $assert_session->fieldExists('Published status');
    $assert_session->fieldExists('Media name');
    $assert_session->fieldExists('Language');

    // Ensure the Type filter exists, then store its value so we can actively
    // assert that the filter works as expected.
    $filter = $assert_session->fieldExists('Type');
    $original_value = $filter->getValue();
    $filter->selectOption('Tweet');

    $assert_session->elementExists('css', '.views-exposed-form')->submit();
    $assert_session->pageTextContains("I'm a tweet");
    $assert_session->pageTextNotContains("I'm an instagram");

    // Restore the original value.
    $filter->selectOption($original_value);
    $assert_session->elementExists('css', '.views-exposed-form')->submit();

    $page->selectFieldOption('Action', 'Delete media');
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->pressButton('Apply to selected items');
    $page->pressButton('Delete');

    $assert_session->pageTextContains('Deleted 2 items.');
    $assert_session->pageTextNotContains("I'm a tweet");
    $assert_session->pageTextNotContains("I'm an instragram");
  }

}
