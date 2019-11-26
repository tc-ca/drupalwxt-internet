<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the functionality of the media browser.
 *
 * @group lightning_media
 */
class MediaBrowserTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field_ui',
    'image_widget_crop',
    'lightning_media_audio',
    'lightning_media_document',
    'lightning_media_image',
    'lightning_media_instagram',
    'lightning_media_twitter',
    'lightning_media_video',
    'node',
  ];

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
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests access to the media browser.
   */
  public function testAccess() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access media_browser entity browser pages',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/entity-browser/modal/media_browser');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No widgets are available.');

    // Create a media type. There should still be no widgets available, since
    // the current user does not have permission to create media.
    $this->createMediaType('image');
    $this->getSession()->reload();
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No widgets are available.');

    $account = $this->drupalCreateUser([
      'access media_browser entity browser pages',
      'create media',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/entity-browser/modal/media_browser');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('No widgets are available.');
    $assert_session->buttonExists('Upload');
    $assert_session->buttonExists('Create embed');
  }

  /**
   * The media browser should be the default widget for a new media field.
   */
  public function testNewMediaReferenceField() {
    $this->drupalPlaceBlock('local_actions_block');

    $node_type = $this->drupalCreateContentType()->id();
    $media_type = $this->createMediaType('image')->id();

    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->drupalGet("/admin/structure/types/manage/$node_type/fields");
    $this->clickLink('Add field');
    $values = [
      'new_storage_type' => 'field_ui:entity_reference:media',
      'label' => 'Foobar',
      'field_name' => 'foobar',
    ];
    $this->drupalPostForm(NULL, $values, 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $values = [
      "settings[handler_settings][target_bundles][$media_type]" => $media_type,
    ];
    $this->drupalPostForm(NULL, $values, 'Save settings');

    $component = lightning_media_entity_get_form_display('node', $node_type)
      ->getComponent('field_foobar');

    $this->assertInternalType('array', $component);
    $this->assertSame('entity_browser_entity_reference', $component['type']);
    $this->assertSame('media_browser', $component['settings']['entity_browser']);
  }

  /**
   * Tests creating embed code-based media in the media browser.
   */
  public function testEmbedCodeBasedMediaCreation() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access media_browser entity browser pages',
      'access media overview',
      'create media',
    ]);
    $this->drupalLogin($account);

    // This could be done with the data provider pattern, but it's not really
    // needed, and this is significantly faster.
    $embed_codes = [
      'https://www.youtube.com/watch?v=zQ1_IbFFbzA',
      'https://vimeo.com/25585320',
      'https://twitter.com/webchick/status/672110599497617408',
      'https://www.instagram.com/p/jAH6MNINJG',
    ];
    foreach ($embed_codes as $embed_code) {
      $this->drupalGet('/entity-browser/modal/media_browser');

      $title = $this->randomString();

      $page->pressButton('Create embed');
      $page->fillField('input', $embed_code);
      $page->pressButton('Update');
      $page->fillField('Name', $title);
      $page->pressButton('Place');

      $this->drupalGet('/admin/content/media');
      $page->clickLink('Table');
      $assert_session->linkExists($title);
    }
  }

  /**
   * Tests creating file-based media in the media browser.
   */
  public function testFileBasedMediaCreation() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access media_browser entity browser pages',
      'access media overview',
      'create media',
    ]);
    $this->drupalLogin($account);

    // This could be done with the data provider pattern, but it's not really
    // needed, and this is significantly faster.
    $files = [
      'test.jpg' => TRUE,
      'test.mp4' => FALSE,
      'test.mp3' => FALSE,
      'test.pdf' => FALSE,
    ];
    foreach ($files as $file => $is_image) {
      $this->drupalGet('/entity-browser/modal/media_browser');

      $title = $this->randomString();

      $page->attachFileToField('File', __DIR__ . '/../../files/' . $file);
      $assert_session->elementExists('css', '.js-form-managed-file')
        ->pressButton('Upload');

      if ($is_image) {
        $summary = $assert_session->elementExists('css', 'details > summary:contains(Crop image)');
        $this->assertTrue($summary->getParent()->hasAttribute('open'));
        $assert_session->elementExists('css', 'details > summary:contains(Freeform)');
      }

      $page->fillField('Name', $title);
      $page->pressButton('Place');

      $this->drupalGet('/admin/content/media');
      $page->clickLink('Table');
      $assert_session->linkExists($title);
    }
  }

}
