<?php

namespace Drupal\theme_switcher\Entity;

use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\theme_switcher\ThemeSwitcherRuleInterface;

/**
 * Defines the Theme Switcher Rule entity.
 *
 * @ConfigEntityType(
 *   id = "theme_switcher_rule",
 *   label = @Translation("Theme Switcher Rule"),
 *   module = "theme_switcher",
 *   handlers = {
 *     "access" = "Drupal\theme_switcher\Access\ThemeSwitcherAccessControlHandler",
 *     "list_builder" = "Drupal\theme_switcher\Controller\ThemeSwitcherRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\theme_switcher\Form\ThemeSwitcherRuleForm",
 *       "edit" = "Drupal\theme_switcher\Form\ThemeSwitcherRuleForm",
 *       "delete" = "Drupal\theme_switcher\Form\ThemeSwitcherRuleDeleteForm"
 *     }
 *   },
 *   config_prefix = "rule",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "uuid",
 *     "id",
 *     "label",
 *     "weight",
 *     "status",
 *     "theme",
 *     "admin_theme",
 *     "visibility",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/theme_switcher/{rule}",
 *     "delete-form" = "/admin/config/theme_switcher/{rule}/delete",
 *   }
 * )
 */
class ThemeSwitcherRule extends ConfigEntityBase implements ThemeSwitcherRuleInterface, EntityWithPluginCollectionInterface {

  /**
   * The ID of the switch theme rule entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The switch theme rule label.
   *
   * @var string
   */
  protected $label;

  /**
   * The switch theme rule sort order.
   *
   * @var int
   */
  protected $weight;

  /**
   * The theme to apply.
   *
   * @var string
   */
  protected $theme;

  /**
   * The admin theme to apply.
   *
   * @var string
   */
  protected $admin_theme;

  /**
   * Switchers instance IDs.
   *
   * @var array
   */
  protected $visibility = [];

  /**
   * The plugin collection that holds the block plugin for this entity.
   *
   * @var \Drupal\block\BlockPluginCollection
   */
  protected $pluginCollection;

  /**
   * The visibility collection.
   *
   * @var \Drupal\Core\Condition\ConditionPluginCollection
   */
  protected $visibilityCollection;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->theme;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminTheme() {
    return $this->admin_theme;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility() {
    return $this->getVisibilityConditions()->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'visibility' => $this->getVisibilityConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibilityConditions() {
    if (!isset($this->visibilityCollection)) {
      $this->visibilityCollection = new ConditionPluginCollection(
        $this->conditionPluginManager(),
        $this->get('visibility')
      );
    }
    return $this->visibilityCollection;
  }

  /**
   * Gets the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  protected function conditionPluginManager() {
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

}
