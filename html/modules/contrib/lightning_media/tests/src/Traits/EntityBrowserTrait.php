<?php

namespace Drupal\Tests\lightning_media\Traits;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Drupal\views\Entity\View;

/**
 * Contains methods for interacting with entity browsers in frames.
 */
trait EntityBrowserTrait {

  /**
   * The machine name of the entity browser whose frame we are in.
   *
   * @var string
   */
  private $currentEntityBrowser;

  /**
   * Adds the "Library" widget to the entity browsers we ship.
   */
  private function addMediaLibraryToEntityBrowsers() {
    $GLOBALS['install_state'] = [];
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('media');
    lightning_media_view_insert($view);
    unset($GLOBALS['install_state']);
  }

  /**
   * Waits for an entity browser frame to load.
   *
   * @param string $id
   *   The machine name of the entity browser.
   * @param bool $switch
   *   (optional) Whether to switch into the entity browser's frame once it has
   *   loaded. Defaults to TRUE.
   */
  private function waitForEntityBrowser($id, $switch = TRUE) {
    $frame = 'entity_browser_iframe_' . $id;
    $this->assertJsCondition("window.$frame !== undefined");
    $this->assertJsCondition("window.$frame.document.readyState === 'complete'");

    if ($switch) {
      $this->getSession()->switchToIFrame($frame);
      $this->currentEntityBrowser = $id;
    }
  }

  /**
   * Waits for the current entity browser frame to close.
   *
   * @param string $id
   *   (optional) The machine name of the entity browser whose frame we are in.
   *   Defaults to the value of $this->currentEntityBrowser.
   */
  private function waitForEntityBrowserToClose($id = NULL) {
    $id = $id ?: $this->currentEntityBrowser;
    $this->assertNotEmpty($id);

    $this->getSession()->switchToIFrame(NULL);
    $this->assertJsCondition("typeof window.entity_browser_iframe_{$id} === 'undefined'");
  }

  /**
   * Waits for the current entity browser to have at least one selectable item.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The selectable items.
   */
  private function waitForItems() {
    $items = $this->getSession()
      ->getPage()
      ->waitFor(10, function (DocumentElement $page) {
        return $page->findAll('css', '[data-selectable]');
      });

    $this->assertNotEmpty($items);
    return $items;
  }

  /**
   * Selects an item in the current entity browser.
   *
   * @param \Behat\Mink\Element\NodeElement $item
   *   The item element.
   */
  private function selectItem(NodeElement $item) {
    $result = $item->waitFor(10, function (NodeElement $item) {
      $item->click();
      return $item->hasClass('selected') && $item->hasCheckedField('Select this item');
    });
    $this->assertTrue($result);
  }

}
