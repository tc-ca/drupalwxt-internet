<?php

namespace Drupal\blazy\Dejavu;

use Drupal\blazy\BlazyDefault as BlazyNewDefault;

@trigger_error('The ' . __NAMESPACE__ . '\BlazyDefault is deprecated in blazy:8.x-1.0 and is removed from blazy:8.x-2.0. Use \Drupal\blazy\BlazyDefault instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * @deprecated in blazy:8.x-1.0 and is removed from blazy:8.x-2.0. Use
 *   \Drupal\blazy\BlazyDefault instead.
 * @see https://www.drupal.org/node/3103018
 */
class BlazyDefault extends BlazyNewDefault {}
