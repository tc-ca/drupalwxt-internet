<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Trait common for all blazy formatters using oembed.
 */
trait BlazyFormatterOEmbedTrait {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'entity');
  }

}
