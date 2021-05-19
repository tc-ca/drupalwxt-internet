<?php

namespace Drupal\Tests\lightning_media_instagram\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the translatability of the field_media_in_library field.
 *
 * @group lightning_media
 * @group lightning_media_instagram
 */
class LibraryInclusionTranslationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'lightning_media_instagram',
  ];

  /**
   * Tests that the 'field_media_in_library' field is not translatable.
   */
  public function test() {
    ConfigurableLanguage::createFromLangcode('hu')->save();

    $media = Media::create([
      'bundle' => 'instagram',
      'name' => $this->randomString(),
      'embed_code' => 'https://www.instagram.com/p/CGkIkLngLDS',
      'field_media_in_library' => TRUE,
    ]);
    $media->addTranslation('hu', [
      'field_media_in_library' => FALSE,
    ]);
    $this->assertSame(SAVED_NEW, $media->save());

    $this->assertTrue($media->field_media_in_library->value);
    $this->assertTrue($media->getTranslation('hu')->field_media_in_library->value);
  }

}
