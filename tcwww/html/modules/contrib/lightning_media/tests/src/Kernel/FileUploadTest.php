<?php

namespace Drupal\Tests\lightning_media\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Form\FormState;
use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\entity_browser\Form\EntityBrowserForm;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Contains low-level tests of the FileUpload entity browser widget.
 *
 * @group lightning_media
 *
 * @coversDefaultClass \Drupal\lightning_media\Plugin\EntityBrowser\Widget\FileUpload
 */
class FileUploadTest extends KernelTestBase {

  use MediaTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_browser',
    'field',
    'file',
    'image',
    'lightning_media',
    'media',
    'system',
    'user',
  ];

  /**
   * Tests that the widget form is built properly.
   */
  public function testFileUploadWidget() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
      $this->markTestSkipped('This test requires PHP 7.4 or later.');
    }

    $this->installEntitySchema('media');
    $this->installSchema('system', 'key_value_expire');
    $dir = $this->container->get('extension.list.module')
      ->getPath('lightning_media');
    $storage = new FileStorage($dir . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY);

    $data = $storage->read('field.storage.media.field_media_in_library');
    FieldStorageConfig::create($data)->save();

    $browser = $storage->read('entity_browser.browser.media_browser');
    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    $browser = EntityBrowser::create($browser);
    $browser->save();

    // There needs to be at least one file-based media type to test with.
    $this->createMediaType('image');

    $this->setUpCurrentUser([], ['create media']);
    // If access to the widget is not allowed, this test will falsely pass.
    $widget_id = '044d2af7-314b-4830-8b6d-64896bbb861e';
    $widget = $browser->getWidget($widget_id);
    $this->assertTrue($widget->access()->isAllowed());

    $form = EntityBrowserForm::create($this->container);
    $form->setEntityBrowser($browser);
    $form_state = new FormState();
    $form_state->set('entity_browser_current_widget', $widget_id);
    $this->container->get('form_builder')
      ->getForm($form, $form_state);

    // This test is looking for an error that happens during getForm() on PHP
    // 7.4 and up. If we get this far, we've passed.
    $this->assertTrue(TRUE);
  }

}
