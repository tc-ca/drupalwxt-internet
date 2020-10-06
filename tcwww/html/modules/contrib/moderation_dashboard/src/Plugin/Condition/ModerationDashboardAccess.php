<?php

namespace Drupal\moderation_dashboard\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Moderation Dashboard Access' condition.
 *
 * @Condition(
 *   id = "moderation_dashboard_access",
 *   label = @Translation("Moderation Dashboard Access"),
 *   context_definitions = {
 *     "dashboard_user" = @ContextDefinition("entity:user", label = @Translation("Dashboard owner")),
 *     "current_user" = @ContextDefinition("entity:user", label = @Translation("Current user")),
 *   }
 * )
 */
class ModerationDashboardAccess extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  protected $userStorage;

  /**
   * Constructor for DI.
   *
   * @param array $configuration
   *   A config array.
   * @param string $plugin_id
   *   Contains plugin Id.
   * @param mixed $plugin_definition
   *   Contains plugin definition.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   Contains User entity storage interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserStorageInterface $user_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userStorage = $user_storage;
  }

  /**
   * Create method for DI.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Contains container interface object.
   * @param array $configuration
   *   A config array.
   * @param string $plugin_id
   *   Contains plugin Id.
   * @param mixed $plugin_definition
   *   Contains plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $dashboard_owner = $this->getContextValue('dashboard_user');
    $current_user = $this->getContextValue('current_user');
    if (is_string($dashboard_owner)) {
      $dashboard_owner = $this->userStorage->load($dashboard_owner);
    }
    // If the given user doesn't have a dashboard, nobody can view it.
    if (!$dashboard_owner->hasPermission('use moderation dashboard')) {
      return FALSE;
    }
    // If the current user is on their own dashboard, they can view it.
    if ($current_user->id() === $dashboard_owner->id()) {
      return TRUE;
    }
    // But they can only view the dashboard of others with another permission.
    return $current_user->hasPermission('view any moderation dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->isNegated()) {
      return $this->t("User can't access moderation dashboard.");
    }
    return $this->t('User can access moderation dashboard.');
  }

}
