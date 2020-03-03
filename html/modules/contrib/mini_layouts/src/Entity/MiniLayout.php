<?php

namespace Drupal\mini_layouts\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageTrait;

/**
 * Class MiniLayout
 *
 * @ConfigEntityType(
 *   id = "mini_layout",
 *   label = @Translation("Mini Layout"),
 *   label_collection = @Translation("Mini Layouts"),
 *   label_singular = @Translation("mini layout"),
 *   label_plural = @Translation("mini layouts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count mini layout",
 *     plural = "@count mini layouts",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\mini_layouts\Entity\MiniLayoutStorage",
 *     "access" = "Drupal\mini_layouts\Entity\MiniLayoutAccessControlHandler",
 *     "view_builder" = "Drupal\mini_layouts\Entity\MiniLayoutViewBuilder",
 *     "list_builder" = "Drupal\mini_layouts\Entity\MiniLayoutListBuilder",
 *     "form" = {
 *       "default" = "Drupal\mini_layouts\Form\MiniLayoutForm",
 *       "delete" = "Drupal\mini_layouts\Form\MiniLayoutDeleteForm",
 *       "layout_builder" = "Drupal\mini_layouts\Form\MiniLayoutLayoutBuilderForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     }
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/mini_layouts/manage/{mini_layout}/delete",
 *     "edit-form" = "/admin/structure/mini_layouts/manage/{mini_layout}",
 *     "add-form" = "/admin/structure/mini_layouts/add",
 *     "collection" = "/admin/structure/mini_layouts",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "admin_label"
 *   },
 *   admin_permission = "administer mini layouts",
 *   config_export = {
 *     "id",
 *     "admin_label",
 *     "category",
 *     "required_context",
 *     "sections",
 *     "locked",
 *   }
 * )
 *
 * @package Drupal\mini_layouts\Entity
 */
class MiniLayout extends ConfigEntityBase implements SectionListInterface {
  use SectionStorageTrait;

  public $admin_label;

  public $id;

  public $category;

  public $required_context = [];

  public $sections = [];

  public $locked;


  /**
   * Gets the layout sections.
   *
   * @return \Drupal\layout_builder\Section[]
   *   A sequentially and numerically keyed array of section objects.
   */
  public function getSections() {
    return $this->sections;
  }

  /**
   * Stores the information for all sections.
   *
   * Implementations of this method are expected to call array_values() to rekey
   * the list of sections.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   An array of section objects.
   *
   * @return $this
   */
  protected function setSections(array $sections) {
    $this->sections = array_values($sections);
    return $this;
  }
}
