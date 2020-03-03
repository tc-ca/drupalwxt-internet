<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\field\Entity\FieldConfig;

/**
 * @group lightning_media
 */
class MediaBrowserUploadWidgetTest extends MediaBrowserWidgetTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_media_image'];

  /**
   * {@inheritdoc}
   */
  protected function createMediaTypes() {
    $this->createMediaType('image', [
      'id' => 'picture',
      'label' => 'Picture',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function chooseWidget() {
    // The Upload widget is the default one, so there's no need to do anything
    // here beyond assert that everything looks right.
    $this->assertSession()->fieldExists('File');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvalidInput() {
    $assert_session = $this->assertSession();

    parent::testInvalidInput();
    $assert_session->pageTextContains('You must upload a file.');

    $this->uploadFile(__DIR__ . '/../../files/test.php');
    $assert_session->elementExists('css', '[role="alert"]');
    $assert_session->pageTextContains('Only files with the following extensions are allowed');
    // The error message should not be double-escaped.
    $assert_session->responseNotContains('&lt;em class="placeholder"&gt;');

    $this->uploadFile(__DIR__ . '/../../files/test.jpg');
    $assert_session->elementNotExists('css', '[role="alert"]');
  }

  /**
   * {@inheritdoc}
   */
  public function testFieldAllowedTypesSettingIsRespected() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $field = FieldConfig::loadByName('node', 'page', 'field_media');
    $handler_settings = $field->getSetting('handler_settings');
    $handler_settings['target_bundles'] = [
      'image' => 'image',
    ];
    $field->setSetting('handler_settings', $handler_settings)->save();

    $this->visitMediaBrowserFromNodeForm();
    $this->uploadFile(__DIR__ . '/../../files/test.jpg');

    // The field only allows Image media items, so there should be no need to
    // disambiguate.
    $assert_session->fieldNotExists('Bundle');
    $page->fillField('Name', $this->randomString());
    $page->fillField('Alternative text', $this->randomString());
    $page->pressButton('Place');
    $assert_session->statusCodeEquals(200);

    $this->assertMediaCount(1, ['bundle' => 'image']);
  }

  /**
   * {@inheritdoc}
   */
  public function testDisambiguation() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    FieldConfig::loadByName('media', 'image', 'image')
      ->setSetting('max_resolution', '100x100')
      ->save();

    $this->visitMediaBrowserFromNodeForm();
    // Upload a 500x500 image.
    $this->uploadFile(__DIR__ . '/../../files/test.jpg');

    $assert_session->elementNotExists('css', '[role="contentinfo"]');
    $page->selectFieldOption('Bundle', 'Image');
    $page->pressButton('Update');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Status message The image was resized to fit within the maximum allowed dimensions of 100x100 pixels. The new dimensions of the resized image are 100x100 pixels.');

    $page->fillField('Name', $this->randomString());
    $page->fillField('Alternative text', $this->randomString());
    $page->pressButton('Place');

    $this->assertMediaCount(1, ['bundle' => 'image']);
  }

  /**
   * Uploads a file in the media browser.
   *
   * @param string $path
   *   The local path of the file to upload.
   */
  private function uploadFile($path) {
    $this->assertFileExists($path);

    $assert_session = $this->assertSession();

    $file_field = $assert_session->elementExists('css', '.js-form-managed-file');
    $file_field->attachFileToField('File', $path);
    $file_field->pressButton('Upload');
    $assert_session->statusCodeEquals(200);
  }

}
