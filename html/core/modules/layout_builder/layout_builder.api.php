<?php

/**
 * @file
 * Hooks provided by the Layout Builder module.
 */

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * @defgroup layout_builder_access Layout Builder access
 * @{
 * In determining access rights for the Layout Builder UI,
 * \Drupal\layout_builder\Access\LayoutBuilderAccessCheck checks if the
 * specified section storage plugin (an implementation of
 * \Drupal\layout_builder\SectionStorageInterface) grants access.
 *
 * By default, the Layout Builder access check requires the 'configure any
 * layout' permission. Individual section storage plugins may override this by
 * setting the 'handles_permission_check' annotation key to TRUE. Any section
 * storage plugin that uses 'handles_permission_check' must provide its own
 * complete routing access checking to avoid any access bypasses.
 *
 * This access checking is only enforced on the routing level (not on the entity
 * or field level) with additional form access restrictions. All HTTP API access
 * to Layout Builder data is currently forbidden.
 *
 * @see https://www.drupal.org/project/drupal/issues/2942975
 */

/**
 * Add and alter contexts available to layout builder sections before building
 * content.
 *
 * In this example we add a customer profile context whenever a user entity is
 * being viewed.
 *
 * @param \Drupal\Core\Plugin\Context\ContextInterface[] &$contexts
 *   Array of contexts for the layout keyed.
 * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
 *   The section storage used to construct the layout. This is provided so that
 *   DefaultsSectionStorage third party settings can be used to alter contexts.
 *   Note that OverridesSectionStorage does not have third party settings.
 * @param bool $sample
 *   Whether or not to permit sample entities. If true, you should ensure that
 *   every possible context has a value to allow for preview and placeholder content
 *   in blocks.
 */
function hook_layout_builder_view_context_alter(&$contexts, SectionStorageInterface $section_storage, $sample = FALSE) {
  if (!isset($contexts['layout_builder.entity'])) {
    return;
  }

  /* @var \Drupal\Core\Plugin\Context\EntityContext $layout_entity_context */
  $layout_entity_context = $contexts['layout_builder.entity'];

  /* @var \Drupal\Core\Entity\EntityInterface $layout_entity */
  $layout_entity = $layout_entity_context->getContextData()->getValue();
  $sample_generator = \Drupal::service('layout_builder.sample_entity_generator');

  if ($layout_entity instanceof AccountInterface) {
    $profile_types = [
      'customer',
    ];

    foreach ($profile_types as $type) {
      if ($layout_entity->get("profile_{$type}")->target_id) {
        $entity = $layout_entity->get("profile_{$type}")->entity;
      }
      elseif ($sample) {
        $entity = $sample_generator->get('profile', $type);
      }

      if (isset($entity)) {
        $contexts['layout_builder.additional.' . $type] = EntityContext::fromEntity($entity);
      }
    }
  }
}

/**
 * @} End of "defgroup layout_builder_access".
 */
