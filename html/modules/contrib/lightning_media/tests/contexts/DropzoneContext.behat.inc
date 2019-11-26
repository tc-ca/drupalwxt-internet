<?php

namespace Acquia\LightningExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Webmozart\Assert\Assert;

/**
 * Contains step definitions for interacting with DropzoneJS widgets.
 *
 * @internal
 *   This is an internal part of Lightning Media's testing system and may be
 *   changed or removed at any time without warning. It should not be extended,
 *   instantiated, or used in any way by external code! If you need to use this
 *   functionality, you should copy the relevant code into your own project.
 */
final class DropzoneContext extends RawMinkContext implements DrupalSubContextInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(DrupalDriverManager $drupal) {
  }

  /**
   * Uploads multiple files into the media library using Dropzone.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $files
   *   A list of local file paths to upload.
   *
   * @When I upload the following files:
   */
  public function uploadMultiple(PyStringNode $files) {
    $page = $this->getSession()->getPage();

    $this->visitPath('/admin/content/media');
    $page->clickLink('Bulk upload');
    // Wait for the dropzone to be initialized.
    sleep(3);

    $files = $files->getStrings();

    foreach ($files as $file) {
      $file = __DIR__ . '/../files/' . $file;
      Assert::fileExists($file);

      $session = $this->getSession();
      $session->executeScript('Dropzone.instances[0].hiddenFileInput.name = "file"');
      $page->attachFileToField('file', $file);

      // @todo: React when the upload actually completes.
      sleep(3);
    }
    $page->pressButton('Continue');

    for ($i = 0; $i < count($files); $i++) {
      $page->pressButton('Save');
    }
  }

}
