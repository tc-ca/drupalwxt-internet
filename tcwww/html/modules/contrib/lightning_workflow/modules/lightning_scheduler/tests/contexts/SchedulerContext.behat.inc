<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions for interacting with the Lightning Scheduler UI.
 *
 * @internal
 *   This is an internal part of Lightning Workflow's testing system and may be
 *   changed or removed at any time without warning. External code should not
 *   extend, instantiate, or use this class in any way! If you want to use the
 *   functionality of this class, you should copy the relevant code into your
 *   own project.
 */
final class SchedulerContext extends DrupalSubContextBase {

  /**
   * The time stamp at which the scenario began.
   *
   * @var int
   */
  private $startTime;

  /**
   * @BeforeScenario
   */
  public function setUp() {
    $this->startTime = time();
  }

  /**
   * @AfterScenario
   */
  public function tearDown() {
    \Drupal::state()->delete('lightning_scheduler.request_time');
  }

  /**
   * Schedules a transition to take place a certain number of seconds from now.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of seconds.
   *
   * @When I schedule a transition to :state in :n second(s)
   */
  public function addInSecondsFromNow($state, $n) {
    $page = $this->getSession()->getPage();

    try {
      $page->clickLink('add another');
    }
    catch (ElementNotFoundException $e) {
      $page->clickLink('Schedule a status change');
    }

    $page->selectFieldOption('Scheduled moderation state', $state);
    // These date and time formats will only work Chrome via WebDriver.
    $page->fillField('Scheduled transition date', date('m-d-Y', $this->startTime + $n));
    $page->fillField('Scheduled transition time', date('g:i:sA', $this->startTime + $n));
    $page->pressButton('Save transition');
  }

  /**
   * Runs cron by invoking the /cron URL.
   *
   * @param int $offset
   *   (optional) A number of seconds, relative to the time the scenario
   *   began. This number will be added to the time the scenario began and
   *   used as the time the cron request occurred.
   *
   * @When I run cron after :offset second(s)
   */
  public function runCron($offset = 0) {
    $state = \Drupal::state();
    $state->set('lightning_scheduler.request_time', $this->startTime + $offset);

    $url = Url::fromRoute('system.cron', [
      'key' => $state->get('system.cron_key'),
    ]);
    $url = $this->locatePath($url->toString());

    \Drupal::httpClient()->get($url);
  }

}
