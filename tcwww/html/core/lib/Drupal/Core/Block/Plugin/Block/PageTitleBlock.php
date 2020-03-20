<?php

namespace Drupal\Core\Block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a block to display the page title.
 *
 * @Block(
 *   id = "page_title_block",
 *   admin_label = @Translation("Page title"),
 *   forms = {
 *     "settings_tray" = FALSE,
 *   },
 * )
 */
class PageTitleBlock extends BlockBase implements TitleBlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array|null
   */
  protected $title = NULL;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Constructs a PageTitleBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, RouteMatchInterface $route_match, TitleResolverInterface $title_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
    $this->routeMatch = $route_match;
    $this->titleResolver = $title_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_route_match'),
      $container->get('title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Deprecate this method in https://www.drupal.org/node/2359901.
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'page_title',
      // @todo Always use the title resolver directly after
      //   https://www.drupal.org/node/2359901 is resolved.
      '#title' => !is_null($this->title) ? $this->title : $this->titleResolver->getTitle($this->request, $this->routeMatch->getRouteObject()),
    ];
  }

}
