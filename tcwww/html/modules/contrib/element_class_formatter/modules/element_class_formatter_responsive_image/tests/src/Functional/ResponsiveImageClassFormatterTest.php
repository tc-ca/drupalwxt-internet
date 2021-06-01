<?php

namespace Drupal\Tests\element_class_formatter_responsive_image\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\file\Entity\File;
use Drupal\Tests\element_class_formatter\Functional\ElementClassFormatterTestBase;

/**
 * Functional tests for the responsive image link with class formatter.
 *
 * @group element_class_formatter_responsive_image
 */
class ResponsiveImageClassFormatterTest extends ElementClassFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'element_class_formatter_responsive_image',
  ];

  const TEST_CLASS = 'test-responsive-img-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
    ];
    $field_config = $this->createEntityField('responsive_image_class', 'image', $formatter_settings);

    $image = current($this->getTestFiles('image'));
    $file = File::create([
      'uri' => $image->uri,
      'status' => 1,
    ]);
    $file->save();

    $entity = EntityTest::create([
      $field_config->getName() => [['target_id' => $file->id()]],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'img.' . self::TEST_CLASS);
  }

}
