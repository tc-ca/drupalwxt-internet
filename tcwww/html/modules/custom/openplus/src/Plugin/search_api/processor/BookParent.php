<?php

namespace Drupal\openplus\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

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
        'type' => 'string',
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
    $node = $this->getNode($item->getOriginalObject());
    if (!$node) {
      // Apparently we were active for a wrong item.
      return;
    }

    if (isset($node->book) && !empty($node->book) && $node->book['bid'] != $node->id()) {

/*
      $book =  \Drupal::entityTypeManager()->getStorage('node')->load($node->book['bid']);
      if ($book->hasTranslation($langcode)) {
        $book = $book->getTranslation($langcode);
      }
        if ($book) {
          $build['book_title']['#markup'] = '<div class="h4">' . $book->getTitle() . '</div>';
        }
      } 
*/

      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'search_api_book_parent');
      foreach ($fields as $field) {
        $field->addValue($node->book['bid']);
      }
    }
  }

}
