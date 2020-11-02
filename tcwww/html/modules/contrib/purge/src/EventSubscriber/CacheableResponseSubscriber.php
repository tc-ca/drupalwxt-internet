<?php

namespace Drupal\purge\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Add cache tags headers on cacheable responses, for external caching systems.
 */
class CacheableResponseSubscriber implements EventSubscriberInterface {

  /**
   * The tagsheaders service for iterating the available header plugins.
   *
   * @var \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface
   */
  protected $purgeTagsHeaders;

  /**
   * Construct a CacheableResponseSubscriber object.
   *
   * @param \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface $purge_tagsheaders
   *   The tagsheaders service for iterating the available header plugins.
   */
  public function __construct(TagsHeadersServiceInterface $purge_tagsheaders) {
    $this->purgeTagsHeaders = $purge_tagsheaders;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

  /**
   * Add cache tags headers on cacheable responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    // Only set any headers when this is a cacheable response.
    $response = $event->getResponse();
    if ($response instanceof CacheableResponseInterface) {

      // Retrieve and process tags.
      $tags = $response->getCacheableMetadata()->getCacheTags();
      $blacklist = $this->configFactory->get('purge.tagsheaders')->get('blacklist');
      $blacklist = is_array($blacklist) ? $blacklist : [];
      $tags = array_filter($tags, function($tag) use ($blacklist) {
        foreach ($blacklist as $prefix) {
          if (strpos($tag, $prefix) !== FALSE) {
            return FALSE;
          }
        }
        return TRUE;
      });

      // Iterate all tagsheader plugins and add a header for each plugin.
      foreach ($this->purgeTagsHeaders as $header) {
        if ($header->isEnabled()) {

          // Retrieve the header name and perform a few simple sanity checks.
          $name = $header->getHeaderName();
          if ((!is_string($name)) || empty(trim($name))) {
            $plugin_id = $header->getPluginId();
            throw new \LogicException("Header plugin '$plugin_id' should return a non-empty string on ::getHeaderName()!");
          }

          $response->headers->set($name, $header->getValue($tags));
        }
      }
    }
  }

}
