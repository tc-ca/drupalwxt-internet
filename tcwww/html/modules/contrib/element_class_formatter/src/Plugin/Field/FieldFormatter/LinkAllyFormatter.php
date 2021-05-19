<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a formatter that allows links with screenreader only text.
 *
 * @FieldFormatter(
 *   id = "link_ally_class",
 *   label = @Translation("Link (with screenreader text)"),
 *   field_types = {
 *     "link",
 *     "string",
 *   }
 * )
 */
class LinkAllyFormatter extends FormatterBase {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  use ElementClassTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->token = $container->get('token');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = parent::defaultSettings() + [
      'link_text' => '',
      'screenreader_text' => '',
      'tag' => '',
    ];

    return ElementClassTrait::elementClassDefaultSettings($default_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['link_text'] = [
      '#title' => $this->t('Link text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_text'),
      '#description' => $this->t('Custom link text - leave empty to use the field value.'),
    ];
    $elements['screenreader_text'] = [
      '#title' => $this->t('Screenreader text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('screenreader_text'),
      '#description' => $this->t('Screenreader text - tokens are available.'),
    ];
    $class = $this->getSetting('class');
    $wrapper_options = [
      'span' => 'span',
      'div' => 'div',
      'p' => 'p',
    ];
    foreach (range(1, 5) as $level) {
      $wrapper_options['h' . $level] = 'H' . $level;
    }

    $elements['tag'] = [
      '#title' => $this->t('Tag'),
      '#type' => 'select',
      '#description' => $this->t('Select an optional tag that will be wrapped around the link.'),
      '#options' => $wrapper_options,
      '#default_value' => $this->getSetting('tag'),
      '#empty_value' => '',
      '#empty_option' => $this->t('None'),
    ];
    return $this->elementClassSettingsForm($elements, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $class = $this->getSetting('class');
    if ($link_text = $this->getSetting('link_text')) {
      $summary[] = $this->t('Link text: @text', [
        '@text' => $link_text,
      ]);
    }
    if ($screenreader_text = $this->getSetting('screenreader_text')) {
      $summary[] = $this->t('Screenreader text: @text', [
        '@text' => $screenreader_text,
      ]);
    }
    if ($tag = $this->getSetting('tag')) {
      $summary[] = $this->t('Tag: @tag', ['@tag' => $tag]);
    }

    return $this->elementClassSettingsSummary($summary, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    $entity = $items->getEntity();
    $custom_link_text = $this->getSetting('link_text');

    $cache = new BubbleableMetadata();
    $screenreader_text = trim($this->token->replace($this->getSetting('screenreader_text'), [
      $entity->getEntityTypeId() => $entity,
    ], [], $cache));
    if ($screenreader_text) {
      $screenreader_text = [
        '#type' => 'inline_template',
        '#template' => '<span class="visually-hidden">{{screenreader_text}}</span>',
        '#context' => [
          'screenreader_text' => [
            '#markup' => $screenreader_text,
          ],
        ],
      ];
    }
    foreach ($items as $delta => $item) {
      $label = $items->getFieldDefinition()->getType() === 'link' ? $item->title : $item->value;
      $uri = $items->getFieldDefinition()->getType() === 'link' ? ($item->getUrl() ?: Url::fromRoute('<none>')) : $entity->toUrl('canonical');
      if ($custom_link_text) {
        $label = $custom_link_text;
      }
      if ($screenreader_text) {
        $label = [
          ['#plain_text' => $label],
          $screenreader_text,
        ];
      }
      $build[$delta] = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => $uri,
        '#options' => $uri->getOptions(),
      ];
    }
    $cache->applyTo($build);
    $build = $this->setElementClass($build, $this->getSetting('class'), $items);
    if ($tag = $this->getSetting('tag')) {
      foreach (Element::children($build) as $delta) {
        $build[$delta] = [
          '#type' => 'inline_template',
          '#template' => '<{{tag}}>{{element}}</{{tag}}>',
          '#context' => [
            'tag' => $tag,
            'element' => $build[$delta],
          ],
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $manager = \Drupal::entityTypeManager();
    $type_id = $field_definition->getTargetEntityTypeId();
    // Either this is a link field, or this is an entity-type that has a
    // canonical route.
    return parent::isApplicable($field_definition) && ($field_definition->getType() === 'link' || (
      $manager->hasDefinition($type_id) &&
      ($type = $manager->getDefinition($type_id)) &&
      $type->hasLinkTemplate('canonical')));
  }

}
