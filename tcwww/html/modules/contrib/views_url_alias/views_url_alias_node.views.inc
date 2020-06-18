<?php

/**
 * @file
 * Provide views data and handler information for Views URL alias (node) module.
 */

/**
 * Implements hook_views_data().
 */
function views_url_alias_node_views_data() {
  // Define table group
  $data['views_url_alias_node']['table']['group'] = t('Content');
  $data['views_url_alias_node']['table']['provider'] = 'views_url_alias_node';

  $data['views_url_alias_node']['table']['base'] = array(
    'field' => 'nid',
    'title' => t('URL alias'),
    'help' => t('Mapping table contains node IDs and their associated path aliases.'),
    'weight' => -10,
  );

  $data['views_url_alias_node']['table']['join'] = array(
    'node_field_data' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['views_url_alias_node']['alias'] = array(
    'title' => t('URL alias'),
    'help' => t('The alias for a node.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  return $data;
}
