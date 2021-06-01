<?php

namespace Drupal\Tests\lightning_media\Kernel\Update;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Lightning Media's 8021 update hook.
 *
 * @group lightning_media
 */
class Update8021Test extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media',
    'media',
    'system',
  ];

  /**
   * Tests Lightning Media's 8021 update hook.
   */
  public function testUpdate() {
    $setting = $this->config('lightning_media.settings')->get('revision_ui');
    $this->assertNull($setting);

    module_load_install('lightning_media');
    lightning_media_update_8021();

    $setting = $this->config('lightning_media.settings')->get('revision_ui');
    $this->assertTrue($setting);
  }

}
