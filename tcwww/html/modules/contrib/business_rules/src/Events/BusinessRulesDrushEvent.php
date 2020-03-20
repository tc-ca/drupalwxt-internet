<?php

namespace Drupal\business_rules\Events;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event that is fired when cron maintenance tasks are performed.
 *
 * @see business_rules_cron()
 */
class BusinessRulesDrushEvent extends GenericEvent {

  const DRUSHINIT = 'business_rules_drush_cron';

}
