<?php

namespace Drupal\mini_layouts\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Class MiniLayoutListBuilder
 *
 *
 *
 * @package Drupal\mini_layouts\Entity
 */
class MiniLayoutListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = [
      'layout' => [
        'title' => new TranslatableMarkup('Manage Layout'),
        'weight' => 1,
        'url' => Url::fromRoute(
          'layout_builder.mini_layout.view',
          [ 'mini_layout' => $entity->id() ]
        ),
      ],
    ] + parent::getOperations($entity);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      $this->t('Admin Label'),
      $this->t('Category'),
      $this->t('Context'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc{
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['label'] = $entity->label();
    $row['category'] = $entity->category;
    $row['context'] = [
      'data' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [],
      ],
    ];
    foreach ($entity->required_context as $machine_name => $info) {
      $row['context']['data']['#items'][] = new TranslatableMarkup(
        '@label (@type)@optional',
        [
          '@label' => $info['label'],
          '@type' => $info['type'],
          '@optional' => empty($info['required']) ? ' [optional]' : '',
        ]
      );
    }
    return $row + parent::buildRow($entity);
  }

}
