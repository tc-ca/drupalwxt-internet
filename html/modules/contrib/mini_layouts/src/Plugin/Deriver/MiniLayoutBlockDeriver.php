<?php

namespace Drupal\mini_layouts\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MiniLayoutBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * MiniLayoutBlockDeriver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param array $base_plugin_definition
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!empty($this->derivatives)) {
      return $this->derivatives;
    }

    foreach ($this->entityTypeManager->getStorage('mini_layout')->loadMultiple() as $id => $mini_layout) {
      $definition = $base_plugin_definition;
      $definition['mini_layout'] = $id;
      $definition['admin_label'] = $mini_layout->admin_label;
      $definition['category'] = $mini_layout->category ?: 'Layouts';
      $definition['context'] = [];
      foreach ($mini_layout->required_context as $name => $info) {
        $definition['context'][$name] = new ContextDefinition($info['type'], $info['label'], !empty($info['required']));
      }
      $this->derivatives[$id] = $definition;
    }

    return $this->derivatives;
  }
}
