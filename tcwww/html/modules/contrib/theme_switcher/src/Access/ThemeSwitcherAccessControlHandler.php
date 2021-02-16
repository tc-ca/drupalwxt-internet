<?php

namespace Drupal\theme_switcher\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the theme_switcher_rule entity type.
 */
class ThemeSwitcherAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    $account = $this->prepareUser($account);

    // Check the global permission.
    if ($account->hasPermission('administer theme switcher rules')) {
      return AccessResult::allowed();
    }

    if ($operation == 'view' && $account->hasPermission('view theme switcher rules')) {
      return AccessResult::allowed();
    }
    elseif ($operation == 'update' && $account->hasPermission('edit theme switcher rules')) {
      return AccessResult::allowed();
    }
    elseif ($operation == 'delete' && $account->hasPermission('delete theme switcher rules')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
