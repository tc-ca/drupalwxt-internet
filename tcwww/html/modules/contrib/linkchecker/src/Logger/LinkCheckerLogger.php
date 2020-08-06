<?php

namespace Drupal\linkchecker\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Class LinkCheckerLogger.
 *
 * This class overrides the default logging behaviour and makes it possible
 * to configure which linkchecker messages should be logged.
 *
 * @package Drupal\linkchecker\Logger
 */
class LinkCheckerLogger extends LoggerChannel {

  /**
   * The link checker settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $linkCheckerSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct($channel, ConfigFactoryInterface $configFactory) {
    parent::__construct($channel);

    $this->linkCheckerSettings = $configFactory->get('linkchecker.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if ($this->levelTranslation[$level] <= $this->linkCheckerSettings->get('logging.level')) {
      parent::log($level, $message, $context);
    }
  }

}
