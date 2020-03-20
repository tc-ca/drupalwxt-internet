<?php

namespace Drupal\mini_layouts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManager;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MiniLayout
 *
 * @Block(
 *   id = "mini_layout",
 *   deriver = "Drupal\mini_layouts\Plugin\Deriver\MiniLayoutBlockDeriver",
 * )
 *
 * @package Drupal\mini_layouts\Plugin\Block
 */
class MiniLayout extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.layout_builder.section_storage')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, SectionStorageManagerInterface $section_storage_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->sectionStorageManager = $section_storage_manager;
  }

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    /** @var \Drupal\mini_layouts\Entity\MiniLayout $mini_layout */
    $mini_layout = $this->entityTypeManager
      ->getStorage('mini_layout')
      ->load($this->getPluginDefinition()['mini_layout']);

    $contexts = $this->getContexts();
    $contexts['display'] = EntityContext::fromEntity($mini_layout);
    $contexts['layout_builder.entity'] = EntityContext::fromEntity($mini_layout);

    // Get section storage to pass to contexts hook.
    $cacheability = new CacheableMetadata();
    $storage = $this->sectionStorageManager->findByContext($contexts, $cacheability);

    // Allow modules to alter the contexts available. Pass the section storage
    // as context so that DefaultsSectionStorage's thirdPartySettings can be
    // used to influence contexts.
    \Drupal::moduleHandler()->alter('layout_builder_view_context', $contexts, $storage);

    $build = [];
    foreach ($storage->getSections() as $delta => $section) {
      $build[$delta] = $section->toRenderArray($contexts);
    }

    return  $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }
}
