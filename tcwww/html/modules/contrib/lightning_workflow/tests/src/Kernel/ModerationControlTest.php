<?php

namespace Drupal\Tests\lightning_workflow\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests default visibility of the content_moderation_control extra field.
 *
 * @group lightning_workflow
 */
class ModerationControlTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'field',
    'lightning_workflow',
    'node',
    'system',
    'text',
    'user',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('lightning_workflow');
    $this->installConfig('node');

    $this->createContentType([
      'type' => 'page',
      'third_party_settings' => [
        'lightning_workflow' => [
          'workflow' => 'editorial',
        ],
      ],
    ]);
    // ContentTypeCreationTrait::createContentType() will add the body field to
    // default entity view display, which breaks this test. We are testing the
    // *default* visibility of the content_moderation_control extra field, which
    // only manifests when creating a new entity view display.
    EntityViewDisplay::load('node.page.default')->delete();
  }

  /**
   * Tests that moderation controls are hidden if Moderation Sidebar is enabled.
   */
  public function testHiddenOnModerationSidebarInstall() {
    $values = [
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ];
    $this->assertArrayHasKey('content_moderation_control', EntityViewDisplay::create($values)->getComponents());

    $this->container->get('module_installer')->install(['moderation_sidebar']);

    $hidden = EntityViewDisplay::create($values)->get('hidden');
    $this->assertTrue($hidden['content_moderation_control']);
  }

}
