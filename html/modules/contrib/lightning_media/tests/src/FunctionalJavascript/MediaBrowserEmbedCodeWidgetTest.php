<?php

namespace Drupal\Tests\lightning_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the embed code widget in the media browser.
 *
 * @group lightning_media
 */
class MediaBrowserEmbedCodeWidgetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media_video',
  ];

  /**
   * Tests that an error message is displayed for malformed URLs.
   */
  public function testErrorMessages() {
    $account = $this->drupalCreateUser([
      'access media_browser entity browser pages',
      'create media',
    ]);
    $this->drupalLogin($account);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/entity-browser/modal/media_browser');

    // Error message is displayed for malformed URLs.
    $page->clickLink('Create embed');
    $page->fillField('input', 'Foo');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('Bundle');

    $page->fillField('input', '');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('Bundle');
    $assert_session->elementNotExists('css', '[role="alert"]');

    // No error message when URL is valid.
    $page->fillField('input', 'https://www.youtube.com/watch?v=zQ1_IbFFbzA');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('Bundle');
    $assert_session->elementNotExists('css', '[role="alert"]');

    // Rerender the form if URL is changed.
    $page->fillField('input', 'Bar');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('Bundle');
    $assert_session->elementTextContains('css', '[role="alert"]', "Input did not match any media types: 'Bar'");
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new WebDriverWebAssert($this->getSession($name), $this->baseUrl);
  }

}
