<?php

namespace Drupal\lightning_scheduler;

use Drupal\Component\Datetime\Time as BaseTime;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decorates the time service to facilitate testing.
 */
class Time extends BaseTime {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * Time constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(RequestStack $request_stack, StateInterface $state) {
    parent::__construct($request_stack);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return $this->state->get('lightning_scheduler.request_time', parent::getRequestTime());
  }

}
