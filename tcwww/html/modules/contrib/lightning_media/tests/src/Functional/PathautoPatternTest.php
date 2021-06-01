<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests that all media items have a /media/BUNDLE/ID Pathauto pattern.
 *
 * @group lightning_media
 */
class PathautoPatternTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'pathauto',
    'lightning_media_document',
    'lightning_media_image',
    'lightning_media_instagram',
    'lightning_media_twitter',
    'lightning_media_video',
    'media_test_source',
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
    $this->config('media.settings')->set('standalone_url', TRUE)->save();
    drupal_flush_all_caches();
  }

  /**
   * Tests media types that ship with Lightning.
   */
  public function testMediaPattern() {
    $assert_session = $this->assertSession();

    // This could be done with the data provider pattern, but there's no real
    // benefit to that in this case, and this is significantly faster.
    $media = [
      'document' => NULL,
      'image' => NULL,
      'video' => NULL,
      'tweet' => 'https://twitter.com/50NerdsofGrey/status/757319527151636480',
      'instagram' => 'https://www.instagram.com/p/BmIh_AFDBzX',
    ];
    foreach ($media as $type => $source_value) {
      /** @var \Drupal\media\MediaInterface $media */
      $media_item = Media::create([
        'bundle' => $type,
        'name' => $this->randomString(),
      ]);

      if ($source_value) {
        $source_field = $media_item->getSource()
          ->getSourceFieldDefinition($media_item->bundle->entity)
          ->getName();

        $media_item->set($source_field, $source_value);
      }
      $media_item->setPublished()->save();

      $this->drupalGet($media_item->toUrl());
      $assert_session->statusCodeEquals(200);
      $assert_session->pageTextContains($media_item->label());
      $assert_session->addressEquals('/media/' . strtolower($media_item->bundle()) . '/' . $media_item->id());
    }
  }

  /**
   * Tests a new media type.
   */
  public function testNewMediaTypePattern() {
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $this->createMediaType('test')->id(),
      'name' => 'Foo Bar',
    ]);
    $media->setPublished()->save();

    $this->drupalGet("/media/{$media->bundle()}/{$media->id()}");

    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Foo Bar');
  }

}
