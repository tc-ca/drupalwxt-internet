<?php

namespace Drupal\lightning_scheduler\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\content_moderation\ModerationInformation;
use Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget as BaseModerationStateWidget;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\lightning_scheduler\TransitionManager;
use Drupal\lightning_scheduler\TransitionSet;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Scheduler extension of Content Moderation's widget.
 */
class ModerationStateWidget extends BaseModerationStateWidget {

  /**
   * The transition manager.
   *
   * @var \Drupal\lightning_scheduler\TransitionManager
   */
  protected $transitionManager;

  /**
   * The current entity.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface
   */
  protected $entity;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ModerationStateWidget object.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\content_moderation\ModerationInformation $moderation_information
   *   Moderation information service.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validator
   *   Moderation state transition validation service.
   * @param \Drupal\lightning_scheduler\TransitionManager $transition_manager
   *   The transition manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ModerationInformation $moderation_information, StateTransitionValidationInterface $validator, TransitionManager $transition_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $current_user, $entity_type_manager, $moderation_information, $validator);
    $this->transitionManager = $transition_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation'),
      $container->get('lightning_scheduler.transition_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $entity = $items->getEntity();

    // The entity must have the proper fields.
    $has_fields = $entity->hasField('scheduled_transition_date') && $entity->hasField('scheduled_transition_state');
    if (!$has_fields) {
      return $element;
    }

    $states = $this->transitionManager->getStates($entity);

    // The latest revision, if there is one, is the canonical source of truth
    // regarding scheduled transitions.
    if ($entity->isNew()) {
      $latest_revision = $entity;
    }
    else {
      $latest_revision = $this->getLatestRevision(
        $entity->getEntityTypeId(),
        $entity->id()
      ) ?: $entity;
    }

    $transition_set = new TransitionSet(
      $latest_revision->get('scheduled_transition_date'),
      $latest_revision->get('scheduled_transition_state')
    );

    $element['scheduled_transitions'] = [
      '#type' => 'html_tag',
      '#tag' => 'TransitionSet',
      '#attributes' => [
        'states' => Json::encode($states),
        'step' => $this->configFactory->get('lightning_scheduler.settings')->get('time_step'),
      ],
      '#attached' => [
        'library' => ['lightning_scheduler/widget'],
      ],
      'data' => [
        '#type' => 'hidden',
        '#element_validate' => [
          [
            get_class($this->transitionManager),
            'validate',
          ],
          [$this, 'storeValue'],
        ],
        '#default_value' => $transition_set->toJson(),
        '#process' => [
          [$this, 'processComponentInput'],
        ],
      ],
    ];

    // Allow the process and validation callbacks to work directly with the
    // entity.
    $this->entity = $entity;

    return $element;
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
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The latest revision of the entity, if one exists.
   */
  private function getLatestRevision($entity_type_id, $entity_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    if ($storage instanceof RevisionableStorageInterface && method_exists($storage, 'getLatestRevisionId')) {
      $revision_id = $storage->getLatestRevisionId($entity_id);
      return isset($revision_id) ? $storage->loadRevision($revision_id) : NULL;
    }
    else {
      // Use call_user_func() here because our deprecation testing tools are not
      // smart enough to recognize the actual code path that leads here.
      return call_user_func([$this->moderationInformation, 'getLatestRevision'], $entity_type_id, $entity_id);
    }
  }

  /**
   * Process callback for the scheduler component's input element.
   *
   * @param array $element
   *   The unprocessed element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The processed element.
   */
  public function processComponentInput(array $element, FormStateInterface $form_state) {
    $key = $element['#parents'];
    if ($form_state->hasValue($key)) {
      $element['#default_value'] = $form_state->getValue($key);
    }
    return $element;
  }

  /**
   * Accesses the hidden input element and stores its value in the form state.
   *
   * @param array $element
   *   The hidden input.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to update.
   */
  public function storeValue(array $element, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return;
    }

    $decoded = Json::decode($element['#value']);

    if (is_array($decoded)) {
      $transition_storage = $form_state->getValue('transition_storage') ?: [];
      // Support multiple widgets on one form (e.g. Inline Entity Form).
      $uuid = $this->entity->uuid();
      $transition_storage[$uuid] = $decoded;
      $form_state->setValue('transition_storage', $transition_storage);
    }
    else {
      throw new \InvalidArgumentException('Expected value of ' . $element['#name'] . ' to be an array.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);

    $transitions = $form_state->getValue('transition_storage');
    $entity = $items->getEntity();
    $uuid = $entity->uuid();

    // Do not use empty() here, because it's possible that the user is trying to
    // clear all scheduled transitions, which means $transitions[$uuid] will
    // be an empty array.
    if (!isset($transitions[$uuid])) {
      return;
    }

    $states = array_map(function (array $transition) {
      if ($transition['state'] && is_string($transition['state'])) {
        return [
          'value' => $transition['state'],
        ];
      }
      else {
        throw new \InvalidArgumentException('Invalid transition state: ' . $transition['state']);
      }
    }, $transitions[$uuid]);

    $dates = array_map(function (array $transition) {
      return [
        'value' => gmdate(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $transition['when']),
      ];
    }, $transitions[$uuid]);

    if (count($states) === count($dates)) {
      $entity
        ->set('scheduled_transition_state', $states)
        ->set('scheduled_transition_date', $dates);
    }
    else {
      throw new \LogicException('scheduled_transition_state and scheduled_transition_date fields must have the same number of items.');
    }
  }

}
