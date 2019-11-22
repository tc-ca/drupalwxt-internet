<?php

namespace Drupal\Tests\lightning_media\Functional;

/**
 * @group lightning_media
 */
class MediaBrowserEmbedCodeWidgetTest extends MediaBrowserWidgetTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_media_video'];

  /**
   * {@inheritdoc}
   */
  protected function createMediaTypes() {
    $this->createMediaType('video_embed_field', [
      'id' => 'test_video_2',
      'label' => 'Test Video 2',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function chooseWidget() {
    $this->getSession()->getPage()->pressButton('Create embed');
    $this->assertSession()->fieldExists('input');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvalidInput() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    parent::testInvalidInput();
    $assert_session->pageTextContains('You must enter a URL or embed code.');

    // The widget should raise an error if the input cannot match any media
    // type.
    $page->fillField('input', 'The quick brown fox gets eaten by hungry lions.');
    $page->pressButton('Update');
    $assert_session->statusCodeEquals(200);
    $page->pressButton('Place');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldNotExists('Bundle');
    $assert_session->elementExists('css', '[role="alert"]');
    $assert_session->pageTextContains("Input did not match any media types: 'The quick brown fox gets eaten by hungry lions.'");

    // The widget should not react if the input is valid, but the user does not
    // have permission to create media of the matched type.
    $page->fillField('input', 'https://twitter.com/webchick/status/824051274353999872');
    $page->pressButton('Update');
    $this->assertEmpty($page->findAll('css', '#entity *'));
  }

  /**
   * {@inheritdoc}
   */
  public function testFieldAllowedTypesSettingIsRespected() {
    // @todo: Nothing here yet.
  }

  /**
   * {@inheritdoc}
   */
  public function testDisambiguation() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/entity-browser/modal/media_browser');
    $assert_session->statusCodeEquals(200);
    $this->chooseWidget();

    $page->fillField('input', 'https://www.youtube.com/watch?v=zQ1_IbFFbzA');
    $page->pressButton('Update');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldNotExists('Name');
    $page->selectFieldOption('Bundle', 'Test Video 2');
    $page->pressButton('Update');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldNotExists('Bundle');
    $page->fillField('Name', 'Foobaz');
    $page->pressButton('Place');
    $assert_session->statusCodeEquals(200);

    $this->assertMediaCount(1, [
      'bundle' => 'test_video_2',
      'name' => 'Foobaz',
    ]);
  }

}
