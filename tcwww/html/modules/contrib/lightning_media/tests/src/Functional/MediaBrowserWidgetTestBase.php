<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

abstract class MediaBrowserWidgetTestBase extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media',
    'node',
  ];

  abstract protected function createMediaTypes();

  abstract protected function chooseWidget();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    $this->createMediaTypes();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => NULL,
        ],
      ],
    ])->save();

    lightning_media_entity_get_form_display('node', 'page', 'default')
      ->setComponent('field_media', [
        'type' => 'entity_browser_entity_reference',
        'settings' => [
          'entity_browser' => 'media_browser',
          'field_widget_display' => 'rendered_entity',
          'field_widget_edit' => TRUE,
          'field_widget_remove' => TRUE,
          'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
          'field_widget_display_settings' => [
            'view_mode' => 'thumbnail',
          ],
          'open' => TRUE,
        ],
        'region' => 'content',
      ])
      ->save();

    $account = $this->createUser([
      'create page content',
      'access media_browser entity browser pages',
      'create media',
    ]);
    $this->drupalLogin($account);
  }

  public function testInvalidInput() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/entity-browser/modal/media_browser');
    $assert_session->statusCodeEquals(200);
    $this->chooseWidget();

    $this->getSession()->getPage()->pressButton('Place');
    $assert_session->statusCodeEquals(200);
  }

  abstract public function testFieldAllowedTypesSettingIsRespected();

  abstract public function testDisambiguation();

  protected function visitMediaBrowserFromNodeForm() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/page');
    $assert_session->statusCodeEquals(200);

    $uuid = $assert_session->buttonExists('Add media')->getAttribute('data-uuid');
    $this->assertNotEmpty($uuid);

    $this->drupalGet("/entity-browser/modal/media_browser", [
      'query' => [
        'uuid' => $uuid,
      ],
    ]);
    $assert_session->statusCodeEquals(200);
  }

  protected function assertMediaCount($count, array $conditions = []) {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery();

    foreach ($conditions as $column => $value) {
      $query->condition($column, $value);
    }
    $this->assertCount($count, $query->execute());
  }

}
