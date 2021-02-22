<?php

namespace Drupal\theme_switcher\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a listing of theme_switcher_rule.
 */
class ThemeSwitcherRuleListBuilder extends DraggableListBuilder {

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The active user account.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, AccountInterface $account, MessengerInterface $messenger) {
    parent::__construct($entity_type, $storage);
    $this->currentUser = $account;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'theme_switcher_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form[$this->entitiesKey]['#rules'] = $this->entities;
    $form['actions']['submit']['#value'] = $this->t('Save configuration');

    // Only super-admins may sort switch theme rules.
    if (!$this->currentUser->hasPermission('administer theme switcher rules')) {
      $form['actions']['submit']['#access'] = FALSE;
      unset($form['entities']['#tabledrag']);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger->addMessage($this->t('Configuration saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['machine_name'] = $this->t('Machine name');
    $header['theme'] = $this->t('Theme');
    $header['admin_theme'] = $this->t('Admin Theme');
    $header['status'] = $this->t('Status');
    $header += parent::buildHeader();

    // Only super-admins may sort theme_switcher_rule.
    if (!$this->currentUser->hasPermission('administer theme switcher rules')) {
      unset($header['weight']);
    }
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['machine_name'] = [
      '#markup' => $entity->id(),
    ];
    $not_set = '<i>- None -</i>';
    $row['theme'] = [
      '#markup' => empty($entity->getTheme()) ? $not_set : $entity->getTheme(),
    ];
    $row['admin_theme'] = [
      '#markup' => empty($entity->getAdminTheme()) ? $not_set : $entity->getAdminTheme(),
    ];
    $row['status'] = [
      '#markup' => $entity->status() ? $this->t('Active') : $this->t('Inactive'),
      '#prefix' => $entity->status() ? '<strong>' : '',
      '#suffix' => $entity->status() ? '</strong>' : '',
    ];
    $row += parent::buildRow($entity);

    // Only super-admins may sort switch theme rules.
    if (!$this->currentUser->hasPermission('administer theme switcher rules')) {
      unset($row['weight']);
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    // Only super-admins may access fast operations.
    if ($this->currentUser->hasPermission('administer theme switcher rules')) {
      if ($entity->status()) {
        $operations['disable'] = [
          'title' => $this->t('Disable'),
          'url' => Url::fromRoute('theme_switcher.inline_action',
              ['op' => 'disable', 'theme_switcher_rule' => $entity->id()]
          ),
          'weight' => 50,
        ];
      }
      else {
        $operations['enable'] = [
          'title' => $this->t('Enable'),
          'url' => Url::fromRoute('theme_switcher.inline_action',
              ['op' => 'enable', 'theme_switcher_rule' => $entity->id()]),
          'weight' => 40,
        ];
      }
    }
    return $operations;
  }

}
