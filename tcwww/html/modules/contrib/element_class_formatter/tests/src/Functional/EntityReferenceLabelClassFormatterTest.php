<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the entity reference label with class formatter.
 *
 * @group element_class_formatter
 */
class EntityReferenceLabelClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-entity-ref-class';

  /**
   * Tests element reference label class formatter.
   *
   * @dataProvider providerFormatterCases
   */
  public function testClassFormatter($link = TRUE, $tag = 'a') {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
      'tag' => 'div',
      'link' => $link,
    ];
    $field_config = $this->createEntityField('entity_reference_label_class', 'entity_reference', $formatter_settings);
    $referenced_node = $this->drupalCreateNode(['type' => 'referenced_content']);
    $referenced_node2 = $this->drupalCreateNode([
      'type' => 'referenced_content',
      'status' => 0,
    ]);

    $entity = EntityTest::create([
      $field_config->getName() => [$referenced_node, $referenced_node2],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', $tag . '.' . self::TEST_CLASS);
    $assert_session->pageTextContains($referenced_node->label());
    $assert_session->pageTextNotContains($referenced_node2->label());
  }

  /**
   * Data provider for ::testClassFormatter().
   *
   * @return array
   *   Test cases.
   */
  public function providerFormatterCases() {
    return [
      'linked' => [],
      'not linked' => [FALSE, 'div'],
    ];
  }

}
