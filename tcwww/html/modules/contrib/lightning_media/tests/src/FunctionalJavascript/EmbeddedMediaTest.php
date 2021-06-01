<?php

namespace Drupal\Tests\lightning_media\FunctionalJavascript;

use Drupal\Component\Serialization\Json;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\lightning_media\Traits\EntityBrowserTrait;
use Drupal\Tests\lightning_media\Traits\EntityEmbedTrait;

/**
 * Tests embedding media items in CKEditor using the media browser.
 *
 * @group lightning_media
 */
class EmbeddedMediaTest extends WebDriverTestBase {

  use EntityBrowserTrait;
  use EntityEmbedTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media_image',
    'lightning_media_instagram',
    'lightning_media_twitter',
    'lightning_media_video',
    'lightning_roles',
  ];

  /**
   * Data provider for ::testEmbedCodeBasedEmbeddedMedia().
   *
   * @return array[]
   *   The test arguments for each scenario.
   */
  public function providerEmbedCodeBasedEmbeddedMedia() {
    return [
      ['video', 'https://www.youtube.com/watch?v=N2_HkWs7OM0'],
      ['video', 'https://vimeo.com/25585320'],
      ['tweet', 'https://twitter.com/djphenaproxima/status/879739227617079296'],
      ['instagram', 'https://www.instagram.com/p/lV3WqOoNDD'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->addMediaLibraryToEntityBrowsers();
    $this->drupalCreateContentType(['type' => 'page']);

    $account = $this->drupalCreateUser([
      'create page content',
      'use text format rich_text',
    ]);
    $account->addRole('media_creator');
    $account->save();
    $this->drupalLogin($account);
  }

  /**
   * Tests embedding embed code-based media, with no image options.
   *
   * @dataProvider providerEmbedCodeBasedEmbeddedMedia
   */
  public function testEmbedCodeBasedEmbeddedMedia($media_type, $embed_code) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $media_type,
      'name' => $this->randomString(),
      'embed_code' => (string) $embed_code,
      'field_media_in_library' => TRUE,
    ]);
    $media->setPublished()->save();

    $this->drupalGet('/node/add/page');
    $this->open();
    $items = $this->waitForItems();
    $this->selectItem($items[0]);
    $this->getSession()->getPage()->pressButton('Place');

    $this->waitForEntityBrowserToClose();
    $this->waitForStandardEmbedForm();
  }

  /**
   * Tests embedding image media with custom alt and title text.
   */
  public function testEmbedImageMedia() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $random = $this->getRandomGenerator();

    $uri = $random->image(uniqid('public://random_') . '.png', '240x240', '640x480');

    $file = File::create(['uri' => $uri]);
    $file->setMimeType('image/png');
    $file->setTemporary();
    $file->save();

    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => 'image',
      'name' => $random->name(32),
      'image' => $file->id(),
      'field_media_in_library' => TRUE,
    ]);
    $media->setPublished()->save();

    $this->drupalGet('/node/add/page');
    $page->fillField('Title', 'Foobar');
    $this->open();
    $items = $this->waitForItems();
    $this->selectItem($items[0]);
    $page->pressButton('Place');

    $embed_form = $this->waitForImageEmbedForm();
    $embed_form->selectFieldOption('Image style', 'medium');
    $embed_form->fillField('Alternate text', 'Behold my image of randomness');
    $embed_form->fillField('Title', 'Ye gods!');
    $this->submitEmbedForm();

    $page->pressButton('Save');
    $assert_session->responseContains('Behold my image of randomness');
    $assert_session->responseContains('Ye gods!');
  }

  /**
   * Executes a CKEditor command.
   *
   * @param string $command
   *   The command ID, as known to CKEditor's API.
   * @param string $id
   *   (optional) The editor instance ID.
   * @param mixed $data
   *   Additional data to pass to the executed command.
   */
  private function executeEditorCommand($command, $id = NULL, $data = NULL) {
    $js = $this->assertEditor($id);

    $result = $this->getSession()
      ->evaluateScript("$js.execCommand('$command', " . Json::encode($data) . ')');

    $result = Json::decode($result);
    $this->assertNotEmpty($result);
  }

  /**
   * Asserts that a CKEditor instance exists and is fully loaded.
   *
   * @param string $id
   *   (optional) The editor instance ID. Defaults to the first available
   *   instance.
   *
   * @return string
   *   A snippet of JavaScript for calling instance methods.
   */
  private function assertEditor($id = NULL) {
    $id = $id ?: $this->getDefaultEditor();

    $js = "CKEDITOR.instances['$id']";
    $this->assertJsCondition("$js.status === 'ready'");

    return $js;
  }

  /**
   * Returns the first available CKEditor instance ID.
   *
   * @return string|false
   *   The first CKEditor instance ID, or FALSE if there are no instances.
   */
  private function getDefaultEditor() {
    $keys = $this->getEditors();
    return reset($keys);
  }

  /**
   * Returns all CKEditor instance IDs.
   *
   * @return string[]
   *   The CKEditor instance IDs.
   */
  private function getEditors() {
    $this->assertJsCondition("typeof CKEDITOR.instances === 'object'");

    $keys = $this
      ->getSession()
      ->evaluateScript('Object.keys(CKEDITOR.instances).join(",")');

    return explode(',', $keys);
  }

  /**
   * Opens the media browser.
   */
  private function open() {
    $this->executeEditorCommand('editdrupalentity', NULL, ['id' => 'media_browser']);
    $this->waitForEntityBrowser('ckeditor_media_browser');
  }

}
