<?php

namespace Drupal\webform_content_creator\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;

/**
 * Form handler for the Webform content creator add and edit forms.
 */
class WebformContentCreatorForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getTitle(),
      '#help' => $this->t('Configuration title'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
        'source' => ['title'],
      ],
      '#disabled' => !$this->entity->isNew(),
      '#description' => $this->t('A unique machine-readable name for this content type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %webform-content-creator-add page, in which underscores will be converted into hyphens.'),
    ];

    // Select with all webforms.
    $webforms_formatted = WebformContentCreatorUtilities::getFormattedWebforms();
    $form['webform'] = [
      '#type' => 'select',
      '#title' => $this->t('Webform'),
      '#options' => $webforms_formatted,
      '#default_value' => $this->entity->getWebform(),
      '#description' => $this->t("Webform title"),
      '#required' => TRUE,
    ];

    // Select with all content types.
    $contentTypes_formatted = WebformContentCreatorUtilities::getFormattedContentTypes();
    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#options' => $contentTypes_formatted,
      '#default_value' => $this->entity->getContentType(),
      '#description' => $this->t("Content type title"),
      '#required' => TRUE,
    ];

    $form['sync_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Synchronize Webform submission with the created node in edition'),
      '#description' => $this->t('Perform synchronization between webform submission and respective node when one is edited. When a webform submission is edited, the resultant node is synchronized with the new values.'),
      '#default_value' => $this->entity->getSyncEditContentCheck(),
    ];

    $form['sync_content_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Synchronize Webform submission with the created node in deletion'),
      '#description' => $this->t('Perform synchronization in deletion. When a webform submission is deleted, the resultant node is also deleted.'),
      '#default_value' => $this->entity->getSyncDeleteContentCheck(),
    ];

    $form['sync_content_node_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Synchronization field machine name'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getSyncContentField(),
      '#help' => $this->t('When a webform submission is edited, the node which stores the webform submission id in this field is also updated. You have to create this field in the content type and then you have to map this field with Submission id. Example: field_submission_id'),
      '#states' => [
        'visible' =>
          [
            [
              ':input[name="sync_content"]' => ['checked' => TRUE],
            ],
            'or',
            [
              ':input[name="sync_content_delete"]' => ['checked' => TRUE],
            ],
          ],
        'required' =>
          [
            ':input[name="sync_content"]' => ['checked' => TRUE],
          ],
      ],
    ];

    $form['use_encrypt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Decrypt values'),
      '#description' => $this->t('This only applies when Webform encrypt module is being used in one or more webform elements.'),
      '#default_value' => $this->entity->getEncryptionCheck(),
    ];

    // Select with all encryption profiles.
    $encryptionProfiles_formatted = WebformContentCreatorUtilities::getFormattedEncryptionProfiles();
    $form['encryption_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Encryption profile'),
      '#options' => $encryptionProfiles_formatted,
      '#default_value' => $this->entity->getEncryptionProfile(),
      '#description' => $this->t("Encryption profile name"),
      '#states' => [
        'visible' =>
          [
            ':input[name="use_encrypt"]' => ['checked' => TRUE],
          ],
        'required' =>
          [
            ':input[name="use_encrypt"]' => ['checked' => TRUE],
          ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->entity->equalsWebform($form['webform']['#default_value']) || !$this->entity->equalsContentType($form['content_type']['#default_value'])) {
      $this->entity->set('elements', []);
    }
    $status = $this->entity->save();
    $this->entity->statusMessage($status);
    $form_state->setRedirect('entity.webform_content_creator.collection');
  }

  /**
   * Helper function to check whether a Webform content creator entity exists.
   *
   * @param string $id
   *   Entity id.
   *
   * @return bool
   *   True if entity already exists.
   */
  public function exist($id) {
    return WebformContentCreatorUtilities::existsWebformContentCreatorEntity($id);
  }

}
