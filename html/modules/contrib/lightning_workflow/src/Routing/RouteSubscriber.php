<?php

namespace Drupal\lightning_workflow\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\lightning_workflow\Controller\PanelizerIPEController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Reacts to routing events.
 *
 * @internal
 *   This is an internal part of Lightning Workflow's integration with Panelizer
 *   and may be changed or removed at any time. External code should not use
 *   or extend this class in any way!
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('panelizer.panels_ipe.revert_to_default');
    if ($route) {
      $route->setDefault('_controller', PanelizerIPEController::class . '::revertToDefault');
    }
  }

}
