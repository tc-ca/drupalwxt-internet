<?php

namespace Drupal\mini_layouts\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\Section;

/**
 * Class MiniLayoutStorage
 *
 * @package Drupal\mini_layouts\Entity
 */
class MiniLayoutStorage extends ConfigEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function mapToStorageRecord(EntityInterface $entity) {
    $record = parent::mapToStorageRecord($entity);

    /**
     * @var integer $delta
     * @var \Drupal\layout_builder\Section $section
     */
    foreach ($record['sections'] as $delta => $section) {
      $record['sections'][$delta] = $section->toArray();
    }

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records) {
    foreach ($records as $id => &$record) {
      if (!empty($record['sections'])) {
        $sections = &$record['sections'];
        $sections = array_map([Section::class, 'fromArray'], $sections);
      }
    }
    return parent::mapFromStorageRecords($records);
  }

}
