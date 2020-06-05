<?php

namespace Drupal\openplus_migrate\Util;

class ConfigUtil {
  const CONFIG_NAME = 'openplus_migrate.settings';
  const HARVESTER_API_URL_SETTING = 'harvester_api_url';
  const HARVESTER_DOMAIN_SETTING = 'harvester_domain';
  const HARVESTER_PORT_SETTING = 'harvester_port';
  const HARVESTER_USER_SETTING = 'harvester_user';
  const HARVESTER_PASSWORD_SETTING = 'harvester_password';


  private static function GetConfig() {
    return \Drupal::config(self::CONFIG_NAME);
  }


  /**
   * @return string
   */
  public static function GetHarvesterBaseUrl() {
    $config = self::GetConfig();
    if (!empty(self::GetHarvesterPort())) {
      $url = self::GetHarvesterDomain() . ':' . self::GetHarvesterPort() . self::GetHarvesterApiUrl();
     }
     else {
       $url = self::GetHarvesterDomain() . self::GetHarvesterApiUrl();
     }

     return rtrim($url, '/') . '/';
  }

  /**
   * @return string
   */
  public static function GetHarvesterApiUrl() {
    $defaultUrl = '/api/v1'; // To be used only if settings form has not been submitted yet.
    $config = self::GetConfig();
    $path = $config->get(self::HARVESTER_API_URL_SETTING) ?? $defaultUrl;
    return $path;
  }

  /**
   * @return string
   */
  public static function GetHarvesterDomain() {
    $defaultUrl = 'https://gccloud.ca'; // To be used only if settings form has not been submitted yet.
    $config = self::GetConfig();
    $domain = $config->get(self::HARVESTER_DOMAIN_SETTING) ?? $defaultUrl;
    return $domain;
  }

  public static function GetHarvesterPort() {
    $config = self::GetConfig();
    $port = $config->get(self::HARVESTER_PORT_SETTING) ?? NULL;
    return $port;
  }

  public static function GetHarvesterUser() {
    $config = self::GetConfig();
    $user = $config->get(self::HARVESTER_USER_SETTING) ?? NULL;
    return $user;
  }

  public static function GetHarvesterPass() {
    $config = self::GetConfig();
    $pass = $config->get(self::HARVESTER_PASSWORD_SETTING) ?? NULL;
    return $pass;
  }
}
