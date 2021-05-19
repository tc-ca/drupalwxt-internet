<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Core\Form\FormState;
use Drupal\lightning_media\Form\SettingsForm;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the add and edit forms for all our shipped media types.
 *
 * @group lightning
 * @group lightning_media
 */
class AddEditFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media_audio',
    'lightning_media_document',
    'lightning_media_image',
    'lightning_media_instagram',
    'lightning_media_twitter',
    'lightning_media_video',
  ];

  /**
   * Tests the add and edit forms for all our shipped media types.
   */
  public function testEditForms() {
    $media_types = $this->container->get('entity_type.manager')
      ->getStorage('media_type')
      ->getQuery()
      ->execute();

    $permissions = [
      'create url aliases',
    ];
    foreach ($media_types as $media_type) {
      $permissions[] = "create $media_type media";
      $permissions[] = "edit own $media_type media";
    }
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();

    $values = [
      'instagram' => [
        'embed_code' => 'https://www.instagram.com/p/CI3iZNXlQbt',
      ],
      'tweet' => [
        'embed_code' => 'https://twitter.com/PhenaProxima/status/1158431969920901126',
      ],
    ];
    $existing_media = [];
    foreach ($media_types as $media_type) {
      $this->drupalGet("/media/add/$media_type");
      $assert_session->statusCodeEquals(200);
      $assert_session->fieldNotExists('URL alias');
      $assert_session->fieldNotExists('Revision log message');

      $media = array_key_exists($media_type, $values)
        ? $values[$media_type]
        : [];

      $media += [
        'bundle' => $media_type,
        // The media type might not use the embed_code source field, but in case
        // it does, feed it a random value here. Because we're just testing the
        // edit form, and not anything specific to the media type itself, this
        // is a reasonable shortcut.
        'embed_code' => $this->randomString(),
      ];
      $media = Media::create($media);
      $media->save();
      $this->drupalGet($media->toUrl('edit-form'));
      $assert_session->statusCodeEquals(200);
      $assert_session->fieldNotExists('Create new revision');
      $assert_session->fieldNotExists('Revision log message');
      array_push($existing_media, $media);
    }

    // Enable the revision UI via our settings form. To speed things up, just
    // directly execute the form without logging in through the UI.
    $form_state = new FormState();
    $form_state->setValue('revision_ui', TRUE);
    $this->container->get('form_builder')
      ->submitForm(SettingsForm::class, $form_state);

    // The revision UI should now be visible, but the revision log message is
    // still hidden in the form display.
    foreach ($existing_media as $media) {
      $this->drupalGet($media->toUrl('edit-form'));
      $assert_session->statusCodeEquals(200);
      $assert_session->fieldExists('Create new revision');
      $assert_session->fieldNotExists('Revision log message');
    }

    foreach ($media_types as $media_type) {
      $this->container->get('entity_display.repository')
        ->getFormDisplay('media', $media_type)
        ->setComponent('revision_log_message')
        ->save();
    }

    foreach ($existing_media as $media) {
      $this->drupalGet($media->toUrl('edit-form'));
      $assert_session->statusCodeEquals(200);
      $assert_session->fieldExists('Create new revision');
      $assert_session->fieldExists('Revision log message');
    }
  }

}
