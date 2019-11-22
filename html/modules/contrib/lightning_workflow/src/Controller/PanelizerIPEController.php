<?php

namespace Drupal\lightning_workflow\Controller;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
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
   * PanelizerIPEController constructor.
   *
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   * @param \Drupal\content_moderation\ModerationInformationInterface $mod_info
   *   The moderation information service.
   */
  public function __construct(PanelizerInterface $panelizer, ModerationInformationInterface $mod_info) {
    parent::__construct($panelizer);
    $this->modInfo = $mod_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('panelizer'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function revertToDefault(FieldableEntityInterface $entity, $view_mode) {
    if ($this->modInfo->isModeratedEntity($entity)) {
      $entity = $this->modInfo->getLatestRevision($entity->getEntityTypeId(), $entity->id());
    }

    return parent::revertToDefault($entity, $view_mode);
  }

}
