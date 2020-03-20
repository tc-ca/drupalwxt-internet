<?php

namespace Drupal\Tests\lightning_media_image\FunctionalJavascript;

use Behat\Mink\Element\DocumentElement;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\lightning_media\FunctionalJavascript\WebDriverWebAssert;
use Drupal\Tests\lightning_media\Traits\EntityBrowserTrait;

/**
 * Tests that the image browser handles field cardinality correctly.
 *
 * @group lightning_media
 * @group lightning_media_image
 */
class ImageBrowserCardinalityTest extends WebDriverTestBase {

  use EntityBrowserTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image_widget_crop',
    'lightning_media_image',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'page']);

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_multi_image',
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => 3,
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Multi-Image',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_unlimited_images',
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Unlimited Images',
    ])->save();

    lightning_media_entity_get_form_display('node', 'page')
      ->setComponent('field_multi_image', [
        'type' => 'entity_browser_file',
        'settings' => [
          'entity_browser' => 'image_browser',
          'field_widget_edit' => TRUE,
          'field_widget_remove' => TRUE,
          'view_mode' => 'default',
          'preview_image_style' => 'thumbnail',
          'open' => TRUE,
          'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        ],
        'region' => 'content',
      ])
      ->setComponent('field_unlimited_images', [
        'type' => 'entity_browser_file',
        'settings' => [
          'entity_browser' => 'image_browser',
          'field_widget_edit' => TRUE,
          'field_widget_remove' => TRUE,
          'view_mode' => 'default',
          'preview_image_style' => 'thumbnail',
          'open' => TRUE,
          'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        ],
        'region' => 'content',
      ])
      ->save();

    for ($i = 0; $i < 4; $i++) {
      $uri = $this->getRandomGenerator()->image(uniqid('public://random_') . '.png', '240x240', '640x480');

      $file = File::create([
        'uri' => $uri,
      ]);
      $file->setMimeType('image/png');
      $file->setTemporary();
      $file->save();

      $media = Media::create([
        'bundle' => 'image',
        'name' => $this->getRandomGenerator()->name(32),
        'image' => $file->id(),
        'field_media_in_library' => TRUE,
      ]);
      $media->save();
    }

    $account = $this->drupalCreateUser([
      'access media overview',
      'create page content',
      'access image_browser entity browser pages',
    ]);
    $this->drupalLogin($account);

    $GLOBALS['install_state'] = [];
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('media');
    lightning_media_image_view_insert($view);
    unset($GLOBALS['install_state']);

    module_load_install('lightning_media_image');
    lightning_media_image_install();
  }

  /**
   * Tests that cardinality is enforced in the image browser.
   */
  public function testCardinality() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('/node/add/page');

    $this->openImageBrowser('Multi-Image');
    $items = $this->waitForItems();
    $this->assertGreaterThanOrEqual(4, count($items));
    $this->selectItem($items[0]);
    $this->selectItem($items[1]);
    $page->pressButton('Select');
    $this->waitForEntityBrowserToClose();
    // Wait for the selected items to actually appear on the page.
    $assert_session->waitForElement('css', '[data-drupal-selector="edit-field-multi-image-current"] [data-entity-id]');

    $this->openImageBrowser('Multi-Image');
    $items = $this->waitForItems();
    $this->assertGreaterThanOrEqual(4, count($items));
    $this->selectItem($items[2]);
    $disabled = $page->waitFor(10, function (DocumentElement $page) {
      return $page->findAll('css', '[data-selectable].disabled');
    });
    $this->assertGreaterThanOrEqual(3, count($disabled));

    // Close the image browser without selecting anything.
    $this->getSession()->switchToIFrame(NULL);
    $assert_session->elementExists('css', '.ui-dialog')->pressButton('Close');

    $this->openImageBrowser('Unlimited Images');
    $items = $this->waitForItems();
    $this->assertGreaterThanOrEqual(4, count($items));
    $this->selectItem($items[0]);
    $this->selectItem($items[1]);
    $this->selectItem($items[2]);
    $page->pressButton('Select');
    $this->waitForEntityBrowserToClose();
    // Wait for the selected items to actually appear on the page.
    $assert_session->waitForElement('css', '[data-drupal-selector="edit-field-unlimited-images-current"] [data-entity-id]');

    $this->openImageBrowser('Unlimited Images');
    $items = $this->waitForItems();
    $this->assertGreaterThanOrEqual(4, count($items));
    $this->selectItem($items[3]);
    $assert_session->elementsCount('css', '[data-selectable].disabled', 0);
  }

  /**
   * Opens a modal image browser.
   *
   * @param string $label
   *   The label of the image field.
   */
  private function openImageBrowser($label) {
    $this->assertSession()
      ->elementExists('css', "details > summary:contains($label)")
      ->getParent()
      ->pressButton('Select Image(s)');

    $this->waitForEntityBrowser('image_browser');
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new WebDriverWebAssert($this->getSession($name), $this->baseUrl);
  }

}
