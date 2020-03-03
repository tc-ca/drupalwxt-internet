<?php

namespace Acquia\LightningExtension\Context;

use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Webmozart\Assert\Assert;

/**
 * Contains step definitions for interacting with entity browser instances.
 *
 * @internal
 *   This is an internal part of Lightning Media's testing system and may be
 *   changed or removed at any time without warning. It should not be extended,
 *   instantiated, or used in any way by external code! If you need to use this
 *   functionality, you should copy the relevant code into your own project.
 */
final class EntityBrowserContext extends DrupalSubContextBase {

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
    // Check if the feature or scenario has the 'javascript' tag.
    $tags = array_merge(
      $scope->getScenario()->getTags(),
      $scope->getFeature()->getTags()
    );
    $this->isJS = in_array('javascript', $tags, TRUE);
  }

  /**
   * Gets all items in an entity browser.
   *
   * @param string $browser_id
   *   (optional) The entity browser ID.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   An array of items in the entity browser.
   */
  private function getItems($browser_id = NULL) {
    if ($browser_id) {
      $selector = 'form#entity-browser-' . Html::cleanCssIdentifier($browser_id) . '-form';
    }
    else {
      $selector = 'form[data-entity-browser-uuid]';
    }

    return $this
      ->assertSession()
      ->elementExists('css', $selector)
      ->findAll('css', '[data-selectable]');
  }

  /**
   * Selects an item in an entity browser view.
   *
   * @param int $n
   *   The one-based index of the item to select.
   * @param string $browser_id
   *   (optional) The entity browser ID.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the entity browser contains fewer than $n items.
   *
   * @When I select item :n
   * @When I select item :n from the entity browser
   * @When I select item :n from the :browser_id entity browser
   */
  public function select($n, $browser_id = NULL) {
    $items = $this->getItems($browser_id);

    if ($n > count($items)) {
      throw new ExpectationException("Expected at least $n item(s) in the $browser_id entity browser.", $this->getSession()->getDriver());
    }
    else {
      $items[--$n]->click();
    }
  }

  /**
   * Asserts that a certain number of items are visible in the entity browser.
   *
   * @param int $n
   *   The number of items that should be visible.
   * @param string $browser_id
   *   (optional) The entity browser ID.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the actual number of items in the entity browser does not match the
   *   expected number.
   *
   * @Then I should see :n item(s) in the entity browser
   */
  public function assertCount($n, $browser_id = NULL) {
    $count = count($this->getItems($browser_id));

    if ($count !== (int) $n) {
      throw new ExpectationException("Expected $n items in the $browser_id entity browser, but there were $count.", $this->getSession()->getDriver());
    }
  }

  /**
   * Submits the entity browser.
   *
   * @When I submit the entity browser
   */
  public function submit() {
    $session = $this->getSession();

    $button = $this->assertSession()
      ->elementExists('css', '[data-drupal-selector="edit-submit"]')
      ->getXpath();

    $frame = $session->evaluateScript('window.name') ?: $session->evaluateScript('window.active_iframe.name');
    Assert::notEmpty($frame);

    // Switch out of the iFrame, because it will be destroyed as soon as we
    // press the button.
    $session->switchToIFrame();

    // If there are single quotes in the button's XPath query, WebDriver needs
    // them to be escaped.
    if ($session->getDriver() instanceof Selenium2Driver) {
      $button = addslashes($button);
    }
    $js = <<<END
document.evaluate('$button', window.$frame.document, null).iterateNext().click();
END;
    $session->executeScript($js);
    $this->awaitAjax();
  }

  /**
   * Opens an entity browser.
   *
   * @param string $id
   *   The entity browser ID.
   */
  public function open($id) {
    $this->isJS ? $this->openJs($id) : $this->openNoJs($id);
  }

  /**
   * Opens an entity browser using JavaScript.
   *
   * @param string $id
   *   The entity browser ID.
   */
  private function openJs($id) {
    $settings = $this->getEntityBrowserSettings($id);

    $this->assertSession()
      ->elementExists('css', '.entity-browser-handle[data-uuid="' . $settings['uuid'] . '"]')
      ->click();

    $frame = "window.entity_browser_iframe_$id";

    $session = $this->getSession();
    Assert::true($session->wait(10000, $frame));
    Assert::true($session->wait(10000, "$frame.document.readyState === 'complete'"));
  }

  /**
   * Opens an entity browser without using JavaScript.
   *
   * @param string $id
   *   The entity browser ID.
   */
  private function openNoJs($id) {
    $settings = $this->getEntityBrowserSettings($id);
    Assert::notEmpty($settings['src']);
    $this->visitPath($settings['src']);
  }

  /**
   * Returns settings for a single entity browser.
   *
   * @param string $id
   *   The entity browser ID (not UUID).
   *
   * @return array
   *   The settings for the entity browser.
   *
   * @throws \Exception
   *   If there is not exactly one entity browser with the given ID.
   */
  private function getEntityBrowserSettings($id) {
    $filter = function (array $settings) use ($id) {
      return $settings['entity_browser_id'] === $id;
    };

    $settings = array_filter($this->getAllEntityBrowserSettings(), $filter);
    Assert::count($settings, 1);

    return reset($settings);
  }

  /**
   * Returns settings for all entity browser instances on the page.
   *
   * @return array[]
   *   The settings for all entity browser instances, keyed by UUID.
   */
  private function getAllEntityBrowserSettings() {
    $settings = $this->getAllSettings();

    Assert::isArray($settings['entity_browser']);
    Assert::notEmpty($settings['entity_browser']);

    $display_types = \Drupal::service('plugin.manager.entity_browser.display')
      ->getDefinitions();

    $settings = array_intersect_key($settings['entity_browser'], $display_types);

    $all = [];
    foreach ($settings as $display_type => $instances) {
      foreach ($instances as $uuid => $instance) {
        $instance['display_type'] = $display_type;
        $instance['uuid'] = $uuid;
        $all[$uuid] = $instance;
      }
    }
    return $all;
  }

  /**
   * Returns all Drupal JavaScript settings on the page.
   *
   * @return mixed[]
   *   The decoded settings.
   */
  private function getAllSettings() {
    $settings = $this->assertSession()
      ->elementExists('css', 'script[type="application/json"][data-drupal-selector="drupal-settings-json"]')
      ->getText();

    return Json::decode($settings);
  }

  /**
   * Selects several items from an entity browser.
   *
   * @param int $count
   *   The number of items to select.
   * @param string $id
   *   The entity browser ID.
   *
   * @When I select :count item(s) from the :id entity browser
   */
  public function selectItems($count, $id) {
    $this->isJS ? $this->switchJs($id) : $this->switchNoJs($id);
    $this->getSession()->getPage()->clickLink('Library');
    $this->awaitAjax();
    for ($n = 0; $n < $count; $n++) {
      $this->select($n + 1);
    }
    $this->submit();
    $this->awaitAjax();
  }

  /**
   * Switches to an entity browser using JavaScript.
   *
   * @param string $id
   *   The entity browser ID.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If the entity browser iframe does not appear after 30 seconds.
   */
  private function switchJs($id) {
    $session = $this->getSession();
    $page = $session->getPage();

    $page->pressButton('Add media');
    $selector = 'iframe[name="entity_browser_iframe_' . $id . '"]';

    $frame = $page->waitFor(30, function (DocumentElement $page) use ($selector) {
      return $page->find('css', $selector);
    });

    if ($page) {
      $session->switchToIFrame($frame->getAttribute('id'));
    }
    else {
      throw new ElementNotFoundException($session->getDriver(), 'frame', 'css', $selector);
    }
  }

  /**
   * Switches to an entity browser without using JavaScript.
   *
   * @param string $id
   *   The entity browser ID.
   */
  private function switchNoJs($id) {
    $settings = $this->getEntityBrowserSettings($id);
    Assert::notEmpty($settings['src']);
    $this->visitPath($settings['src']);
  }

}
