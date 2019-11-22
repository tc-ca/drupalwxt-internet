<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * @group lightning_media
 */
class MediaBrowserTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field_ui',
    'lightning_media_image',
    'lightning_media_twitter',
    'node',
  ];

  /**
   * Slick Entity Reference has a schema error.
   *
   * @var bool
   *
   * @todo Remove when depending on slick_entityreference 1.2 or later.
   */
  protected $strictConfigSchema = FALSE;

  public function testAccess() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access media_browser entity browser pages',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/entity-browser/modal/media_browser');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No widgets are available.');

    // Create a media type. There should still be no widgets available, since
    // the current user does not have permission to create media.
    $this->createMediaType('image');
    $this->getSession()->reload();
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No widgets are available.');

    $account = $this->drupalCreateUser([
      'access media_browser entity browser pages',
      'create media',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/entity-browser/modal/media_browser');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('No widgets are available.');
    $assert_session->buttonExists('Upload');
    $assert_session->buttonExists('Create embed');
  }

  /**
   * The media browser should be the default widget for a new media field.
   */
  public function testNewMediaReferenceField() {
    $this->drupalPlaceBlock('local_actions_block');

    $node_type = $this->drupalCreateContentType()->id();
    $media_type = $this->createMediaType('image')->id();

    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->drupalGet("/admin/structure/types/manage/$node_type/fields");
    $this->clickLink('Add field');
    $values = [
      'new_storage_type' => 'field_ui:entity_reference:media',
      'label' => 'Foobar',
      'field_name' => 'foobar',
    ];
    $this->drupalPostForm(NULL, $values, 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $values = [
      "settings[handler_settings][target_bundles][$media_type]" => $media_type,
    ];
    $this->drupalPostForm(NULL, $values, 'Save settings');

    $component = lightning_media_entity_get_form_display('node', $node_type)
      ->getComponent('field_foobar');

    $this->assertInternalType('array', $component);
    $this->assertSame('entity_browser_entity_reference', $component['type']);
    $this->assertSame('media_browser', $component['settings']['entity_browser']);
  }

}
