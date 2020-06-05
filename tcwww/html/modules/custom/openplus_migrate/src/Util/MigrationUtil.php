<?php

namespace Drupal\openplus_migrate\Util;

use Drupal\migrate_plus\Entity\Migration;
use Drupal\rest\ResourceResponse;

class MigrationUtil {
  public static function GetMigrationStat($migration) {
    $output = array();
    $output['status'] = $migration->getStatusLabel();
    $source_plugin = $migration->getSourcePlugin();
    $output['total'] = $source_plugin->count();
    $map = $migration->getIdMap();
    $output['imported'] = $map->importedCount();

    // -1 indicates uncountable sources.
    if ($output['total'] == -1) {
      $output['total'] = t('N/A');
      $output['unprocessed'] = t('N/A');
    }
    else {
      $output['unprocessed'] = $output['total'] - $map->processedCount();
    }

    $migrate_last_imported_store = \Drupal::keyValue('migrate_last_imported');
    $last_imported = $migrate_last_imported_store->get($migration->id(), FALSE);
    if ($last_imported) {
      /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
      $date_formatter = \Drupal::service('date.formatter');
      $output['last_imported'] = $date_formatter->format($last_imported / 1000,
        'custom', 'Y-m-d H:i:s');
    }
    else {
      $output['last_imported'] = '';
    }

    return $output;
  }

  /**
   * @param string $uuid
   * @return string
   */
  public static function GetNodeMigrationId($uuid) {
    $id = [
      'maas',
      'nd',
      'en',
      str_replace('-', '_', $uuid), // Replace - with _ for db compatibility
    ];
    return implode('__', $id);
  }

  /**
   * @param string $uuid
   * @return string
   */
  public static function GetNodeTranslationMigrationId($uuid) {
    $id = [
      'maas',
      'nd',
      'fr',
      str_replace('-', '_', $uuid), // Replace - with _ for db compatibility
    ];
    return implode('__', $id);
  }

  /**
   * @param string $id
   * @param bool   $doReportNotFoundAsOk
   * @return \Drupal\rest\ResourceResponse
   */
  public static function DeleteMigration($id, $doReportNotFoundAsOk = FALSE) {
    $migration = Migration::load($id);

    if ($migration) {
      $migration->delete();
      $response = ['message' => 'Deleted migration ID: ' . $id];
      return new ResourceResponse($response);
    }

    $response = ['message' => 'Migration ID ' . $id . ' not found to be deleted.'];
    $status   = $doReportNotFoundAsOk ? 200 : 500;
    return new ResourceResponse($response, $status);
  }
}
