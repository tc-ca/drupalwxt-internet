<?php

namespace Drupal\Tests\lightning_media_slideshow\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\lightning_media\FunctionalJavascript\WebDriverWebAssert;
use Drupal\Tests\lightning_media\Traits\EntityBrowserTrait;
use Drupal\views\Entity\View;

/**
 * Tests the basic functionality of Lightning Media's slideshow component.
 *
 * @group lightning_media_slideshow
 * @group lightning_media
 */
class SlideshowTest extends WebDriverTestBase {

  use EntityBrowserTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Slick Entity Reference has a schema error.
   *
   * @var bool
   *
   * @todo Remove when depending on slick_entityreference 1.2 or later.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'lightning_media_instagram',
    'lightning_media_slideshow',
    'lightning_media_twitter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $GLOBALS['install_state'] = [];
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('media');
    lightning_media_view_insert($view);
    unset($GLOBALS['install_state']);
  }

  /**
   * Tests creating a slideshow block with media items in it.
   */
  public function testSlideshow() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access content',
      'access media_browser entity browser pages',
      'access media overview',
      'view media',
      'create media',
      'update media',
      'administer blocks',
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

    $this->drupalGet('/block/add/media_slideshow');
    $page->fillField('Block description', 'Test Block');

    $page->pressButton('Add media');
    $this->waitForEntityBrowser('media_browser');

    $items = $this->waitForItems();
    $this->assertGreaterThanOrEqual(2, count($items));
    $this->selectItem($items[0]);
    $this->selectItem($items[1]);

    $page->pressButton('Place');
    $this->waitForEntityBrowserToClose();

    // Wait for the selected items to actually appear on the page.
    $assert_session->waitForElement('css', '[data-drupal-selector^="edit-field-slideshow-items-current-items-"]');

    $page->pressButton('Save');
    $page->selectFieldOption('Region', 'Content');
    $page->pressButton('Save block');
    $this->drupalGet('<front>');

    $this->assertNotEmpty($assert_session->waitForElement('css', 'button.slick-prev.slick-arrow'));
    $assert_session->elementExists('css', 'button.slick-next.slick-arrow');
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new WebDriverWebAssert($this->getSession($name), $this->baseUrl);
  }

}
