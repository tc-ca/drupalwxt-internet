<?php

namespace Drupal\redis\Client;

use Drupal\redis\ClientFactory;
use Drupal\redis\ClientInterface;
use Predis\Client;


/**
 * Predis client specific implementation.
 */
class Predis implements ClientInterface {

  public function getClient($host = NULL, $port = NULL, $base = NULL, $password = NULL, $scheme = NULL, $persistent = NULL, $replicationHosts = NULL) {
    $connectionInfo = [
      'password' => $password,
      'host'     => $host,
      'port'     => $port,
      'database' => $base,
      'scheme'   => $scheme,
      'persistent' => $persistent
    ];

    foreach ($connectionInfo as $key => $value) {
      if (!isset($value)) {
        unset($connectionInfo[$key]);
      }
    }

    // I'm not sure why but the error handler is driven crazy if timezone
    // is not set at this point.
    // Hopefully Drupal will restore the right one this once the current
    // account has logged in.
    date_default_timezone_set(@date_default_timezone_get());

    // If we are passed in an array of $replicationHosts, we should attempt a clustered client connection.
    if ($replicationHosts !== NULL) {
      $persistent_value = isset($connectionInfo['persistent']) ? 'persistent='. $connectionInfo['persistent'].'&' : '?';
      $parameters = [];

      foreach ($replicationHosts as $replicationHost) {
        if (!isset($replicationHost['scheme']) || empty($replicationHost['scheme'])) {
          $replicationHost['scheme'] = ClientFactory::REDIS_DEFAULT_SCHEME;
        }
        // Configure master.
        if ($replicationHost['role'] === 'primary') {
          $parameters[] = $replicationHost['scheme'] . '://' . $replicationHost['host'] . ':' . $replicationHost['port'] . $persistent_value .'alias=master';
        }
        else {
          $parameters[] = $replicationHost['scheme'] . '://' . $replicationHost['host'] . ':' . $replicationHost['port'] . $persistent_value;
        }
      }

      $options = ['replication' => true];
      $client = new Client($parameters, $options);
    }
    else {
      $client = new Client($connectionInfo);
    }
    return $client;

  }

  public function getName() {
    return 'Predis';
  }
}
