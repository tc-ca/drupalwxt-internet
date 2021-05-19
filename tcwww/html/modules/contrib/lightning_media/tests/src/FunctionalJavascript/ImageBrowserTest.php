<?php

namespace Drupal\Tests\lightning_media\FunctionalJavascript;

use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\lightning_media\Traits\EntityBrowserTrait;

/**
 * Tests the image browser shipped with Lightning Media Image.
 *
 * @group lightning_media
 */
class ImageBrowserTest extends WebDriverTestBase {

  use EntityBrowserTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

    $this->drupalCreateContentType(['type' => 'page']);

    $field_storage = FieldStorageConfig::create([
      'type' => 'image',
      'entity_type' => 'node',
      'field_name' => 'field_hero_image',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'bundle' => 'page',
      'field_storage' => $field_storage,
    ])->save();

    lightning_media_entity_get_form_display('node', 'page')
      ->setComponent('field_hero_image', [
        'type' => 'entity_browser_file',
        'settings' => [
          'entity_browser' => 'image_browser',
          'field_widget_edit' => TRUE,
          'field_widget_remove' => TRUE,
          'view_mode' => 'default',
          'preview_image_style' => 'thumbnail',
          'open' => TRUE,
          'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        ],
        'region' => 'content',
      ])
      ->save();
  }

  /**
   * Tests uploading an image in the image browser.
   */
  public function testUpload() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser(['create page content']);
    $account->addRole('media_creator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');

    $assert_session->elementExists('css', '.field--name-field-hero-image')
      ->pressButton('Select Image(s)');
    $this->waitForEntityBrowser('image_browser');

    // This helps stabilize the next couple of calls; without it, the
    // upload is more likely to randomly fail. It's not clear why this
    // is the case, but repeated testing on Travis CI seems to support
    // it.
    $assert_session->assertWaitOnAjaxRequest();

    $path = realpath(__DIR__ . '/../../files/test.jpg');
    $this->assertNotEmpty($path);
    $assert_session->waitForField('File')->attachFile($path);
    $assert_session->waitForField('Name')->setValue('Behold, a generic logo');

    $summary = $assert_session->elementExists('css', 'details > summary:contains(Crop image)');
    $this->assertTrue($summary->getParent()->hasAttribute('open'));
    $assert_session->elementExists('css', 'details > summary:contains(Freeform)');

    $page->pressButton('Select');
    $this->waitForEntityBrowserToClose();

    $assert_session->elementNotExists('css', "table[drupal-data-selector='edit-image-current'] td.empty");
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new WebDriverWebAssert($this->getSession($name), $this->baseUrl);
  }

}
