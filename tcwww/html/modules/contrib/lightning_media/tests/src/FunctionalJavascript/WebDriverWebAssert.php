<?php

namespace Drupal\Tests\lightning_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverWebAssert as BaseWebDriverWebAssert;
use PHPUnit\Framework\Assert;

/**
 * Contains asynchronous assertions.
 */
class WebDriverWebAssert extends BaseWebDriverWebAssert {

  /**
   * {@inheritdoc}
   */
  public function waitForButton($locator, $timeout = 10000) {
    $button = parent::waitForButton($locator, $timeout);
    Assert::assertNotEmpty($button);
    return $button;
  }

  /**
   * {@inheritdoc}
   */
  public function waitForElement($selector, $locator, $timeout = 10000) {
    $element = parent::waitForElement($selector, $locator, $timeout);
    Assert::assertNotEmpty($element);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function waitForField($locator, $timeout = 10000) {
    $field = parent::waitForField($locator, $timeout);
    Assert::assertNotEmpty($field);
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function waitForLink($locator, $timeout = 10000) {
    $link = parent::waitForLink($locator, $timeout);
    Assert::assertNotEmpty($link);
    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function waitForText($text, $timeout = 10000) {
    $result = parent::waitForText($text, $timeout);
    Assert::assertNotEmpty($result);
  }

}
