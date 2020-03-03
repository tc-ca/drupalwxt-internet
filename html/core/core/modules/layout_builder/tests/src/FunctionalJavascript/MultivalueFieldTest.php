<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;

/**
 * Test display of fields with multiple values.
 *
 * @group layout_builder
 */
class MultivalueFieldTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * Path prefix for the field UI for the test bundle.
   *
   * @var string
   */
  const FIELD_UI_PREFIX = 'admin/structure/types/manage/bundle_with_section_field';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'node',
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'bundle_with_section_field']);
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
      'access contextual links',
    ]));

    $this->drupalPostForm(static::FIELD_UI_PREFIX . '/display/default', ['layout[enabled]' => TRUE], 'Save');
    $this->drupalPostForm(static::FIELD_UI_PREFIX . '/display/default', ['layout[allow_custom]' => TRUE], 'Save');

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'multi_textfield',
      'type' => 'text',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => 'multi_textfield',
      'entity_type' => 'node',
      'bundle' => 'bundle_with_section_field',
    ])->save();
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'bundle_with_section_field', 'default')
      ->setComponent('multi_textfield', ['type' => 'string'])
      ->save();

    $node = Node::create([
      'type' => 'bundle_with_section_field',
      'title' => 'Kicker of Elves',
    ]);
    $node->multi_textfield->appendItem('The first item');
    $node->multi_textfield->appendItem('The second item');
    $node->multi_textfield->appendItem('The third item');
    $node->multi_textfield->appendItem('The fourth item');
    $node->multi_textfield->appendItem('The fifth item');
    $node->multi_textfield->appendItem('The sixth item');
    $node->multi_textfield->appendItem('The seventh item');
    $node->save();
  }

  /**
   * Tests display of partial contents of multivalue fields.
   *
   * @dataProvider testMultiValueFieldProvider
   */
  public function testMultiValueField($params) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $multiple_field_items = [
      'The first item',
      'The second item',
      'The third item',
      'The fourth item',
      'The fifth item',
      'The sixth item',
      'The seventh item',
    ];

    $should_not_be_visible = array_diff($multiple_field_items, $params['should_be_visible']);

    $this->drupalGet('node/1/layout');

    foreach ($multiple_field_items as $item) {
      $assert_session->pageTextContains($item);
    }

    $this->clickContextualLink('.block-field-blocknodebundle-with-section-fieldmulti-textfield', 'Configure');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $page->selectFieldOption('settings[multivalue_wrapper][display_items]', $params['display_items']);
    $this->assertTrue($assert_session->waitForElementVisible('css', '[name="settings[multivalue_wrapper][items_to_display]"]'));
    $page->fillField('settings[multivalue_wrapper][items_to_display]', $params['items_to_display']);
    $page->fillField('settings[multivalue_wrapper][offset]', $params['offset']);
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');

    foreach ($params['should_be_visible'] as $item) {
      $assert_session->pageTextContains($item);
    }
    foreach ($should_not_be_visible as $item) {
      $assert_session->pageTextNotContains($item);
    }

    $page->pressButton('Save layout');

    foreach ($params['should_be_visible'] as $item) {
      $assert_session->pageTextContains($item);
    }
    foreach ($should_not_be_visible as $item) {
      $assert_session->pageTextNotContains($item);
    }
  }

  /**
   * Provider for testMultiValueField().
   */
  public function testMultiValueFieldProvider() {
    return [
      'offset' => [
        'params' => [
          'display_items' => 'display_some',
          'items_to_display' => 2,
          'offset' => 2,
          'should_be_visible' => [
            'The third item',
            'The fourth item',
          ],
        ],
      ],
      'no_offset' => [
        'params' => [
          'display_items' => 'display_some',
          'items_to_display' => 3,
          'offset' => 0,
          'should_be_visible' => [
            'The first item',
            'The second item',
            'The third item',
          ],
        ],
      ],
      'offset_too_high' => [
        'params' => [
          'display_items' => 'display_some',
          'items_to_display' => 3,
          'offset' => 7,
          'should_be_visible' => [],
        ],
      ],
    ];
  }

}
