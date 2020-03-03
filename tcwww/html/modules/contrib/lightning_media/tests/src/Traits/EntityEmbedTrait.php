<?php

namespace Drupal\Tests\lightning_media\Traits;

use Behat\Mink\Element\DocumentElement;

/**
 * Contains helper methods for interacting with Entity Embed.
 */
trait EntityEmbedTrait {

  /**
   * Waits for the entity embed form to appear.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The entity embed form.
   */
  private function waitForEmbedForm() {
    $embed_form = $this->assertSession()->waitForElement('css', 'form.entity-embed-dialog.entity-embed-dialog-step--embed');
    $this->assertNotEmpty($embed_form);
    return $embed_form;
  }

  /**
   * Waits for an image-based entity embed form to appear.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The entity embed form.
   */
  private function waitForImageEmbedForm() {
    $assert_session = $this->assertSession();

    $embed_form = $this->waitForEmbedForm();
    $assert_session->selectExists('Image style', $embed_form);
    $assert_session->fieldExists('Alternate text', $embed_form);
    $assert_session->fieldExists('Title', $embed_form);

    return $embed_form;
  }

  /**
   * Waits for a non-image based entity embed form to appear.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The entity embed form.
   */
  private function waitForStandardEmbedForm() {
    $assert_session = $this->assertSession();

    $embed_form = $this->waitForEmbedForm();
    $assert_session->fieldNotExists('Image style', $embed_form);
    $assert_session->fieldNotExists('Alternate text', $embed_form);
    $assert_session->fieldNotExists('Title', $embed_form);

    return $embed_form;
  }

  /**
   * Presses the "Embed" button in the embed form, then waits for it to vanish.
   */
  private function submitEmbedForm() {
    $this->waitForEmbedForm();

    // Don't click the Embed button *in* the form, because it is hidden by
    // Drupal's dialog system.
    $this->assertSession()
      ->elementExists('css', '.ui-dialog-buttonpane')
      ->pressButton('Embed');

    $result = $this->getSession()
      ->getPage()
      ->waitFor(10, function (DocumentElement $page) {
        return $page->find('css', '.ui-dialog') == NULL;
      });

    $this->assertTrue($result);
  }

}
