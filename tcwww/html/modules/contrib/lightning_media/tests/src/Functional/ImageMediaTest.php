<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\lightning_media\Traits\EntityBrowserTrait;

/**
 * Tests the image media type.
 *
 * @group lightning_media
 */
class ImageMediaTest extends BrowserTestBase {

  use EntityBrowserTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image_widget_crop',
    'lightning_media_image',
    'lightning_roles',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->addMediaLibraryToEntityBrowsers();
  }

  /**
   * Tests creating an image to be ignored by the media library.
   */
  public function testCreateIgnoredImage() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([]);
    $account->addRole('media_creator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/media/add/image');
    $page->attachFileToField('Image', __DIR__ . '/../../files/test.jpg');
    $page->pressButton('Upload');

    // Cropping should be enabled.
    $summary = $assert_session->elementExists('css', 'details > summary:contains(Crop image)');
    $this->assertTrue($summary->getParent()->hasAttribute('open'));
    $assert_session->elementExists('css', 'details > summary:contains(Freeform)');

    $page->fillField('Name', 'Blorg');
    $page->uncheckField('Show in media library');
    $page->pressButton('Save');
    $this->drupalGet('/entity-browser/modal/media_browser');
    $assert_session->pageTextContains('There are no media items to display.');
  }

}
