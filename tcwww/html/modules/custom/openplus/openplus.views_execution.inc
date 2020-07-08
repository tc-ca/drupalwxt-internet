<?php
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;

/**
 * Implements hook_views_query_alter().
 */
function openplus_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  if ($view->id() == 'linked_nodes'&& $view->current_display == 'block_1') {
    $query->where[0]['conditions'][0]['operator'] = "ilike";
    $query->where[0]['conditions'][0]['value'] = '%node_' . $query->where[0]['conditions'][0]['value'] . '%'; 
  }
}
