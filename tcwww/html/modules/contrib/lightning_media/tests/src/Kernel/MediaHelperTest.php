<?php

namespace Drupal\Tests\lightning_media\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\lightning_media\MediaHelper;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Contains unit-level test coverage of MediaHelper.
 *
 * @group lightning_media
 *
 * @coversDefaultClass \Drupal\lightning_media\MediaHelper
 */
class MediaHelperTest extends KernelTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'lightning_media',
    'media',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');

    FieldStorageConfig::create([
      'entity_type' => 'media',
      'type' => 'boolean',
      'field_name' => 'field_media_in_library',
    ])->save();
  }

  /**
   * @covers ::getSourceField
   * @covers ::prepareFileDestination
   * @covers ::useFile
   */
  public function testUseFile() {
    $media_type = $this->createMediaType('file');

    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $media_type->id(),
    ]);

    /** @var \Drupal\field\Entity\FieldConfig $source_field */
    $source_field = $media->getSource()->getSourceFieldDefinition($media_type);
    $source_field->setSetting('file_directory', 'wambooli')->save();
    $field_name = $source_field->getName();

    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uri' => $this->generateFile('foo', 80, 10),
    ]);
    $file->save();

    $this->assertDirectoryNotExists('public://wambooli');
    $this->assertTrue($media->get($field_name)->isEmpty());

    $file = MediaHelper::useFile($media, $file);

    $this->assertSame($file->id(), $media->$field_name->target_id);
    $this->assertFileExists($file->getFileUri());
  }

}
