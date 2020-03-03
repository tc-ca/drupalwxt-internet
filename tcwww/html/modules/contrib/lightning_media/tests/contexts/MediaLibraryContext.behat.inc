<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions to interact with the administrative media library.
 *
 * @internal
 *   This is an internal part of Lightning Media's testing system and may be
 *   changed or removed at any time without warning. It should not be extended,
 *   instantiated, or used in any way by external code! If you need to use this
 *   functionality, you should copy the relevant code into your own project.
 */
final class MediaLibraryContext extends DrupalSubContextBase {

  /**
   * Names of media items created during the scenario, keyed by entity ID.
   *
   * @var string[]
   */
  private $media = [];

  /**
   * @AfterScenario
   */
  public function tearDown() {
    $storage = \Drupal::entityTypeManager()->getStorage('media');

    $items = array_keys($this->media);
    $items = $storage->loadMultiple($items);
    $storage->delete($items);
  }

  /**
   * Creates media items for the scenario.
   *
   * @Given I have items in the media library
   */
  public function createItems() {
    $storage = \Drupal::entityTypeManager()->getStorage('media');

    /** @var \Drupal\media\MediaInterface $media */
    $media = $storage->create(['bundle' => 'tweet']);
    $media
      ->setName("I'm a tweet")
      ->set('embed_code', 'https://twitter.com/50NerdsofGrey/status/757319527151636480')
      ->set('field_media_in_library', TRUE)
      ->setPublished();

    $storage->save($media);
    $id = $media->id();
    $this->media[$id] = $media->label();

    $media = $storage->create(['bundle' => 'instagram']);
    $media
      ->setName("I'm an instagram")
      ->set('embed_code', 'https://www.instagram.com/p/BaecNGYAYyP/')
      ->set('field_media_in_library', TRUE)
      ->setPublished();

    $storage->save($media);
    $id = $media->id();
    $this->media[$id] = $media->label();
  }

  /**
   * Visits the media overview (library).
   *
   * @When I visit the media library
   */
  public function visitLibrary() {
    $this->visitPath('/admin/content/media-table');

    try {
      $this->assertSession()->statusCodeEquals(200);
    }
    catch (UnsupportedDriverActionException $e) {
      // Not a critically important assertion, so just swallow the exception.
    }
  }

  /**
   * Asserts that the media overview can filter by publishing status.
   *
   * @Then I should be able to filter media by publishing status
   */
  public function assertStatusFilter() {
    $this->assertSession()->fieldExists('Published status');
  }

  /**
   * Asserts that the media overview can filter by media type.
   *
   * @Then I should be able to filter media by type
   */
  public function assertTypeFilter() {
    $assert = $this->assertSession();

    // Ensure the Type filter exists, then store its value so we can actively
    // assert that the filter works as expected.
    $filter = $assert->fieldExists('Type');
    $original_value = $filter->getValue();
    $filter->selectOption('Tweet');

    /** @var \Acquia\LightningExtension\Context\ViewsContext $context */
    $context = $this->getContext(ViewsContext::class);
    $context->applyExposedFilters();
    $assert->pageTextContains("I'm a tweet");
    $assert->pageTextNotContains("I'm an instagram");

    // Restore the original value.
    $filter->selectOption($original_value);
    $context->applyExposedFilters();
  }

  /**
   * Asserts that the media overview can filter by media name.
   *
   * @Then I should be able to filter media by name
   */
  public function assertNameFilter() {
    $this->assertSession()->fieldExists('Media name');
  }

  /**
   * Asserts that the media overview can filter by language.
   *
   * @Then I should be able to filter media by language
   */
  public function assertLanguageFilter() {
    $this->assertSession()->fieldExists('Language');
  }

  /**
   * Asserts that media can be selected and deleted from the media overview.
   *
   * @Then I should be able to select and delete media
   */
  public function assertDelete() {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $page->selectFieldOption('Action', 'Delete media');
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->pressButton('Apply to selected items');
    $page->pressButton('Delete');

    $assert->pageTextContains('Deleted 2 items.');
    array_walk($this->media, [$assert, 'pageTextNotContains']);
  }

  /**
   * Asserts that a media item is in the media library.
   *
   * @param string $title
   *   The name of the media item.
   *
   * @Then I should see :title in the media library
   */
  public function assertMediaInLibrary($title) {
    $this->visitLibrary();
    $this->assertSession()->elementExists('named', ['link', $title]);
  }

}
