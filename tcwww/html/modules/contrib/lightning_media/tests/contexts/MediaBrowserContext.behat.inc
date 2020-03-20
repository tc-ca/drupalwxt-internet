<?php

namespace Acquia\LightningExtension\Context;

use Behat\Behat\Hook\Scope\ScenarioScope;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\MinkContext;
use Webmozart\Assert\Assert;

/**
 * Contains step definitions for interacting with Lightning's media browser.
 *
 * @internal
 *   This is an internal part of Lightning Media's testing system and may be
 *   changed or removed at any time without warning. It should not be extended,
 *   instantiated, or used in any way by external code! If you need to use this
 *   functionality, you should copy the relevant code into your own project.
 */
final class MediaBrowserContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Indicates if the current scenario uses JavaScript.
   *
   * @var bool
   */
  private $isJS;

  /**
   * Performs pre-scenario tasks.
   *
   * @BeforeScenario
   */
  public function setUp(ScenarioScope $scope) {
    // Set the MinkContext's files_path parameter so that the "I attach the file
    // :file to :field"'s :file parameter can be given relative to the test
    // files' directory.
    $mink_context = $this->getContext(MinkContext::class);
    if ($mink_context instanceof MinkContext) {
      $path = $mink_context->getMinkParameter('files_path');

      if (empty($path)) {
        $mink_context->setMinkParameter('files_path', __DIR__ . '/../files');
      }
    }

    // Check if the feature or scenario has the 'javascript' tag.
    $tags = array_merge(
      $scope->getScenario()->getTags(),
      $scope->getFeature()->getTags()
    );
    $this->isJS = in_array('javascript', $tags, TRUE);
  }

  /**
   * Opens the media browser, obviously.
   *
   * @param string $button
   *   (optional) The embed button ID.
   *
   * @When I open the media browser
   */
  public function open($button = 'media_browser') {
    $this->isJS ? $this->openJs($button) : $this->openNoJs();
  }

  /**
   * Opens the media browser when JavaScript is enabled.
   *
   * @param string $button
   *   (optional) The embed button ID.
   * @param string $browser
   *   (optional) The entity browser ID.
   */
  private function openJs($button = 'media_browser', $browser = 'ckeditor_media_browser') {
    $this
      ->getContext(CkEditorContext::class)
      ->execute('editdrupalentity', NULL, [
        'id' => $button,
      ]);

    $frame = $this->awaitElement("iframe[name='entity_browser_iframe_$browser']", 30)
      ->getAttribute('name');

    $this->getSession()->switchToIFrame($frame);
  }

  /**
   * Opens the media browser without JavaScript.
   *
   * @param string $browser_id
   *   (optional) The entity browser ID.
   */
  private function openNoJs($browser_id = 'media_browser') {
    $uuid = $this->assertSession()
      ->elementExists('named', ['button', 'Add media'])
      ->getAttribute('data-uuid');

    $this->visitPath("/entity-browser/modal/$browser_id?uuid=$uuid");
  }

  /**
   * Selects an item from the media browser.
   *
   * @param int $n
   *   The one-based index of the item to select.
   *
   * @When I select item :n in the media browser
   */
  public function selectN($n) {
    $this->getContext(EntityBrowserContext::class)->select($n, 'media_browser');
  }

  /**
   * Completes the media browser selection.
   *
   * @When I complete the media browser selection
   */
  public function completeSelection() {
    $assert = $this->assertSession();
    $session = $this->getSession();

    $frame = $session->evaluateScript('window.name') ?: $session->evaluateScript('window.active_iframe.name');
    Assert::notEmpty($frame);

    $button = $assert->elementExists('named', ['button', 'Place'])->getXpath();

    // Switch out of the iFrame, because it will be destroyed as soon as we
    // press the button.
    $session->switchToIFrame();
    $js = <<<END
document.evaluate('$button', window.$frame.document, null).iterateNext().click();
END;
    $session->executeScript($js);

    $this->awaitElement('form.entity-embed-dialog');
    $this->getSession()->getPage()->pressButton('Embed');
    $this->awaitAjax();
  }

  /**
   * Enters an embed code in the media browser.
   *
   * @param string $code
   *   The embed code.
   *
   * @When I enter embed code :code
   */
  public function embed($code) {
    // Activate the 'Create embed' tab. We cannot use the link text because it
    // may change between versions of Lightning (as in commit 48fa57e), but the
    // UUID won't.
    $this->assertSession()
      ->elementExists('css', 'nav.eb-tabs ul li a[data-button-id="edit-tab-selector-8b142f33-59d1-47b1-9e3a-4ae85d8376fa"]')
      ->click();

    $this->getSession()->getPage()->fillField('input', $code);

    // The change event, which triggers AJAX, is fired automatically after 600
    // milliseconds.
    sleep(1);
    $this->awaitAjax();
    $this->awaitExpression('jQuery("#entity").children().length');
  }

  /**
   * Uploads a file in the media browser.
   *
   * @param string $file
   *   The path to the file, relative to the test files directory.
   *
   * @When I upload :file
   */
  public function upload($file) {
    $file = __DIR__ . '/../files/' . $file;
    Assert::fileExists($file);

    $this->isJS ? $this->uploadJs($file) : $this->uploadNoJs($file);
  }

  /**
   * Uploads a file in the media browser using JavaScript.
   *
   * @param string $file
   *   The absolute path to the file.
   */
  private function uploadJs($file) {
    $page = $this->getSession()->getPage();

    $page->clickLink('Upload');
    $page->attachFileToField('File', $file);
    $this->awaitExpression('jQuery("#entity").children().length');
  }

  /**
   * Uploads a file in the media browser without using JavaScript.
   *
   * @param string $file
   *   The absolute path to the file.
   */
  private function uploadNoJs($file) {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    // Switch to the "Upload" tab of the media browser, which should be the
    // first button named "Upload" on the page.
    $page->pressButton('Upload');

    $page->attachFileToField('File', $file);
    $assert->elementExists('css', '.js-form-managed-file')->pressButton('Upload');
  }

  /**
   * Creates a media item from a file upload.
   *
   * @param string $title
   *   The name of the media item to create.
   * @param string $file
   *   The local path to the file to upload.
   *
   * @When I create media named :title by uploading :file
   */
  public function createFromUpload($title, $file) {
    $this->visitPath('/entity-browser/modal/media_browser');

    $this->upload($file);

    // If the file is an image, assert that cropping is available.
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    $extension = strtolower($extension);

    if (in_array($extension, ['jpg', 'jpeg', 'gif', 'png'])) {
      /** @var \Acquia\LightningExtension\Context\ImageBrowserContext $context */
      $context = $this->getContext(ImageBrowserContext::class);
      $context->assertCrop();
    }

    $page = $this->getSession()->getPage();
    $page->fillField('Name', $title);
    $page->pressButton('Place');
  }

  /**
   * Creates a media item in the media browser using an embed code.
   *
   * @param string $title
   *   The label of the created media item.
   * @param string $embed_code
   *   The embed code from which to create the media item.
   *
   * @When I create media named :title using the embed code :embed_code
   */
  public function createFromEmbedCode($title, $embed_code) {
    $this->visitPath('/entity-browser/modal/media_browser');

    $page = $this->getSession()->getPage();
    $page->pressButton('Create embed');
    $page->fillField('input', $embed_code);
    $page->pressButton('Update');
    $page->fillField('Name', $title);
    $page->pressButton('Place');
  }

}
