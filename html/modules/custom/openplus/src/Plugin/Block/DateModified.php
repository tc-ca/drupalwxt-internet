<?php
  
namespace Drupal\openplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the date modified Block.
 *
 * @Block(
 *   id = "op_date_modified",
 *   admin_label = @Translation("Date modified (field_date_published) block"),
 *   category = @Translation("Openplus"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */
class DateModified extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        DateFormatter $date_formatter
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $date_formats = [
      'tiny' => $this->t('Tiny'),
      'short' => $this->t('Short'),
      'medium' => $this->t('Medium'),
      'long' => $this->t('Long'),
    ];
    $form['date_modified'] = [
      '#type' => 'select',
      '#title' => $this->t('Date Modified'),
      '#description' => $this->t('Date Modified block formats.'),
      '#options' => $date_formats,
      '#default_value' => is_null($config['date_modified']) ? '' : $config['date_modified'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $modified = $form_state->getValue('date_modified');
    $this->configuration['date_modified'] = $modified;
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $format = $this->configuration['date_modified'];
    $time = REQUEST_TIME;

    // Node context.
    if (is_object($node)) {
      if ($node->hasField('field_date_published') && !$node->get('field_date_published')->isEmpty()) {
        $value = $node->get('field_date_published')->getValue();
        $time = strtotime($value[0]['value']);
      }
      else {
        $time = $node->getChangedTime();
      }
    }

    // Formatting of date.
    if ($format == 'tiny') {
      $formatted_date = 'Y-m-d';
    }
    else {
      $formatted_date = DateFormat::load($format)->getPattern();;
    }
    $date = $this->dateFormatter->format($time, 'custom', $formatted_date);

    $build = [];
    $build['date_modified_block']['#markup'] = '<div class="datemod mrgn-bttm-lg"><dl id="wb-dtmd">' . "\n";
    $build['date_modified_block']['#markup'] .= '<dt>' . $this->t('Date modified:') . '</dt>' . "\n";
    $build['date_modified_block']['#markup'] .= '<dd><time property="dateModified">' . $date . '</time></dd>';
    $build['date_modified_block']['#markup'] .= '</dl></div>';

    return $build;

  }

}

