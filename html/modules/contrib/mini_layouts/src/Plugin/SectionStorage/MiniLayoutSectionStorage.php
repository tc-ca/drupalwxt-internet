<?php

namespace Drupal\mini_layouts\Plugin\SectionStorage;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\facets\Exception\Exception;
use Drupal\layout_builder\Entity\SampleEntityGeneratorInterface;
use Drupal\layout_builder\Plugin\SectionStorage\SectionStorageBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class MiniLayoutSectionStorage
 *
 * @SectionStorage(
 *   id = "mini_layout",
 *   weight = 20,
 *   context_definitions = {
 *     "display" = @ContextDefinition("entity:mini_layout"),
 *   },
 * )
 *
 * @package Drupal\mini_layouts\Plugin\SectionStorage
 */
class MiniLayoutSectionStorage extends SectionStorageBase implements ContainerFactoryPluginInterface, ThirdPartySettingsInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * @var \Drupal\layout_builder\Entity\SampleEntityGeneratorInterface
   */
  protected $sampleEntityGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('layout_builder.sample_entity_generator')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle_info, SampleEntityGeneratorInterface $sample_entity_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleInfo = $entity_bundle_info;
    $this->sampleEntityGenerator = $sample_entity_generator;
  }

  /**
   * Get the mini layout entity.
   *
   * @return \Drupal\mini_layouts\Entity\MiniLayout
   */
  protected function getMiniLayout() {
    return $this->getContextValue('display');
  }

  /**
   * Gets the section list.
   *
   * @return \Drupal\layout_builder\SectionListInterface
   *   The section list.
   */
  protected function getSectionList() {
    return $this->getMiniLayout();
  }

  /**
   * Returns an identifier for this storage.
   *
   * @return string
   *   The unique identifier for this storage.
   */
  public function getStorageId() {
    return $this->getMiniLayout()->id();
  }

  /**
   * Derives the section list from the storage ID.
   *
   * @param string $id
   *   The storage ID, see ::getStorageId().
   *
   * @return \Drupal\layout_builder\SectionListInterface
   *   The section list.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the ID is invalid.
   *
   * @internal
   *   This should only be called during section storage instantiation.
   *
   * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. The
   *   section list should be derived from context. See
   *   https://www.drupal.org/node/3016262.
   */
  public function getSectionListFromId($id) {
    @trigger_error('\Drupal\layout_builder\SectionStorageInterface::getSectionListFromId() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. The section list should be derived from context. See https://www.drupal.org/node/3016262.', E_USER_DEPRECATED);
    return $this->entityTypeManager->getStorage('mini_layout')->load($id);
  }

  /**
   * Provides the routes needed for Layout Builder UI.
   *
   * Allows the plugin to add or alter routes during the route building process.
   * \Drupal\layout_builder\Routing\LayoutBuilderRoutesTrait is provided for the
   * typical use case of building a standard Layout Builder UI.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   *
   * @see \Drupal\Core\Routing\RoutingEvents::ALTER
   */
  public function buildRoutes(RouteCollection $collection) {
    $requirements = [];
    $this->buildLayoutRoutes(
      $collection,
      $this->getPluginDefinition(),
      'admin/structure/mini_layouts/manage/{mini_layout}/layout',
      [
        'parameters' => [
          'mini_layout' => [
            'type' => 'entity:mini_layout',
          ],
        ],
      ],
      $requirements,
      // This can't be an admin route because seven decides to ditch all
      // contextual links on blocks. See issue
      // https://www.drupal.org/project/drupal/issues/2487025
      ['_admin_route' => FALSE],
      '',
      'mini_layout'
    );
  }

  /**
   * Gets the URL used when redirecting away from the Layout Builder UI.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getRedirectUrl() {
    return Url::fromRoute('entity.mini_layout.edit_form', [
      'mini_layout' => $this->getMiniLayout()->id(),
    ]);
  }

  /**
   * Gets the URL used to display the Layout Builder UI.
   *
   * @param string $rel
   *   (optional) The link relationship type, for example: 'view' or 'disable'.
   *   Defaults to 'view'.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getLayoutBuilderUrl($rel = 'view') {
    return Url::fromRoute("layout_builder.{$this->getStorageType()}.{$rel}", [
      'mini_layout' => $this->getMiniLayout()->id(),
    ]);
  }

  /**
   * Configures the plugin based on route values.
   *
   * @param mixed $value
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string|null
   *   The section storage ID if it could be extracted, NULL otherwise.
   *
   * @internal
   *   This should only be called during section storage instantiation.
   *
   * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0.
   *   \Drupal\layout_builder\SectionStorageInterface::deriveContextsFromRoute()
   *   should be used instead. See https://www.drupal.org/node/3016262.
   */
  public function extractIdFromRoute($value, $definition, $name, array $defaults) {
    throw new \Exception(new TranslatableMarkup('This method is deprecated in 8.7.0'));
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextsDuringPreview() {
    $contexts = parent::getContextsDuringPreview();

    foreach ($this->getMiniLayout()->required_context as $machine_name => $info) {
      if (strpos($info['type'], 'entity:') === 0) {
        list(,$entity_type_id, $bundle) = explode(':', $info['type'], 3);

        if (!$bundle) {
          $bundle = $entity_type_id;
          if ($this->entityTypeManager->getDefinition($entity_type_id)->hasKey('bundle')) {
            if (!empty($info['bundle'])) {
              $bundle = $info['bundle'];
            } else {
              $bundle = key($this->entityBundleInfo->getBundleInfo($entity_type_id));
            }
          }
        }

        $sample = $this->sampleEntityGenerator->get($entity_type_id, $bundle);
        $contexts[$machine_name] = new Context(
          new ContextDefinition($info['type'], $info['label'], !empty($info['required']), FALSE, '', $sample),
          $sample
        );
      }
      else {
        $contexts[$machine_name] = new Context(
          new ContextDefinition($info['type'], $info['label'], !empty($info['required']), FALSE)
        );
      }
    }

    return $contexts;
  }

  /**
   * Derives the available plugin contexts from route values.
   *
   * This should only be called during section storage instantiation,
   * specifically for use by the routing system. For all non-routing usages, use
   * \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextValue().
   *
   * @param mixed $value
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The available plugin contexts.
   *
   * @see \Drupal\Core\ParamConverter\ParamConverterInterface::convert()
   */
  public function deriveContextsFromRoute($value, $definition, $name, array $defaults) {
    $contexts = [];

    $id = !empty($value) ? $value : (!empty($defaults['mini_layout']) ? $defaults['mini_layout'] : NULL);
    if ($id && ($entity = $this->entityTypeManager->getStorage('mini_layout')->load($id))) {
      $contexts['display'] = EntityContext::fromEntity($entity);
    }

    return $contexts;
  }

  /**
   * Gets the label for the object using the sections.
   *
   * @return string
   *   The label, or NULL if there is no label defined.
   */
  public function label() {
    return $this->getMiniLayout()->label();
  }

  /**
   * Saves the sections.
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   */
  public function save() {
    return $this->getMiniLayout()->save();
  }

  /**
   * Determines if this section storage is applicable for the current contexts.
   *
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   Refinable cacheability object, typically provided by the section storage
   *   manager. When implementing this method, populate $cacheability with any
   *   information that affects whether this storage is applicable.
   *
   * @return bool
   *   TRUE if this section storage is applicable, FALSE otherwise.
   *
   * @internal
   *   This method is intended to be called by
   *   \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::findByContext().
   *
   * @see \Drupal\Core\Cache\RefinableCacheableDependencyInterface
   */
  public function isApplicable(RefinableCacheableDependencyInterface $cacheability) {
    return TRUE;
  }

  /**
   * Overrides \Drupal\Core\Access\AccessibleInterface::access().
   *
   * @ingroup layout_builder_access
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($module, $key, $value) {
    $this->getMiniLayout()->setThirdPartySetting($module, $key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    return $this->getMiniLayout()->getThirdPartySetting($module, $key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($module) {
    return $this->getMiniLayout()->getThirdPartySettings($module);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($module, $key) {
    $this->getMiniLayout()->unsetThirdPartySetting($module, $key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return $this->getMiniLayout()->getThirdPartyProviders();
  }
}
