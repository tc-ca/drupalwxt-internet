<?php

namespace Drupal\linkchecker\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\linkchecker\LinkCheckerBatch;
use Drupal\linkchecker\LinkCleanUp;
use Drupal\linkchecker\LinkExtractorBatch;
use Drush\Commands\DrushCommands;
use Psr\Log\LoggerInterface;

/**
 * Drush 9 commands for Linkchecker module.
 */
class LinkCheckerCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The linkchecker settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $linkcheckerSetting;

  /**
   * The logger.channel.linkchecker service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The extractor batch helper.
   *
   * @var \Drupal\linkchecker\LinkExtractorBatch
   */
  protected $extractorBatch;

  /**
   * The checker batch helper.
   *
   * @var \Drupal\linkchecker\LinkCheckerBatch
   */
  protected $checkerBatch;

  /**
   * The link clean up.
   *
   * @var \Drupal\linkchecker\LinkCleanUp
   */
  protected $linkCleanUp;

  /**
   * LinkCheckerCommands constructor.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
    LinkExtractorBatch $extractorBatch,
    LinkCheckerBatch $checkerBatch,
    LinkCleanUp $linkCleanUp
  ) {
    parent::__construct();
    $this->linkcheckerSetting = $configFactory->get('linkchecker.settings');;
    $this->logger = $logger;
    $this->extractorBatch = $extractorBatch;
    $this->checkerBatch = $checkerBatch;
    $this->linkCleanUp = $linkCleanUp;
  }

  /**
   * Reanalyzes content for links. Recommended after module has been upgraded.
   *
   * @command linkchecker:analyze
   *
   * @aliases lca
   */
  public function analyze() {
    $this->analyzeCheckParams();

    $total = $this->extractorBatch->getTotalEntitiesToProcess();
    if (!empty($total)) {
      $this->extractorBatch->batch();
      drush_backend_batch_process();
    }
    else {
      $this->logger()->warning('No content configured for link analysis.');
    }
  }

  /**
   * Clears all link data and analyze content for links.
   *
   * WARNING: Custom link check settings are deleted.
   *
   * @command linkchecker:clear
   *
   * @aliases lccl
   */
  public function clear() {
    $this->analyzeCheckParams();

    $total = $this->extractorBatch->getTotalEntitiesToProcess();
    if (!empty($total)) {
      $this->linkCleanUp->removeAllBatch();
      $this->extractorBatch->batch();
      drush_backend_batch_process();
    }
    else {
      $this->logger()->warning('No content configured for link analysis.');
    }
  }

  /**
   * Check link status.
   *
   * @command linkchecker:check
   *
   * @aliases lcch
   */
  public function check() {
    $this->logger()->info('Starting link checking...');

    // Always rebuild queue on Drush command run to check all links.
    if (empty($this->checkerBatch->getTotalLinksToProcess())) {
      $this->logger
        ->notice($this->t('There are no links to check.'));
      return;
    }

    $this->checkerBatch->batch();
    drush_backend_batch_process();
  }

  /**
   * Throws an exception if base url is not set.
   *
   * @throws \Exception
   */
  protected function analyzeCheckParams() {
    $alias = $this->getConfig()->getContext('alias');
    $options = $alias->get('options');

    if (!isset($options['uri'])) {
      $httpProtocol = $this->linkcheckerSetting->get('default_url_scheme');
      $baseUrl = $httpProtocol . $this->linkcheckerSetting->get('base_path');
    }
    else {
      $baseUrl = $options['uri'];
    }

    if (empty($baseUrl)) {
      throw new \Exception($this->t('You MUST configure the site base_url or provide --uri parameter.'));
    }

    if (mb_strpos($baseUrl, 'http') !== 0) {
      throw new \Exception($this->t('Base url should start with http scheme (http:// or https://)'));
    }
  }

}
