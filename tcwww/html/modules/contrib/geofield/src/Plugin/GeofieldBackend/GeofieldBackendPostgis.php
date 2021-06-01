<?php

namespace Drupal\geofield\Plugin\GeofieldBackend;

use Drupal\geofield\Plugin\GeofieldBackendBase;

/**
 * PostgreSQL/PostGIS Backend for Geofield.
 *
 * Definition of a Geofield Backend storing values in EWKB Format, suitable for
 * PostgreSQL/PostGIS,
 *
 * @GeofieldBackend(
 *   id = "geofield_backend_postgis",
 *   admin_label = @Translation("Geofield PostgreSQL/PostGIS Backend")
 * )
 */
class GeofieldBackendPostgis extends GeofieldBackendBase {

  /**
   * {@inheritdoc}
   */
  public function schema() {
    return [
      'type' => 'geometry',
      'size' => 'big',
      'not null' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save($geometry) {
    $geom = $this->geoPhpWrapper->load($geometry);
    $unpacked = unpack('H*', $geom->out('ewkb'));
    return $unpacked[1];
  }

}
