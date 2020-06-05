<?php

namespace Drupal\openplus_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Fixes a date format if source is a recognizable date.
 *
 * Available configuration keys:
 * - source
 *
 * Examples:
 *
 * @code
 * process:
 *   new_field:
 *     plugin: openplus_fixdate
 *     source: some_field
 * @endcode
 *
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "openplus_fixdate",
 *   handle_multiples = TRUE
 * )
 */
class OpenplusFixdate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $url = $row->getSourceProperty('website');
    //\Drupal::logger('openplus_migrate')->notice('DEBUG:' . json_encode($value));
    if (!is_array($value)) {
      $t = strtotime($value);
      // convert value to a timestamp (will return false if it fails)
      if ($t) {
        $new_value = date('Y-m-d', $t);
      }
      else {
        // not a valid date so just return today as the date 
        $migrate_executable->saveMessage('Defaulting date on: ' . $url);
        $new_value = date('Y-m-d');
      }

      return $new_value;
    }

    return $value;
  }

}
