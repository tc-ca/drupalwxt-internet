<?php

namespace Drupal\Tests\layout_builder_st\Functional\Jsonapi;

use Drupal\layout_builder_st\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder_st\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder_st\ResourceTypeRepository;
use Drupal\Tests\layout_builder\Functional\Jsonapi\LayoutBuilderEntityViewDisplayTest as CoreTest;

/**
 * JSON:API integration test for the "EntityViewDisplay" config entity type.
 *
 * @group jsonapi
 * @group layout_builder
 */
class LayoutBuilderEntityViewDisplayTest extends CoreTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['layout_builder_st'];

  /**
   * {@inheritdoc}
   */
  public function testGetIndividual() {
    $resource_type_repository = $this->container->get('jsonapi.resource_type.repository');
    $this->assertInstanceOf(ResourceTypeRepository::class, $resource_type_repository);

    // Ensure that the entity_view_display entity class has actually been
    // overridden.
    $entity_class = $this->container->get('entity_type.manager')
      ->getDefinition('entity_view_display')
      ->getClass();
    $this->assertSame(LayoutBuilderEntityViewDisplay::class, $entity_class);

    parent::testGetIndividual();
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $document = parent::getExpectedDocument();
    $document['data']['attributes']['hidden'][OverridesSectionStorage::TRANSLATED_CONFIGURATION_FIELD_NAME] = TRUE;
    return $document;
  }

}
