<?php

namespace Drupal\Tests\lightning_media\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\lightning_media\Traits\EntityBrowserTrait;
use Drupal\Tests\lightning_media\Traits\EntityEmbedTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the media browser's integration with CKEditor.
 *
 * @group lightning_media
 */
class CKEditorMediaBrowserTest extends WebDriverTestBase {

  use EntityBrowserTrait;
  use EntityEmbedTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image_widget_crop',
    'lightning_media_document',
    'lightning_media_image',
    'node',
    'media_test_source',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    $account = $this->drupalCreateUser([
      'access media overview',
      'create page content',
      'edit own page content',
      'access ckeditor_media_browser entity browser pages',
      'access media_browser entity browser pages',
      'use text format rich_text',
    ]);
    $this->drupalLogin($account);

    $this->addMediaLibraryToEntityBrowsers();

    module_load_install('lightning_media_image');
    lightning_media_image_install();
  }

  /**
   * Tests exposed filters in the media browser.
   */
  public function testExposedFilters() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $media_type = $this->createMediaType('test')->id();
    for ($i = 0; $i < 3; $i++) {
      $this->addMedia([
        'bundle' => $media_type,
        'name' => sprintf('Code Wisdom %d', $i + 1),
        'field_media_test' => $this->randomString(),
      ]);
    }

    $this->drupalGet('/node/add/page');
    $this->open();

    // All items should be visible initially.
    $this->waitForItemsCount(3);

    // Try filtering by media type.
    $page->selectFieldOption('Type', 'Image');
    $page->pressButton('Filter');
    $assert_session->waitForText('There are no media items to display.');
    $assert_session->elementsCount('css', '[data-selectable]', 0);

    // Clear the type filter.
    $page->selectFieldOption('Type', '- Any -');
    $page->pressButton('Filter');
    $this->waitForItemsCount(3);

    // Try filtering by keywords.
    $page->fillField('Keywords', 'Code Wisdom 1');
    $page->pressButton('Filter');
    $this->waitForItemsCount(1);

    // Clear the keyword filter.
    $page->fillField('Keywords', '');
    $page->pressButton('Filter');
    $this->waitForItemsCount(3);
  }

  /**
   * Waits for a specific number of selectable media items to be present.
   *
   * @param int $count
   *   The number of items we're waiting for.
   */
  private function waitForItemsCount($count) {
    $result = $this->getSession()
      ->getPage()
      ->waitFor(10, function () use ($count) {
        return count($this->waitForItems()) === $count;
      });

    $this->assertTrue($result);
  }

  /**
   * Tests that cardinality is never enforced in the media browser.
   */
  public function testUnlimitedCardinality() {
    $media_type = $this->createMediaType('test')->id();

    for ($i = 0; $i < 2; $i++) {
      $this->addMedia([
        'bundle' => $media_type,
        'field_media_test' => $this->randomString(),
      ]);
    }

    $this->drupalGet('/node/add/page');
    $this->open();

    $items = $this->waitForItems();
    $this->assertCount(2, $items);
    $this->selectItem($items[0]);
    $this->selectItem($items[1]);

    // Only one item can be selected at any time, but nothing is ever disabled.
    $assert_session = $this->assertSession();
    $assert_session->elementsCount('css', '[data-selectable].selected', 1);
    $assert_session->elementsCount('css', '[data-selectable].disabled', 0);
  }

  /**
   * Tests that the entity embed dialog opens when editing a pre-existing embed.
   */
  public function testEditEmbed() {
    $page = $this->getSession()->getPage();

    $media_type = $this->createMediaType('test')->id();
    $this->addMedia([
      'bundle' => $media_type,
      'field_media_test' => $this->randomString(),
    ]);

    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Blorf',
      'body' => [
        'value' => '',
        'format' => 'rich_text',
      ],
    ]);

    $this->drupalGet($node->toUrl('edit-form'));
    $this->open();

    $items = $this->waitForItems();
    $this->selectItem($items[0]);
    $page->pressButton('Place');
    $this->waitForEntityBrowserToClose();

    $this->submitEmbedForm();

    $page->pressButton('Save');
    $this->drupalGet($node->toUrl('edit-form'));

    $this->open(FALSE, function ($editor) {
      $this->getSession()
        ->executeScript("CKEDITOR.instances['$editor'].widgets.instances[0].focus()");
    });
    $this->waitForEmbedForm();
  }

  /**
   * Tests that the image embed plugin is used to embed an image.
   */
  public function testImageEmbed() {
    $session = $this->getSession();

    $uri = uniqid('public://') . '.png';
    $uri = $this->getRandomGenerator()->image($uri, '640x480', '800x600');
    $this->assertFileExists($uri);
    $image = File::create([
      'uri' => $uri,
    ]);
    $image->save();

    $media = $this->addMedia([
      'bundle' => 'image',
      'name' => 'Foobar',
      'image' => $image->id(),
    ]);
    $media->image->alt = 'I am the greetest';
    $media->save();

    $this->drupalGet('/node/add/page');
    $this->open();

    $items = $this->waitForItems();
    $this->selectItem($items[0]);
    $session->getPage()->pressButton('Place');
    $session->switchToIFrame(NULL);

    $embed_form = $this->waitForImageEmbedForm();

    $assert_session = $this->assertSession();
    $assert_session->optionExists('Image style', 'Cropped: Freeform', $embed_form);
    $assert_session->fieldValueEquals('Alternate text', 'I am the greetest', $embed_form);
    $assert_session->fieldValueEquals('attributes[title]', 'Foobar', $embed_form);
  }

  /**
   * Tests that the image embed plugin is not used to embed a document.
   */
  public function testDocumentEmbed() {
    $session = $this->getSession();

    $uri = uniqid('public://') . '.txt';
    file_put_contents($uri, $this->getRandomGenerator()->paragraphs());
    $file = File::create([
      'uri' => $uri,
    ]);
    $file->save();

    $this->addMedia([
      'bundle' => 'document',
      'field_document' => $file->id(),
    ]);

    $this->drupalGet('/node/add/page');
    $this->open();

    $items = $this->waitForItems();
    $this->selectItem($items[0]);
    $session->getPage()->pressButton('Place');
    $session->switchToIFrame(NULL);

    $this->waitForStandardEmbedForm();
  }

  /**
   * Adds a media item to the library and marks it for deletion in tearDown().
   *
   * @param array $values
   *   The values with which to create the media item.
   *
   * @return \Drupal\media\MediaInterface
   *   The saved media item.
   */
  private function addMedia(array $values) {
    $values['field_media_in_library'] = TRUE;
    $values['status'] = TRUE;

    $media = Media::create($values);
    $media->save();

    return $media;
  }

  /**
   * Opens the CKeditor media browser.
   *
   * @param bool $switch
   *   (optional) If TRUE, switch into the media browser iFrame. Defaults to
   *   TRUE.
   * @param callable $pre_open
   *   (optional) A callback function run before opening the media browser,
   *   for example to run some additional JavaScript. Defaults to NULL.
   */
  private function open($switch = TRUE, callable $pre_open = NULL) {
    $session = $this->getSession();

    // Wait for CKEditor to be ready.
    $this->assertJsCondition('typeof CKEDITOR.instances === "object"');

    // Assert that we have a valid list of CKeditor instance IDs.
    /** @var array $editors */
    $editors = $session->evaluateScript('Object.keys(CKEDITOR.instances)');
    $this->assertInternalType('array', $editors);
    $this->assertNotEmpty($editors);

    // Assert that the editor is ready.
    $editor = $editors[0];
    $this->assertJsCondition("CKEDITOR.instances['$editor'].status === 'ready'");

    if ($pre_open) {
      $pre_open($editor);
    }

    $status = $session->evaluateScript("CKEDITOR.instances['$editor'].execCommand('editdrupalentity', { id: 'media_browser' });");
    $this->assertNotEmpty($status);

    if ($switch) {
      $this->waitForEntityBrowser('ckeditor_media_browser', $switch);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new WebDriverWebAssert($this->getSession($name), $this->baseUrl);
  }

}
