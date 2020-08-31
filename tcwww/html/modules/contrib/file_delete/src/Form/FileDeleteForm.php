<?php

namespace Drupal\file_delete\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a File.
 */
class FileDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * The file being deleted.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $entity;

  /**
   * The File Usage Service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * {@inheritdoc}
   */
  public function __construct(FileUsageInterface $file_usage, EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    $this->fileUsage = $file_usage;

    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file.usage'),
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['force_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do you want to force this file to be deleted?'),
      '#description' => $this->t('This option will override the usages check, which could result in a broken link. To avoid this, remove all usages of the file first.'),
    ];
    $form['delete_immediately'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do you want to delete the file immediately?'),
      '#description' => $this->t('This option will skip Drupal\'s file cleanup method and delete the file directly.'),
    ];
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the file %file_name (%file_path) ?', [
      '%file_name' => $this->entity->getFilename(),
      '%file_path' => $this->entity->getFileUri(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.files.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete File');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $force_delete = $form_state->getValue('force_delete');
    $delete_immediately = $form_state->getValue('delete_immediately');
    $usages = $this->fileUsage->listUsage($this->entity);

    // If the file is in use, and we don't want to force delete, cancel the
    // delete and set error message.
    if ($usages && !$force_delete) {
      $url = new Url('view.files.page_2', ['arg_0' => $this->entity->id()]);
      $this->messenger()->addError($this->t('The file %file_name cannot be deleted because it is in use by the following modules: %modules.<br>Click <a href=":link_to_usages">here</a> to see its usages.', [
        '%file_name' => $this->entity->getFilename(),
        '%modules' => implode(', ', array_keys($usages)),
        ':link_to_usages' => $url->toString(),
      ]));

      return;
    }

    // If $delete_immediately is TRUE, delete the file, otherwise mark it for
    // removal by file_cron().
    if ($delete_immediately) {
      $this->entity->delete();
      $this->messenger()->addMessage($this->t('The file %file_name has been deleted.', [
        '%file_name' => $this->entity->getFilename(),
      ]));
    }
    else {
      $this->entity->setTemporary();
      $this->entity->save();
      $this->messenger()->addMessage($this->t('The file %file_name has been marked for deletion.', [
        '%file_name' => $this->entity->getFilename(),
      ]));
    }

    $form_state->setRedirect('view.files.page_1');
  }

}
