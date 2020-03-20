<?php

namespace Drupal\business_rules\EventSubscriber;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\Events\BusinessRulesDrushEvent;
use Drupal\business_rules\Util\BusinessRulesProcessor;
use Drupal\business_rules\Util\BusinessRulesUtil;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class BusinessRulesListener.
 *
 * @package Drupal\business_rules\EventSubscriber
 */
class BusinessRulesListener implements EventSubscriberInterface {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private static $container;

  /**
   * The business rule processor.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesProcessor
   */
  private $processor;

  /**
   * The Business Rules Util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  private $util;

  /**
   * The eventDispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * A LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * A ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Events to subscribe if container is not available. See #3093597.
   *
   * @var array
   */
  private static $staticEvents = [
      KernelEvents::CONTROLLER => ['registerDynamicEvents', 100],
      KernelEvents::REQUEST => ['registerDynamicEvents', 100],
      KernelEvents::TERMINATE => ['registerDynamicEvents', 100],
      KernelEvents::VIEW => ['registerDynamicEvents', 100],
      BusinessRulesDrushEvent::DRUSHINIT => ['registerDynamicEvents', 100],
    ];

  /**
   * BusinessRulesListener constructor.
   *
   * @param \Drupal\business_rules\Util\BusinessRulesProcessor $processor
   *   The business rule processor service.
   * @param \Drupal\business_rules\Util\BusinessRulesUtil $util
   *   The business rule util.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The logger channel.
   */
  public function __construct(BusinessRulesProcessor $processor, BusinessRulesUtil $util, EventDispatcherInterface $eventDispatcher, LoggerChannelFactoryInterface $loggerChannelFactory, ModuleHandlerInterface $moduleHandler) {
    $this->util      = $util;
    $this->processor = $processor;
    $this->eventDispatcher = $eventDispatcher;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Sets the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface|null $container
   *   A ContainerInterface instance or null.
   */
  public static function setContainer(ContainerInterface $container = NULL) {
    self::$container = $container;
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $return['business_rules.item_pos_delete'] = 'itemPosDelete';
    $return[KernelEvents::TERMINATE][]        = ['onTerminate', 100];

    // If there is no state service there is nothing we can do here. This static
    // method could be called early when the container is built, so the state
    // service might not always be available.
    if (!\Drupal::hasService('state')) {
      return self::$staticEvents;
    }

    // If there is no container service there is not possible to load any event.
    // As this method can be called before the container is ready, it might not
    // be available.
    // To avoid the necessity to manually clear all caches via user interface,
    // we are getting the plugin definition using this ugly way.
    if (!\Drupal::hasContainer() || !\Drupal::hasService('plugin.manager.business_rules.reacts_on')) {

      $query = Database::getConnection()
        ->query('SELECT value FROM {key_value} WHERE collection = :collection AND name = :name', [
          ':collection' => 'state',
          ':name'       => 'system.module.files',
        ])
        ->fetchCol();

      $modules = [];
      if (isset($query[0])) {
        $modules = unserialize($query[0]);
      }

      foreach ($modules as $name => $module) {
        $arr = explode('/', $module);
        unset($arr[count($arr) - 1]);
        $path = implode('/', $arr);

        // Skip core modules.
        if ($arr[0] != 'core') {
          $root_namespaces["Drupal\\$name"] = "$path/src";
        }
      }

      $root_namespaces['_serviceId'] = 'container.namespaces';

      $root_namespaces   = new \ArrayIterator($root_namespaces);
      $annotation        = new AnnotatedClassDiscovery('/Plugin/BusinessRulesReactsOn', $root_namespaces, 'Drupal\business_rules\Annotation\BusinessRulesReactsOn');
      $eventsDefinitions = $annotation->getDefinitions();
    }
    else {
      // If we have the container, we can get the definitions using the correct
      // process.
      $container         = \Drupal::getContainer();
      $reactionEvents    = $container->get('plugin.manager.business_rules.reacts_on');
      $eventsDefinitions = $reactionEvents->getDefinitions();
    }

    foreach ($eventsDefinitions as $event) {
      $return[$event['eventName']] = [
        'process',
        $event['priority'],
      ];
    }

    return $return;

  }

  /**
   * Rebuilds container when dynamic rule eventsubscribers are not registered.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event object.
   * @param string $event_name
   *   The event name.
   */
  public function registerDynamicEvents(Event $event, $event_name) {
    foreach (self::$staticEvents as $old_event_name => $method) {
      $this->eventDispatcher->removeListener($old_event_name, [$this, $method[0]]);
    }
    $this->eventDispatcher->addSubscriber($this);
    $this->moduleHandler->reload();
  }

  /**
   * Process the rules.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event to be processed.
   */
  public function process(BusinessRulesEvent $event) {
    $this->processor->process($event);
  }

  /**
   * Remove the item references.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event.
   */
  public function itemPosDelete(BusinessRulesEvent $event) {
    $this->util->removeItemReferences($event);
  }

  /**
   * Run the necessary commands on terminate event.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The terminate event.
   */
  public function onTerminate(Event $event) {
    // $key_value = \Drupal::keyValueExpirable('business_rules.debug');
    // $key_value->deleteAll();
  }

}
