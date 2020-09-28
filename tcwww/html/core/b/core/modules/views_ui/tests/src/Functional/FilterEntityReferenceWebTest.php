<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\node\NodeInterface;

/**
 * Test the entity reference filter UI.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\filter\EntityReference
 */
class FilterEntityReferenceWebTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Entity type and referenceable type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $entityType;

  /**
   * Referenceable entity type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $referenceableType;

  /**
   * Referenceable content.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * Content containing fields as reference.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $containingNodes;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Create an entity type, and a referenceable type. Since these are coded
    // into the test view, they are not randomly named.
    $this->entityType = $this->drupalCreateContentType(['type' => 'page']);
    $this->referenceableType = $this->drupalCreateContentType(['type' => 'article']);

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'node',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'bundle' => $this->entityType->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          // Note, this has no impact on Views at this time.
          'target_bundles' => [
            $this->referenceableType->id() => $this->referenceableType->label(),
          ],
        ],
      ],
    ]);
    $field->save();

    // Create 10 referenceable nodes.
    for ($i = 0; $i < 10; $i++) {
      $node = $this->drupalCreateNode(['type' => $this->referenceableType->id()]);
      $this->nodes[$node->id()] = $node;
    }

    $node = $this->drupalCreateNode(['type' => $this->entityType->id()]);
    $this->containingNodes = [
      $node->id() => $node,
    ];
  }

  /**
   * Tests the filter UI.
   */
  public function testFilterUi() {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');

    $options = $this->getUiOptions();
    // Should be sorted by title ASC.
    uasort($this->nodes, function (NodeInterface $a, NodeInterface $b) {
      return strnatcasecmp($a->getTitle(), $b->getTitle());
    });
    $found_all = TRUE;
    $i = 0;
    foreach ($this->nodes as $nid => $node) {
      $option = $options[$i];
      $label = $option['label'];
      $found_all = $found_all && $label == $node->label() && $nid == $option['nid'];
      $this->assertEqual($label, $node->label(), new FormattableMarkup('Expected referenceable label found for option :option', [':option' => $i]));
      $i++;
    }
    $this->assertTrue($found_all, 'All referenceable nodes were available as a select list properly ordered.');

    // Change the sort field and direction.
    $edit = [
      'options[handler_settings][sort][field]' => 'nid',
      'options[handler_settings][sort][direction]' => 'DESC',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler-extra/test_filter_entity_reference/default/filter/field_test_target_id', $edit, t('Apply'));

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    // Items should now be in reverse nid order.
    krsort($this->nodes);
    $options = $this->getUiOptions();
    $found_all = TRUE;
    $i = 0;
    foreach ($this->nodes as $nid => $node) {
      $option = $options[$i];
      $label = $option['label'];
      $found_all = $found_all && $label == $node->label() && $nid == $option['nid'];
      $this->assertEqual($label, $node->label(), new FormattableMarkup('Expected referenceable label found for option :option', [':option' => $i]));
      $i++;
    }
    $this->assertTrue($found_all, 'All referenceable nodes were available as a select list properly ordered.');

    // Change bundle types.
    $edit = [
      "options[handler_settings][target_bundles][{$this->entityType->id()}]" => TRUE,
      "options[handler_settings][target_bundles][{$this->referenceableType->id()}]" => TRUE,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler-extra/test_filter_entity_reference/default/filter/field_test_target_id', $edit, t('Apply'));

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    $options = $this->getUiOptions();
    $found_all = TRUE;
    $i = 0;
    foreach ($this->containingNodes + $this->nodes as $nid => $node) {
      $option = $options[$i];
      $label = $option['label'];
      $found_all = $found_all && $label == $node->label() && $nid == $option['nid'];
      $this->assertEqual($label, $node->label(), new FormattableMarkup('Expected referenceable label found for option :option', [':option' => $i]));
      $i++;
    }
    $this->assertTrue($found_all, 'All referenceable nodes were available from both bundles.');

    // Reduce maximum select items, so the widget automatically switches to an
    // autocomplete.
    $edit = [
      'options[list_max]' => 2,
      'options[handler_settings][target_bundles][page]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler-extra/test_filter_entity_reference/default/filter/field_test_target_id', $edit, t('Apply'));

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    $autocompletes = $this->xpath('//input[@name="options[value]"]');
    $this->assertNotEmpty($autocompletes, 'Autocomplete filter field found when select threshold exceeded.');
    $this->assertNotEmpty($autocompletes[0]->getAttribute('data-autocomplete-path'), 'Autocomplete path was set on filter field');

    // Now explicitly change widget mode.
    $edit = [
      'options[widget]' => 'autocomplete',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler-extra/test_filter_entity_reference/default/filter/field_test_target_id', $edit, t('Apply'));

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    $autocompletes = $this->xpath('//input[@name="options[value]"]');
    $this->assertNotEmpty($autocompletes, 'Autocomplete filter field found when widget set to autocomplete');
    $this->assertNotEmpty($autocompletes[0]->getAttribute('data-autocomplete-path'), 'Autocomplete path was set on filter field');
  }

  /**
   * Helper method to parse options from the UI.
   *
   * @return array
   *   Array of keyed arrays containing `nid` and `label` of each option.
   */
  protected function getUiOptions() {
    /** @var \Behat\Mink\Element\NodeElement[] $result */
    $result = $this->xpath('//select[@name="options[value][]"]/option');
    $this->assertNotEmpty($result, 'Options found');

    $options = [];
    foreach ($result as $option) {
      $nid = (int) $option->getValue();
      $options[] = [
        'nid' => $nid,
        'label' => (string) $this->getSession()->getDriver()->getText($option->getXpath()),
      ];
    }

    return $options;
  }

}
