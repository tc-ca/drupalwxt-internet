<?php

namespace Drupal\theme_switcher;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a theme_switcher_rule entity.
 */
interface ThemeSwitcherRuleInterface extends ConfigEntityInterface {

  /**
   * Gets the sort weight of the switch theme rule.
   *
   * @return int
   *   The switchThemeRule record sort weight.
   */
  public function getWeight();

  /**
   * Gets the theme to apply.
   *
   * @return string
   *   The switch theme rule theme.
   */
  public function getTheme();

  /**
   * Gets the admin theme to apply.
   *
   * @return string
   *   The switch theme rule admin theme.
   */
  public function getAdminTheme();

  /**
   * Return the switchers.
   *
   * @return array
   *   The switchers.
   */
  public function getVisibility();

}
