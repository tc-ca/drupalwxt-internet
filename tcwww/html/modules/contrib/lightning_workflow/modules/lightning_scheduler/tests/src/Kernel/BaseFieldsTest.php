<?php

namespace Drupal\Tests\lightning_scheduler\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lightning_scheduler
 */
class BaseFieldsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'lightning_scheduler',
    'node',
    'workflows',
  ];

  public function testBaseFieldDefinitions() {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions */
    $field_definitions = $this->container->get('entity_field.manager')
      ->getBaseFieldDefinitions('node');

    $this->assertEquals('Scheduled transition date', $field_definitions['scheduled_transition_date']->getLabel());
    $this->assertEquals('Scheduled transition state', $field_definitions['scheduled_transition_state']->getLabel());
  }

}
