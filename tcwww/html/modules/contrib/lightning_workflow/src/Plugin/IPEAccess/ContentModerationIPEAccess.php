<?php

namespace Drupal\lightning_workflow\Plugin\IPEAccess;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels_ipe\Plugin\IPEAccessBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Panels IPE access logic for Content Moderation integration.
 *
 * @internal
 *   This is an internal part of Lightning Workflow's integration with Panels
 *   and may be changed or removed at any time. External code should not use
 *   or extend this class in any way!
 *
 * @IPEAccess(
 *   id = "content_moderation_ipe",
 *   label = @Translation("Content moderation")
 * )
 */
class ContentModerationIPEAccess extends IPEAccessBase implements ContainerFactoryPluginInterface {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $information;

  /**
   * ContentModerationIPEAccess constructor.
   *
   * @param array $configuration
   *   An array of plugin configuration options.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\content_moderation\ModerationInformationInterface $information
   *   The moderation information service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModerationInformationInterface $information) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->information = $information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(PanelsDisplayVariant $display) {
    $contexts = $display->getContexts();

    if (empty($contexts['@panelizer.entity_context:entity'])) {
      return FALSE;
    }

    $context = $contexts['@panelizer.entity_context:entity'];
    if ($context->hasContextValue()) {
      return $this->information->isModeratedEntity($context->getContextValue());
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access(PanelsDisplayVariant $display) {
    $entity = $display->getContexts()['@panelizer.entity_context:entity']->getContextValue();
    return $this->isLatestRevision($entity) && !$this->information->isLiveRevision($entity);
  }

  /**
   * Determines if an entity is the latest revision.
   *
   * This is a shim around RevisionableInterface::isLatestRevision() and
   * ModerationInformationInterface::isLatestRevision(). The former is added in
   * Drupal 8.8, deprecating the latter.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity is the latest revision, FALSE otherwise.
   */
  private function isLatestRevision(EntityInterface $entity) {
    return $entity instanceof RevisionableInterface && method_exists($entity, 'isLatestRevision')
      ? $entity->isLatestRevision()
      // Use call_user_func() to work around the fact that our deprecation
      // scanning tools cannot recognize the actual code path that leads here.
      : call_user_func([$this->information, 'isLatestRevision'], $entity);
  }

}
