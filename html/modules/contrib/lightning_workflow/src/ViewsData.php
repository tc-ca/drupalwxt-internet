<?php

namespace Drupal\lightning_workflow;

/**
 * Provides data to Views (i.e., via hook_views_data()).
 *
 * @internal
 *   This is an internal part of Lightning Workflow's integration with Views and
 *   may be changed or removed at any time. External code should not use or
 *   extend this class in any way!
 */
class ViewsData {

  /**
   * Returns all relevant data for Views.
   *
   * @return array
   *   The data exposed to Views, in the format expected by hook_views_data().
   */
  public function getAll() {
    return [];
  }

}
