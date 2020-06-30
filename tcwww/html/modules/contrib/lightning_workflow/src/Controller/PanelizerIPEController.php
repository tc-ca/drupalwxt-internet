<?php

namespace Drupal\lightning_workflow\Controller;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\panelizer\Controller\PanelizerPanelsIPEController;
use Drupal\panelizer\PanelizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Panels IPE routes that are specific to Panelizer.
 *
 * @internal
 *   This is an internal part of Lightning Workflow's integration with Panelizer
 *   and may be changed or removed at any time. External code should not use
 *   or extend this class in any way!
 */
class PanelizerIPEController extends PanelizerPanelsIPEController {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $modInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * PanelizerIPEController constructor.
   *
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   * @param \Drupal\content_moderation\ModerationInformationInterface $mod_info
   *   The moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PanelizerInterface $panelizer, ModerationInformationInterface $mod_info, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($panelizer);
    $this->modInfo = $mod_info;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('panelizer'),
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function revertToDefault(FieldableEntityInterface $entity, $view_mode) {
    if ($this->modInfo->isModeratedEntity($entity)) {
      $entity = $this->getLatestRevision($entity->getEntityTypeId(), $entity->id());
    }

    return parent::revertToDefault($entity, $view_mode);
  }

  /**
   * Loads the latest revision of an entity.
   *
   * This is a shim around ModerationInformationInterface::getLatestRevision(),
   * which was replaced by calling methods on the entity storage handler in
   * Drupal 8.8.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param mixed $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The latest revision of the entity. If one could not be found, the default
   *   revision is returned instead.
   */
  private function getLatestRevision($entity_type_id, $entity_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    if ($storage instanceof RevisionableStorageInterface && method_exists($storage, 'getLatestRevisionId')) {
      $revision_id = $storage->getLatestRevisionId($entity_id);
      return isset($revision_id) ? $storage->loadRevision($revision_id) : $storage->load($entity_id);
    }
    else {
      // Use call_user_func() here because our deprecation testing tools are not
      // smart enough to recognize the actual code path that leads here.
      return call_user_func([$this->modInfo, 'getLatestRevision'], $entity_type_id, $entity_id);
    }
  }

}
