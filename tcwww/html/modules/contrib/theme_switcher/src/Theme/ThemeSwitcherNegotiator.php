<?php

namespace Drupal\theme_switcher\Theme;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Negotiate the current theme based on theme_switcher_rules rules.
 */
class ThemeSwitcherNegotiator implements ThemeNegotiatorInterface {

  use ConditionAccessResolverTrait;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The theme to be applied.
   *
   * @var string
   */
  private $theme;

  /**
   * ThemeSwitcherNegotiator constructor.
   *
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(AdminContext $admin_context, EntityTypeManagerInterface $entity_type_manager, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository) {
    $this->adminContext = $admin_context;
    $this->entityTypeManager = $entity_type_manager;
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $storage = $this->entityTypeManager->getStorage('theme_switcher_rule');
    $configEntities = $storage->getQuery()->sort('weight', 'ASC')->execute();

    $rules = $storage->loadMultiple($configEntities);
    foreach ($rules as $rule) {
      /** @var \Drupal\theme_switcher\Entity\ThemeSwitcherRule $rule */

      // Check whether the rule is enabled and one of the themes is set.
      if ($rule->status() && (!empty($rule->getTheme()) || !empty($rule->getAdminTheme()))) {

        $conditions = [];
        foreach ($rule->getVisibilityConditions() as $condition_id => $condition) {
          if ($condition instanceof ContextAwarePluginInterface) {
            try {
              $contexts = $this->contextRepository->getRuntimeContexts(
                array_values($condition->getContextMapping())
              );
              $this->contextHandler->applyContextMapping($condition, $contexts);
            }
            catch (MissingValueContextException | ContextException $e) {
              // MissingValueContextException: If any context is missing then
              // we might be missing cacheable metadata, and don't know based
              // on what conditions the block is accessible or not. Make sure
              // the result cannot be cached.
              //
              // ContextException: The contexts exist but have no value. Deny
              // access without disabling caching. For example the node type
              // condition will have a missing context on any non-node route
              // like the frontpage.
              return FALSE;
            }
          }
          $conditions[$condition_id] = $condition;
        }

        // Check whether the conditions are resolved positively.
        if ($this->resolveConditions($conditions, 'and') !== FALSE) {

          // Are we in a admin route?
          $route = $route_match->getRouteObject();
          $is_admin_route = $this->adminContext->isAdminRoute($route);
          $this->theme = (!$is_admin_route) ? $rule->getTheme() : $rule->getAdminTheme();

          return (!empty($this->theme)) ? TRUE : FALSE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->theme;
  }

}
