<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests Lightning Media's modifications to the 'media' view.
 *
 * @group lightning_media
 */
class MediaOverviewTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media',
    'views',
  ];

  /**
   * Tests modifications to the 'media' view, depending on core version.
   */
  public function testMediaOverviewPath() {
    // The logic in lightning_media_view_insert() is normally only done during
    // site installation, so we need to simulate that.
    $GLOBALS['install_state'] = [];
    $view = View::load('media');
    $this->assertInstanceOf(View::class, $view);
    lightning_media_view_insert($view);

    $display = View::load('media')->getDisplay('media_page_list');

    // The path of the media overview page should only be modified on Drupal 8.7
    // and earlier.
    if (version_compare(\Drupal::VERSION, '8.8.0', '>=')) {
      $this->assertSame('admin/content/media', $display['display_options']['path']);
    }
    else {
      $this->assertSame('admin/content/media-table', $display['display_options']['path']);
    }
  }

}
