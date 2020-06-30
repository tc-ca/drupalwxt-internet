<?php

namespace Drupal\Tests\lightning_scheduler\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * @group lightning_workflow
 * @group lightning_scheduler
 */
class TimeStepTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_page',
    'lightning_workflow',
    'lightning_scheduler',
  ];

  /**
   * Tests the time steps.
   */
  public function testTimeSteps() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $steps = [
      '1 second' => [
        'time_step' => 1,
        'expected_format' => '%d%d:%d%d:%d%d',
      ],
      '1 minute' => [
        'time_step' => 60,
        'expected_format' => '%d%d:%d%d',
      ],
      '5 minutes' => [
        'time_step' => 300,
        'expected_format' => '%d%d:%d%d',
      ],
      '10 minutes' => [
        'time_step' => 600,
        'expected_format' => '%d%d:%d%d',
      ],
      '15 minutes' => [
        'time_step' => 900,
        'expected_format' => '%d%d:%d%d',
      ],
      '30 minutes' => [
        'time_step' => 1800,
        'expected_format' => '%d%d:%d%d',
      ],
      '1 hour' => [
        'time_step' => 3600,
        'expected_format' => '%d%d:00',
      ],
    ];
    $this->drupalLogin($this->createUser([], NULL, TRUE));

    foreach ($steps as $step) {
      $this->drupalGet('/admin/config/system/lightning');
      $page->clickLink('Scheduler');
      $page->selectFieldOption('time_step', $step['time_step']);
      $page->pressButton('Save configuration');

      $this->drupalGet('/node/add/page');
      $link = $assert_session->waitForLink('Schedule a status change');
      $this->assertNotEmpty($link);
      $link->click();

      $field = $assert_session->fieldExists('Scheduled transition time');
      $this->assertEquals($step['time_step'], $field->getAttribute('step'));
      $this->assertStringMatchesFormat($step['expected_format'], $field->getValue());
    }
  }

}
