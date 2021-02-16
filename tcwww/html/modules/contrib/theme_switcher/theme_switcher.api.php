<?php

/**
 * @file
 * API documentation file for the module.
 */

/**
 * Allows modules to modify the conditions available.
 *
 * This hook allows modules to unset some conditions to be unavailable on the
 * theme_switcher_rule form. Some conditions may not make sense.
 *
 * @param array $definitions
 *   An array with all available conditions.
 */
function hook_available_conditions_alter(array &$definitions) {
  foreach ($definitions as $condition_id => $definition) {
    // Don't use the current theme condition.
    if ($condition_id == 'current_theme') {
      unset($definitions[$condition_id]);
    }
  }
}
