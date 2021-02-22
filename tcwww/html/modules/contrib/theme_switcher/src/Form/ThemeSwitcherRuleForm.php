<?php

namespace Drupal\theme_switcher\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the ThemeSwitcherRule add and edit forms.
 */
class ThemeSwitcherRuleForm extends EntityForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The ConditionManager for building the visibility UI.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs an SwitchThemeRuleForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_plugin_manager
   *   The ConditionManager for building the visibility UI.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(MessengerInterface $messenger, LoggerChannelInterface $logger, ThemeHandlerInterface $theme_handler, ExecutableManagerInterface $condition_plugin_manager, ContextRepositoryInterface $context_repository, LanguageManagerInterface $language_manager) {
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->themeHandler = $theme_handler;
    $this->conditionPluginManager = $condition_plugin_manager;
    $this->contextRepository = $context_repository;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory')->get('theme_switcher'),
      $container->get('theme_handler'),
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $available_contexts = $this->contextRepository->getAvailableContexts();
    $form_state->setTemporaryValue('gathered_contexts', $available_contexts);

    /** @var \Drupal\theme_switcher\Entity\ThemeSwitcherRule $entity */
    $entity = $this->entity;
    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Theme Switcher Rule'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('The human-readable name is shown in the Theme Switcher list.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Theme Switcher Rule status'),
      '#options' => [
        1 => $this->t('Active'),
        0 => $this->t('Inactive'),
      ],
      '#default_value' => (int) $entity->status(),
      '#description' => $this->t('The Theme Switcher Rule will only work if the active option is set.'),
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#access' => FALSE,
      '#default_value' => $entity->getWeight(),
      '#description' => $this->t('The sort order for this record. Lower values display first.'),
    ];
    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('The theme to apply in all pages that meet the conditions below.'),
      '#options' => $this->getThemeOptions(),
      '#default_value' => $entity->getTheme() ?? '',
      '#required' => TRUE,
    ];
    $form['admin_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Admin Theme'),
      '#description' => $this->t('The theme to apply in just the admin pages that meet the conditions below.'),
      '#options' => $this->getThemeOptions(),
      '#default_value' => $entity->getAdminTheme() ?? '',
    ];

    // Build the visibility UI form and follow this
    // https://www.drupal.org/node/2284687
    $form['visibility'] = [
      'visibility_tabs' => [
        '#type' => 'vertical_tabs',
        '#title' => $this->t('Conditions'),
        '#parents' => ['visibility_tabs'],
      ],
    ];
    $visibility = $entity->getVisibility();
    $definitions = $this->conditionPluginManager->getFilteredDefinitions(
      'theme_switcher_ui',
      $form_state->getTemporaryValue('gathered_contexts'),
      ['theme_switcher_rule' => $entity]
    );

    // Allows modules to alter the number the conditions.
    $this->moduleHandler->alter('available_conditions', $definitions);
    foreach ($definitions as $condition_id => $definition) {

      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->conditionPluginManager->createInstance(
          $condition_id, $visibility[$condition_id] ?? []
      );
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);

      $form['visibility'][$condition_id] = [
        '#type' => 'details',
        '#title' => $condition->getPluginDefinition()['label'],
        '#group' => 'visibility_tabs',
      ] + $condition_form;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * The settings conditions context mappings is now the plugin responsibility
   * so we can avoid doing it here. From 8.2 the class ConditionPluginBase do
   * the job on submitConfigurationForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\theme_switcher\Entity\ThemeSwitcherRule $entity */
    $entity = $this->entity;
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $subform = SubformState::createForSubform($form['visibility'][$condition_id], $form, $form_state);
      $condition->submitConfigurationForm($form['visibility'][$condition_id], $subform);

      // Update the visibility conditions on the block.
      $entity->getVisibilityConditions()->addInstanceId(
          $condition_id, $condition->getConfiguration()
      );
    }
    // Save the settings of the plugin.
    $status = $entity->save();

    $message = $this->t("The Theme Switcher Rule '%label' has been %op.", [
      '%label' => $entity->label(),
      '%op' => ($status == SAVED_NEW) ? 'created' : 'updated',
    ]);
    $this->messenger->addStatus($message);
    $this->logger->notice($message);

    $form_state->setRedirect('theme_switcher.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate the weight.
    $form_state->setValue('weight', (int) $form_state->getValue('weight'));

    // Validate visibility condition settings.
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue(['visibility', $condition_id, 'negate'], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $subform = SubformState::createForSubform($form['visibility'][$condition_id], $form, $form_state);
      $condition->validateConfigurationForm($form['visibility'][$condition_id], $subform);
    }
  }

  /**
   * Return an array with all the themes.
   *
   * @return array
   *   An array with all the themes.
   */
  protected function getThemeOptions() {
    $output[''] = '- None -';
    foreach ($this->themeHandler->listInfo() as $key => $value) {
      $output[$key] = $value->getName();
    }
    return $output;
  }

  /**
   * Checks whether a theme_switcher_rule exists.
   *
   * @param string $id
   *   The theme_switcher_rule machine name.
   *
   * @return bool
   *   Whether the theme_switcher_rule exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('theme_switcher_rule')
      ->getQuery()->condition('id', $id)->execute();
    return (bool) $entity;
  }

}
