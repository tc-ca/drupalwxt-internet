<?php

/**
 * @file
 * Business Rules module integration with Drush 8 and earlier.
 */

use Drupal\business_rules\Events\BusinessRulesDrushEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Implements hook_drush_init().
 */
function business_rules_drush_init() {
  if (\Drupal::hasService('event_dispatcher')) {
    \Drupal::service('event_dispatcher')->dispatch(BusinessRulesDrushEvent::DRUSHINIT, new Event());
  }
}
