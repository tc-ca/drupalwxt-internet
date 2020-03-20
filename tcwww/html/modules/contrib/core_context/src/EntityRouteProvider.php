<?php

namespace Drupal\core_context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides contexts stored in an entity when viewing it at its canonical route.
 */
final class EntityRouteProvider implements ContextProviderInterface {

  use CacheableContextTrait;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  private $entityDisplayRepository;

  /**
   * EntityRouteProvider constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * Extracts contexts from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which to extract contexts.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   The contexts extracted from the entity.
   */
  private function getContextsFromEntity(EntityInterface $entity) {
    $entity_type = $entity->getEntityType();

    if ($entity_type->hasHandlerClass('context')) {
      return $this->entityTypeManager->getHandler($entity_type->id(), 'context')
        ->getContexts($entity);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $contexts = [];

    $route = $this->routeMatch->getRouteObject();

    $ignore_routes = ['page_manager_page_variant'];
    if ($route && !in_array($route->hasDefault('_entity_view'), $ignore_routes) && ($route->hasDefault('_entity_view') || $route->hasDefault('_core_context_entity'))) {
      list ($entity_type_id, $view_mode) = explode('.', $route->getDefault('_entity_view') ?: $route->getDefault('_core_context_entity'));

      $entity = $this->routeMatch->getParameter($entity_type_id);
      $contexts = array_merge($contexts, $this->getContextsFromEntity($entity));

      $display = $this->getViewDisplay($entity, $view_mode);
      $contexts = array_merge($contexts, $this->getContextsFromEntity($display));
    }

    // Don't return contexts that aren't being specifically requested.
    if ($unqualified_context_ids) {
      $contexts = array_intersect_key($contexts, array_flip($unqualified_context_ids));
    }


    // Since the current route determines whether or not we have an entity
    // from which to extract contexts, we need to add the route cache context.
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheContexts(['route']);

    return $this->applyCaching($contexts, $cache_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

  /**
   * Returns an entity view display for a specific entity type and view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to retrieve the view display.
   * @param string $view_mode
   *   The desired view mode of the view display.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The entity view display.
   *
   * @todo Remove this when Drupal 8.8.0 is the minimum supported version.
   */
  private function getViewDisplay(EntityInterface $entity, $view_mode) {
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    if ($this->entityDisplayRepository instanceof EntityDisplayRepository && method_exists($this->entityDisplayRepository, 'getViewDisplay')) {
      return $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
    }
    else {
      return entity_get_display($entity_type_id, $bundle, $view_mode);
    }
  }

}
