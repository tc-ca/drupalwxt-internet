<?php

namespace Drupal\Tests\blazy\FunctionalJavascript;

/**
 * Tests the Blazy bLazy JavaScript using PhantomJS, or Chromedriver.
 *
 * @group blazy
 */
class BlazyBlazyJavaScriptTest extends BlazyJavaScriptTestBase {

  /**
   * Test the Blazy element from loading to loaded states.
   */
  public function testFormatterDisplay() {
    $data['settings']['blazy'] = TRUE;
    $data['settings']['ratio'] = '';
    $data['settings']['image_style'] = 'thumbnail';

    $this->setUpContentTypeTest($this->bundle);
    $this->setUpFormatterDisplay($this->bundle, $data);
    $this->setUpContentWithItems($this->bundle);

    $this->drupalGet('node/' . $this->entity->id());

    // Ensures Blazy is not loaded on page load.
    $this->assertSession()->elementNotExists('css', '.b-loaded');

    $this->doTestFormatterDisplay();
  }

}
