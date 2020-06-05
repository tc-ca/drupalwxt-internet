<?php

namespace Drupal\openplus_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for Post Processing of content.
 *
 * @MigrateSource(
 *   id = "post_process_all_nodes"
 * )
 */
class PostProcessAllNodes extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $migration_table = 'migrate_map_maas__nd__en__7359480d_4fb9_411b_a2c5_64acc6af21e8';

    $query = $this->select('node', 'n');
    $schema = \Drupal::database()->schema();
    // if the migration has not run once this table will not exist and the admin migration page will whitescreen
    if ($schema->tableExists($migration_table)) {
      $query->innerJoin($migration_table, 'm', 'n.nid = m.destid1');
    }

    $query ->fields('n',
      [
        'nid',
        'vid',
        'langcode',
        'type',
      ]);
    $query->join('node__body', 'nb', 'n.nid = nb.entity_id');
    $query->condition('nb.body_value', '%NJS_%', 'LIKE');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'vid' => $this->t('Revision ID'),
      'langcode' => $this->t('Language'),
      'type' => $this->t('Type'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Translation support.
    if (!empty($row->getSourceProperty('translations'))) {
      $row->setSourceProperty('langcode', 'fr');
    }

    // Title Field.
    $title = $this->select('node_field_data', 'fd')
      ->fields('fd', ['title'])
      ->condition('nid', $row->getSourceProperty('nid'))
      ->condition('vid', $row->getSourceProperty('vid'))
      ->condition('langcode', $row->getSourceProperty('langcode'))
      ->condition('type', $row->getSourceProperty('type'))
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('node__body', 'fd')
      ->fields('fd', ['body_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('langcode', $row->getSourceProperty('langcode'))
      ->condition('bundle', $row->getSourceProperty('type'))
      ->execute()
      ->fetchCol();

    if (!empty($title[0])) {
      $row->setSourceProperty('title', $title[0]);
    }
    elseif (!empty($row->getSourceProperty('translations'))) {
      return FALSE;
    }
    $row->setSourceProperty('body', $body[0]);

    return parent::prepareRow($row);
  }

}
