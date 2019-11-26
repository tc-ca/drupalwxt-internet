<?php

namespace Drupal\Tests\lightning_media\FunctionalJavascript;

use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\lightning_media\Traits\EntityBrowserTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests media type disambiguation in the media browser.
 *
 * @group lightning_media
 */
class MediaBrowserWidgetDisambiguationTest extends WebDriverTestBase {

  use EntityBrowserTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media_image',
    'lightning_media_video',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createMediaType('image', [
      'id' => 'picture',
      'label' => 'Picture',
    ]);
    $this->createMediaType('video_embed_field', [
      'id' => 'advertisement',
      'label' => 'Advertisement',
    ]);

    $this->createContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => NULL,
        ],
      ],
    ])->save();

    lightning_media_entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_media', [
        'type' => 'entity_browser_entity_reference',
        'settings' => [
          'entity_browser' => 'media_browser',
          'field_widget_display' => 'rendered_entity',
          'field_widget_edit' => TRUE,
          'field_widget_remove' => TRUE,
          'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
          'field_widget_display_settings' => [
            'view_mode' => 'thumbnail',
          ],
          'open' => TRUE,
        ],
        'region' => 'content',
      ])
      ->save();

    $account = $this->createUser([
      'create article content',
      'access media_browser entity browser pages',
      'create media',
    ]);
    $this->drupalLogin($account);

    $page = $this->getSession()->getPage();

    $this->drupalGet('/node/add/article');
    $page->fillField('Title', 'Foo');
    $page->pressButton('Add media');
    $this->waitForEntityBrowser('media_browser');
  }

  /**
   * Tests that select is shown when media bundle is ambiguous.
   */
  public function testUpload() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // This is necessary, it seems, in order for automatic file uploads to work
    // correctly.
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->waitForField('input_file')->attachFile(__DIR__ . '/../../files/test.jpg');
    $assert_session->waitForField('Bundle')->selectOption('Picture');
    $assert_session->waitForField('Name')->setValue('Bar');
    $page->fillField('Alternative text', 'Baz');
    $page->pressButton('Place');
    $this->waitForEntityBrowserToClose();

    $assert_session->waitForButton('Remove');
    $page->pressButton('Save');

    // Assert the correct entities are created.
    $node = Node::load(1);
    $this->assertInstanceOf(Node::class, $node);
    /** @var \Drupal\node\NodeInterface $node */
    $this->assertSame('Foo', $node->getTitle());
    $this->assertFalse($node->get('field_media')->isEmpty());
    $this->assertSame('picture', $node->field_media->entity->bundle());
    $this->assertSame('Bar', $node->field_media->entity->getName());
    $this->assertSame('Baz', $node->field_media->entity->field_media_image->alt);
    $this->assertSame('test.jpg', $node->field_media->entity->field_media_image->entity->getFilename());
  }

  /**
   * Tests that select is shown when media bundle is ambiguous.
   */
  public function testEmbed() {
    $assert_session = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $assert_session->waitForLink('Create embed')->click();
    $video_url = 'https://www.youtube.com/watch?v=zQ1_IbFFbzA';
    $assert_session->waitForField('input')->setValue($video_url);
    $assert_session->waitForField('Bundle')->selectOption('Advertisement');
    $assert_session->waitForField('Video Url');
    $page->fillField('Name', 'Bar');
    $page->pressButton('Place');
    $this->waitForEntityBrowserToClose();

    $assert_session->waitForButton('Remove');
    $page->pressButton('Save');

    // Assert the correct entities are created.
    $node = Node::load(1);
    $this->assertInstanceOf(Node::class, $node);
    $this->assertSame('Foo', $node->getTitle());
    $this->assertSame('advertisement', $node->field_media->entity->bundle());
    $this->assertSame('Bar', $node->field_media->entity->label());
    $this->assertSame($video_url, $node->field_media->entity->field_media_video_embed_field->value);
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new WebDriverWebAssert($this->getSession($name), $this->baseUrl);
  }

}
