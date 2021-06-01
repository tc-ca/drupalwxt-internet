<?php

namespace Drupal\insert_view_adv\Plugin\Filter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\views\Views;

/**
 * Provides a filter for insert view.
 *
 * @Filter(
 *   id = "insert_view_adv",
 *   module = "insert_view_adv",
 *   title = @Translation("Advanced Insert View"),
 *   description = @Translation("Allows to embed views using the simple syntax:[view:name=display=args]"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "allowed_views" = {},
 *     "render_as_empty" = 0,
 *     "hide_argument_input" = 0,
 *   }
 * )
 */
class InsertView extends FilterBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $matches = [];
    // Encode configuration to path to build method because lazy_loader only works with scalar arguments
    $encoded_configuration = Json::encode($this->getConfiguration());
    $result = new FilterProcessResult($text);
    // Check first the direct input of shortcode.
    $count = preg_match_all("/\[view:([^=\]]+)=?([^=\]]+)?=?([^\]]*)?\]/i", $text, $matches);
    // Keep track of the number of times a view was inserted.
    $insert_view_count = 0;

    if ($count) {
      $search = $replace = [];
      foreach ($matches[0] as $key => $value) {
        $view_name = $matches[1][$key];
        $display_id = ($matches[2][$key] && !is_numeric($matches[2][$key])) ? $matches[2][$key] : 'default';
        $args = $matches[3][$key];
        // Do not allow arguments if they are forbidden to input.
        if (!empty($this->settings['hide_argument_input'])) {
          $args = '';
        }
        $view_output = $result->createPlaceholder('\Drupal\insert_view_adv\Plugin\Filter\InsertView::build', [
          $view_name,
          $display_id,
          $args,
          $encoded_configuration
        ]);
        $search[] = $value;
        $replace[] = $view_output;
      }
      $text = str_replace($search, $replace, $text, $insert_view_count);
    }
    // Check the view inserted from the CKeditor plugin.
    $count = preg_match_all('/(<p>)?(?<json>{(?=.*inserted_view_adv\b)(?=.*arguments\b)(.*)})(<\/p>)?/', $text, $matches);
    if ($count) {
      $search = $replace = [];
      foreach ($matches['json'] as $key => $value) {
        $inserted = Json::decode($value);
        if (!is_array($inserted) || empty($inserted)) {
          continue;
        }
        $view_parts = explode('=', $inserted['inserted_view_adv']);
        if (empty($view_parts)) {
          continue;
        }
        $view_name = $view_parts[0];
        $display_id = ($view_parts[1] && !is_numeric($view_parts[1])) ? $view_parts[1] : 'default';
        $args = '';
        if (!empty($inserted['arguments']) && empty($this->settings['hide_argument_input'])) {
          $args = implode('/', $inserted['arguments']);
        }
        $view_output = $result->createPlaceholder('\Drupal\insert_view_adv\Plugin\Filter\InsertView::build', [
          $view_name,
          $display_id,
          $args,
          $encoded_configuration
        ]);
        $search[] = $value;
        $replace[] = $view_output;
      }
      $text = str_replace($search, $replace, $text, $insert_view_count);
    }
    // If views were actually inserted, then update the processed text and add
    // cache tags and contexts. This check is important because cache tags and
    // contexts may be incorrectly added to a render array and cause
    // unnecessary cache variations.
    if ($insert_view_count > 0) {
      $result->setProcessedText($text)->addCacheTags(['insert_view_adv'])->addCacheContexts(['url', 'user.permissions']);
    }

    return $result;
  }

  /**
   * Builds the view markup from the data received from the token.
   *
   * @param string $view_name
   *   The machine name of the view.
   * @param string $display_id
   *   The name of the display to show.
   * @param string $args
   *   The arguments that are passed to the view in format arg1/arg2/arg3/... .
   * @param string $configuration
   *   Json encoded string of the filter configuration.
   *
   * @return array
   *   The rendered array of the view to display.
   */
  public static function build($view_name, $display_id, $args, $configuration) {
    $plain = '';
    // Just in case check if this is an array already.
    if (!is_array($configuration)) {
      $configuration = Json::decode($configuration);
    }
    // Check what to do if the render array is empty and there is nothing to show.
    if ($configuration && isset($configuration['settings']['render_as_empty']) && $configuration['settings']['render_as_empty'] == 0) {
      $plain = '[view:' . $view_name . '=' . $display_id;
      if (!empty($args)) {
        $plain .= '=' . $args;
      }
      $plain .= ']';
    }
    // Do nothing if there is no view name provided.
    if (empty($view_name)) {
      return ['#attached' => [], '#markup' => $plain];
    }
    // Do not render the views that are not allowed to be printed.
    if ($configuration && !empty($configuration['settings']['allowed_views'])) {
      $allowed_views = array_filter($configuration['settings']['allowed_views']);
      if (!empty($allowed_views) && empty($allowed_views[$view_name . '=' . $display_id])) {
        return ['#attached' => [], '#markup' => $plain];
      }
    }
    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getView($view_name);
    if (empty($view)) {
      return ['#attached' => [], '#markup' => $plain];
    }
    // Check if the current user has access to the given view.
    if (!$view->access($display_id)) {
      return ['#attached' => [], '#markup' => $plain];
    }
    $view->setDisplay($display_id);
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    $current_path = $request->getPathInfo();
    // Workaround for exposed filter reset button.
    // Because of exposed form redirect on reset, lazyloading throws
    // Drupal\Core\Form\EnforcedResponseException. For this reason we need to
    // perform this redirect with javascript without rendering the form.
    if (\Drupal::currentUser()->isAuthenticated() && \Drupal::moduleHandler()->moduleExists('big_pipe')) {
      $op = $request->get('op');
      $display_options = $view->display_handler->getOption('exposed_form');
      if (!is_null($op) && !empty($display_options) && $op == $display_options['options']['reset_button_label']) {
        return [
          '#attached' => [
            'drupalSettings' => [
              'insert_view_adv' => [
                'reset_redirect' => $current_path,
              ],
            ],
            'library' => [
              'insert_view_adv/reset_redirect'
            ]
          ],
        ];
      }
    }
    // Try to get the arguments from the current path.
    $url_args = explode('/', $current_path);
    foreach ($url_args as $id => $arg) {
      $args = str_replace("%$id", $arg, $args);
    }
    $args = preg_replace(',/?(%\d),', '', $args);
    $args = $args ? explode('/', $args) : [];

    return $view->preview($display_id, $args) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $examples = [
        '[view:my_view]',
        '[view:my_view=my_display]',
        '[view:my_view=my_display=arg1/arg2/arg3]',
        '[view:my_view==arg1/arg2/arg3]',
      ];
      $items = [
        $this->t('Insert view filter allows to embed views using tags. The tag syntax is relatively simple: [view:name=display=args]'),
        $this->t('For example [view:tracker=page=1] says, embed a view named "tracker", use the "page" display, and supply the argument "1".'),
        $this->t('The <em>display</em> and <em>args</em> parameters can be omitted. If the display is left empty, the view\'s default display is used.'),
        $this->t('Multiple arguments are separated with slash. The <em>args</em> format is the same as used in the URL (or view preview screen).'),
        [
          'data' => $this->t('Valid examples'),
          'children' => $examples,
        ],
      ];
      $list = [
        '#type' => 'item_list',
        '#items' => $items,
      ];
      return render($list);
    }
    else {
      return $this->t('You may use [view:<em>name=display=args</em>] tags to display views.');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $views_list = Views::getEnabledViews();
    $options = [];
    foreach ($views_list as $machine_name => $view) {
      foreach ($view->get('display') as $display) {
        $display_title = !empty($display['display_options']['title']) ? $display['display_options']['title'] : $display['display_title'];
        $options[$machine_name . '=' . $display['id']] = $this->t('@view_name: @display_title (@display_name)', ['@view_name' => $view->label(), '@display_title' => $display_title, '@display_name' => $display['id']]);
      }
    }
    $form['allowed_views'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed views to insert'),
      '#description' => $this->t('Leave empty to allow all views'),
      '#options' => $options,
      '#default_value' => $this->settings['allowed_views'],
    ];
    $form['render_as_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not render disabled/not allowed views'),
      '#default_value' => $this->settings['render_as_empty'],
      '#description' => $this->t('If unchecked the disabled/not allowed view will be rendered as token [view:view_name=display_id=args]'),
    ];
    $form['hide_argument_input'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide views arguments (contextual filters) input'),
      '#default_value' => $this->settings['hide_argument_input'],
      '#description' => $this->t('If checked the user will not be allowed to input the argument values, only default will be used.'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function setConfiguration(array $configuration) {
    // Filter out "allowed_views" options that are not actually selected.
    // Otherwise, they are exported as part of the filter configuration even if
    // the text format does not use the insert_view_adv filter. They are also
    // rendered per views insert as part of the cache_render entry since each
    // enabled view on the site gets an entry in the filter plugin settings.
    if (!empty($configuration['settings']['allowed_views'])) {
      $configuration['settings']['allowed_views'] = array_filter($configuration['settings']['allowed_views']);
    }
    parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['build'];
  }

}
