<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\filter\Entity\FilterFormat;

/**
 * Functional tests for the mailto link with class formatter.
 *
 * @group element_class_formatter
 */
class WrapperClassFormatterTest extends ElementClassFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  const TEST_CLASS = 'test-wrapper-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
      'tag' => 'h2',
    ];
    $field_config = $this->createEntityField('wrapper_class', 'string', $formatter_settings);

    $entity = EntityTest::create([
      $field_config->getName() => [['value' => 'I am a string']],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'h2.' . self::TEST_CLASS);
  }

  /**
   * Tests summary formatter.
   *
   * @dataProvider providerSummaryFormatter
   *
   */
  public function testTextWithSummary(bool $summary, string $expected, int $trim = NULL) {
    $format = FilterFormat::create(['format' => $this->randomMachineName()]);
    $format->save();
    $formatter_settings = [
      'class' => self::TEST_CLASS,
      'tag' => 'h2',
      'summary' => $summary,
    ];
    $field_config = $this->createEntityField('wrapper_class', 'text_with_summary', $formatter_settings);

    $entity = EntityTest::create([
      $field_config->getName() => [
        [
          'value' => 'I am a string',
          'summary' => !$trim ? 'I am a summary' : '',
          'format' => $format->id(),
        ],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementTextContains('css', 'h2.' . self::TEST_CLASS, $expected);
  }

  /**
   * Provider for ::testTextWithSummary().
   *
   * @return array
   *   Test cases.
   */
  public function providerSummaryFormatter() {
    return [
      'body' => [
        FALSE, 'I am a string',
      ],
      'summary' => [
        TRUE, 'I am a summary',
      ],
      'trimmed fallback' => [
        FALSE, 'I am a string', 3,
      ],
    ];
  }

}
