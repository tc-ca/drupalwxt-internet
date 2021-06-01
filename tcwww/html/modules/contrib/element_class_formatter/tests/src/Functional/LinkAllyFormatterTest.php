<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines a class for testing LinkAllyFormatter.
 *
 * @group element_class_formatter
 */
class LinkAllyFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-link-class';

  /**
   * Test formatter with link field.
   *
   * @dataProvider providerLinkText
   */
  public function testLinkAllyFormatterLinkField(string $link_text = NULL, string $wrapper = '') {
    $field_config = $this->createEntityField('link_ally_class', 'link', [
      'class' => self::TEST_CLASS,
      'link_text' => $link_text,
      'screenreader_text' => 'about [entity_test:name]',
      'tag' => $wrapper,
    ]);

    $anchor_text = $this->randomMachineName();
    $entity = EntityTest::create([
      $field_config->getName() => [
        [
          'uri' => 'https://drupal.org',
          'title' => $anchor_text,
        ],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $selector = 'a.' . self::TEST_CLASS;
    if ($wrapper) {
      $selector = "$wrapper $selector";
    }
    $element = $assert_session->elementExists('css', $selector);
    $assert_session->elementTextContains('css', $selector, $link_text ?: $anchor_text);
    $assert_session->elementExists('css', sprintf('span.visually-hidden:contains("about %s")', $entity->label()), $element);
  }

  /**
   * Test formatter with string field.
   *
   * @dataProvider providerLinkText
   */
  public function testLinkAllyFormatterStringField(string $link_text = NULL, string $wrapper = '') {
    $field_config = $this->createEntityField('link_ally_class', 'string', [
      'class' => self::TEST_CLASS,
      'link_text' => $link_text,
      'screenreader_text' => 'about [entity_test:name]',
      'tag' => $wrapper,
    ]);

    $field_value = $this->randomMachineName();
    $entity = EntityTest::create([
      $field_config->getName() => $field_value,
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $selector = 'a.' . self::TEST_CLASS;
    if ($wrapper) {
      $selector = "$wrapper $selector";
    }
    $element = $assert_session->elementExists('css', $selector);
    $assert_session->elementTextContains('css', $selector, $link_text ?: $field_value);
    $assert_session->elementExists('css', sprintf('span.visually-hidden:contains("about %s")', $entity->label()), $element);
  }

  /**
   * Data provider for link text.
   *
   * @return array
   *   Test cases.
   */
  public function providerLinkText() {
    return [
      'use field value' => [],
      'use custom' => ['Read more'],
      'use wrapper' => ['Read more', 'p'],
    ];
  }

}
