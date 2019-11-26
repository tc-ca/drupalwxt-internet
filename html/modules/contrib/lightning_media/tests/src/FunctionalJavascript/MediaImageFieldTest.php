<?php

namespace Drupal\Tests\lightning_media\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * @group lightning_media
 */
class MediaImageFieldTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_media_image',
    'lightning_media_video',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // The media.settings:standalone_url setting was added in Drupal 8.7. To
    // avoid config schema errors, we should only set the option if it actually
    // exists to begin with.
    $settings = $this->config('media.settings');
    if ($settings->get('standalone_url') === FALSE) {
      $settings->set('standalone_url', TRUE)->save();
      // Flush all caches to rebuild the entity type definitions and routing
      // tables, which will expose the canonical media entity route.
      drupal_flush_all_caches();
    }
  }

  /**
   * Tests clearing an image field on an existing media item.
   */
  public function test() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $field_name = 'field_test' . mb_strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'media',
      'type' => 'image',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'video',
      'label' => 'Image',
    ])->save();

    $this->drupalPlaceBlock('local_tasks_block');

    $form_display = lightning_media_entity_get_form_display('media', 'video');
    // Add field_image to the display and save it; lightning_media_image will
    // default it to the image browser widget.
    $form_display->setComponent($field_name, ['type' => 'image_image'])->save();
    // Then switch it to a standard image widget.
    $form_display
      ->setComponent($field_name, [
        'type' => 'image_image',
        'weight' => 4,
        'settings' => [
          'preview_image_style' => 'thumbnail',
          'progress_indicator' => 'throbber',
        ],
        'region' => 'content',
      ])
      ->save();

    $account = $this->createUser([
      'create media',
      'update media',
    ]);
    $this->drupalLogin($account);

    $name = $this->randomString();

    $this->drupalGet('/media/add/video');
    $page->fillField('Name', $name);
    $page->fillField('Video URL', 'https://www.youtube.com/watch?v=z9qY4VUZzcY');
    $this->assertNotEmpty($assert_session->waitForField('Image'));
    $page->attachFileToField('Image', __DIR__ . '/../../files/test.jpg');
    $this->assertNotEmpty($assert_session->waitForField('Alternative text'));
    $page->fillField('Alternative text', 'This is a beauty.');
    $page->pressButton('Save');
    $page->clickLink('Edit');
    $page->pressButton("{$field_name}_0_remove_button");
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure that the widget has actually been cleared. This test was written
    // because the AJAX operation would fail due to a 500 error at the server,
    // which would prevent the widget from being cleared.
    $assert_session->buttonNotExists("{$field_name}_0_remove_button");
  }

}
