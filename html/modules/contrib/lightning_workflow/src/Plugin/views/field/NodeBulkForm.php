<?php

namespace Drupal\lightning_workflow\Plugin\views\field;

use Drupal\node\Plugin\views\field\NodeBulkForm as BaseNodeBulkForm;

/**
 * Extends the node_bulk_form field plugin to disallow certain options.
 *
 * @internal
 *   This is an internal part of Lightning Workflow's integration with Views and
 *   may be changed or removed at any time. External code should not use or
 *   extend this class in any way!
 */
class NodeBulkForm extends BaseNodeBulkForm {

  /**
   * {@inheritdoc}
   */
  protected function getBulkOptions($filtered = TRUE) {
    $options = parent::getBulkOptions($filtered);
    unset($options['node_publish_action'], $options['node_unpublish_action']);

    return $options;
  }

}
