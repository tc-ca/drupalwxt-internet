<?php

namespace Drupal\openplus\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
<<<<<<< HEAD
use Drupal\Core\TypedData\ComplexDataInterface;
=======
>>>>>>> a4bb16d0039ca3994a8d24861fa1c07b22f60dfd

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "book_parent",
 *   label = @Translation("Book parent"),
 *   description = @Translation("Adds the item's book parent to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class BookParent extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Book parent'),
        'description' => $this->t('The parent book of the node if it is part of a book.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_book_parent'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    // Only run for node
    $entity_type_id = $item->getDatasource()->getEntityTypeId();
    if ($entity_type_id != 'node') {
      return;
    }

    // Get the node object.
    $node = $item->getOriginalObject()->getValue();
    if (!$node) {
      // Apparently we were active for a wrong item.
      return;
    }

    if (isset($node->book) && !empty($node->book) && $node->book['bid'] != $node->id()) {
      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'search_api_book_parent');

      foreach ($fields as $field) {
        $field->addValue($node->book['bid']);
      }
    }
  }

}
