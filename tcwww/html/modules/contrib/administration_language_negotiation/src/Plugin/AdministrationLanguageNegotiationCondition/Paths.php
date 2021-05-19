<?php

declare(strict_types = 1);

namespace Drupal\administration_language_negotiation\Plugin\AdministrationLanguageNegotiationCondition;

use Drupal\administration_language_negotiation\AdministrationLanguageNegotiationConditionBase;
use Drupal\administration_language_negotiation\AdministrationLanguageNegotiationConditionInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\language\ConfigurableLanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for the Blacklisted paths condition plugin.
 *
 * @AdministrationLanguageNegotiationCondition(
 *   id = "paths",
 *   weight = -50,
 *   name = @Translation("Paths"),
 *   description = @Translation("Returns particular language on configured paths.")
 * )
 */
class Paths extends AdministrationLanguageNegotiationConditionBase implements
    AdministrationLanguageNegotiationConditionInterface {
  /**
   * An alias manager to find the alias for the current system path.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The configurable language manager.
   *
   * @var Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a RequestPath condition plugin.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   An alias manager to find the alias for the current system path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\language\ConfigurableLanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(
        AliasManagerInterface $alias_manager,
        PathMatcherInterface $path_matcher,
        RequestStack $request_stack,
        CurrentPathStack $current_path,
        ConfigFactory $config_factory,
        array $configuration,
        $plugin_id,
        array $plugin_definition,
        ConfigurableLanguageManager $language_manager
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
    $this->currentPath = $current_path;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()] = [
      '#type' => 'textarea',
      '#default_value' => implode(PHP_EOL, (array) $this->configuration[$this->getPluginId()]),
      '#size' => 10,
      '#description' => $this->t(
            'Specify on which paths the administration language negotiations should be circumvented.'
      ) . '<br />'
      . $this->t(
                "Specify pages by using their paths. A path must start with <em>/</em>.
                          Enter one path per line. The '*' character is a wildcard.
                          Example paths are %blog for the blog page and %blog-wildcard for every personal blog.
                          %front is the front page.",
                [
                  '%blog' => '/blog',
                  '%blog-wildcard' => '/blog/*',
                  '%front' => '<front>',
                ]
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $container->get('path_alias.manager'),
        $container->get('path.matcher'),
        $container->get('request_stack'),
        $container->get('path.current'),
        $container->get('config.factory'),
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $prefixes = $this->configFactory->get('language.negotiation')->get('url.prefixes');
    $admin_paths = array_filter($this->configuration[$this->getPluginId()]);

    foreach ($admin_paths as $admin_path) {
      foreach ($prefixes as $prefix) {
        $admin_paths[] = '/' . $prefix . '/' . trim($admin_path, '/');
      }
    }

    // Check the path against a list of paths where that the module shouldn't
    // run on.
    // This list of paths is configurable on the admin page.
    foreach ($admin_paths as $blacklisted_path) {
      $request = $this->requestStack->getCurrentRequest();
      // Compare the lowercase path alias (if any) and internal path.
      $path = $this->currentPath->getPath($request);

      // Do not trim a trailing slash if that is the complete path.
      $path = $path === '/' ? $path : rtrim($path, '/');

      // Aliases have a language property that must be used to
      // search for a match on the current path alias, or the
      // default language will be used instead.
      $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path, $langcode));

      $is_on_blacklisted_path = $this->pathMatcher->matchPath($path_alias, $blacklisted_path) ||
                (($path !== $path_alias) && $this->pathMatcher->matchPath($path, $blacklisted_path));

      if ($is_on_blacklisted_path) {
        return $this->block();
      }
    }

    return $this->pass();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $form_state->setValue(
        $this->getPluginId(),
        array_filter(
            array_map(
                'trim',
                explode(PHP_EOL, $form_state->getValue($this->getPluginId()))
            )
        )
    );
  }

}
