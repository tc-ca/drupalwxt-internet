<?php

namespace Drupal\theme_switcher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\theme_switcher\ThemeSwitcherRuleInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for AJAX callbacks for domain actions.
 */
class ThemeSwitcherController extends ControllerBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ThemeSwitcherController constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(MessengerInterface $messenger, LoggerChannelInterface $logger) {
    $this->messenger = $messenger;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory')->get('theme_switcher')
    );
  }

  /**
   * Handles AJAX operations from the overview form.
   *
   * @param \Drupal\theme_switcher\ThemeSwitcherRuleInterface $theme_switcher_rule
   *   A Theme Switcher Rule object.
   * @param string|null $op
   *   The operation being performed, either 'enable' to enable the Theme
   *   Switcher Rule, or 'disable' to disable the Theme Switcher Rule.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to redirect back to the theme switcher rule list.
   *
   * @see \Drupal\theme_switcher\Controller\ThemeSwitcherRuleListBuilder
   */
  public function ajaxOperation(ThemeSwitcherRuleInterface $theme_switcher_rule, $op = NULL) {
    $message = $this->t("The operation '%op' to '%label' failed.",
      ['%op' => $op, '%label' => $theme_switcher_rule->label()]
    );
    try {
      switch ($op) {
        case 'enable':
          $theme_switcher_rule->enable();
          $message = $this->t("The Theme Switcher Rule '%label' has been enabled.",
            ['%label' => $theme_switcher_rule->label()]
          );
          break;

        case 'disable':
          $theme_switcher_rule->disable();
          $message = $this->t("The Theme Switcher Rule '%label' has been disabled.",
            ['%label' => $theme_switcher_rule->label()]
          );
          break;
      }
      $theme_switcher_rule->save();
      $this->messenger->addStatus($message);
      $this->logger->notice($message);
    }
    catch (EntityStorageException $e) {
      $this->messenger->addStatus($message);
      $this->logger->error($message);
    }

    // Return to the invoking page.
    $url = Url::fromRoute('theme_switcher.admin', [], ['absolute' => TRUE]);
    return new RedirectResponse($url->toString(), 302);
  }

}
