<?php

namespace Drupal\cshs;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Html;
use Drupal\cshs\Element\CshsElement;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * @internal DO NOT refer to this constant in your code as it is most likely
 * to be removed.
 */
const HIERARCHY_OPTIONS = [
  'hierarchy_depth' => [
    'Hierarchy depth',
    [
      'Limits the nesting level. Use 0 to display all values. For the hierarchy like',
      '"a" -> "b" -> "c" the selection of 2 will result in "b" being the deepest option.',
    ],
  ],
  'required_depth' => [
    'Required depth',
    [
      'Requires item selection at the given nesting level. Use 0 to not impose the',
      'requirement. For the hierarchy like "a" -> "b" -> "c" the selection of 2 will',
      'obey a user to select at least "a" and "b".',
    ],
  ],
];

/**
 * Defines a class for getting options for a cshs form element from vocabulary.
 */
trait CshsOptionsFromHelper {

  use TaxonomyStorages;

  /**
   * Defines the default settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultSettings(): array {
    return [
      'parent' => 0,
      'level_labels' => '',
      'force_deepest' => FALSE,
      'save_lineage' => FALSE,
      'hierarchy_depth' => 0,
      'required_depth' => 0,
      'none_label' => CshsElement::NONE_LABEL,
    ];
  }

  /**
   * Returns the array of settings, including defaults for missing settings.
   *
   * @return array
   *   The array of settings.
   */
  abstract public function getSettings(): array;

  /**
   * Returns the value of a setting, or its default value if absent.
   *
   * @param string $key
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  abstract public function getSetting($key);

  /**
   * Returns the taxonomy vocabulary to work with.
   *
   * @return \Drupal\taxonomy\VocabularyInterface|null
   *   The taxonomy vocabulary.
   */
  abstract public function getVocabulary(): ?VocabularyInterface;

  /**
   * Returns a short summary for the settings.
   *
   * @return array
   *   A short summary of the settings.
   */
  public function settingsSummary(): array {
    $settings = $this->getSettings();
    $summary = [];
    $deepest = $this->t('Deepest');
    $none = $this->t('None');
    $yes = $this->t('Yes');
    $no = $this->t('No');

    $summary[] = $this->t('Parent: @parent', [
      '@parent' => empty($settings['parent']) ? $none : $this->getTranslationFromContext($this->getTermStorage()->load($settings['parent']))->label(),
    ]);

    foreach (HIERARCHY_OPTIONS as $option_name => [$title]) {
      /** @noinspection NestedTernaryOperatorInspection */
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $summary[] = $this->t("$title: @$option_name", [
        "@$option_name" => empty($settings['force_deepest'])
          ? (empty($settings[$option_name]) ? $none : $settings[$option_name])
          : $deepest,
      ]);
    }

    $summary[] = $this->t('Force deepest: @force_deepest', [
      '@force_deepest' => empty($settings['force_deepest']) ? $no : $yes,
    ]);

    $summary[] = $this->t('Save lineage: @save_lineage', [
      '@save_lineage' => empty($settings['save_lineage']) ? $no : $yes,
    ]);

    $summary[] = $this->t('Level labels: @level_labels', [
      '@level_labels' => empty($settings['level_labels']) ? $none : $this->getTranslatedLevelLabels(),
    ]);

    $summary[] = $this->t('The "no selection" label: @none_label', [
      '@none_label' => $this->getTranslatedNoneLabel(),
    ]);

    return $summary;
  }

  /**
   * Returns a form to configure settings.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form definition for the settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $vocabulary = $this->getVocabulary();
    \assert($vocabulary !== NULL);
    $options = [];

    // Build options for parent select field.
    foreach ($this->getOptions($vocabulary->id()) as $key => $value) {
      $options[$key] = $value['name'];
    }

    $element['parent'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent'),
      '#options' => $options,
      '#description' => $this->t('Select a parent term to use only a subtree of a vocabulary for this field.'),
      '#default_value' => $this->getSetting('parent'),
    ];

    foreach (HIERARCHY_OPTIONS as $option_name => [$title, $description]) {
      $description[] = '<i>Ignored when the deepest selection is enforced.</i>';
      $element[$option_name] = [
        '#min' => 0,
        '#type' => 'number',
        '#title' => $title,
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        '#description' => $this->t(\implode('<br />', $description)),
        '#default_value' => $this->getSetting($option_name),
        '#states' => [
          'disabled' => [
            ':input[name*="force_deepest"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $element['force_deepest'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force selection of deepest level'),
      '#description' => $this->t('If checked the user will be forced to select terms from the deepest level.'),
      '#default_value' => $this->getSetting('force_deepest'),
    ];

    // This method can be called during Views filter configuration where
    // the "$this->fieldDefinition" is not available. Moreover, we don't
    // need to provide the "save_lineage" there.
    if ($this instanceof WidgetBase) {
      $errors = [];
      $field_storage = $this->fieldDefinition->getFieldStorageDefinition();
      \assert($field_storage instanceof FieldStorageConfigInterface);
      $is_unlimited = $field_storage->getCardinality() === FieldStorageConfigInterface::CARDINALITY_UNLIMITED;
      $description = [
        'Save all parents of the selected term. Please note that you will not have a',
        'familiar field when multiple items can be added via the "Add more" button.',
        'In fact, the field will look like a "single" and the selected terms will',
        'be stored each as a separate field value.',
      ];

      if ($this->getStorage($field_storage->getTargetEntityTypeId())->countFieldData($field_storage, TRUE)) {
        $errors[] = 'There is data for this field in the database. This setting can no longer be changed.';
      }

      if (!$is_unlimited) {
        $errors[] = 'The option can be enabled only for fields with unlimited cardinality.';
      }

      if (!empty($errors)) {
        \array_unshift($description, \sprintf('<b class="color-error">%s</b>', \implode('<br />', $errors)));
      }

      $element['save_lineage'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Save lineage'),
        '#disabled' => !empty($errors),
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        '#description' => $this->t(\implode('<br />', $description)),
        '#default_value' => $is_unlimited && $this->getSetting('save_lineage'),
      ];
    }

    $element['level_labels'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Labels per hierarchy-level'),
      '#description' => $this->t('Enter labels for each hierarchy-level separated by comma.'),
      '#default_value' => $this->getTranslatedLevelLabels(),
    ];

    $element['none_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The "no selection" label'),
      '#description' => $this->t('The label for an empty option.'),
      '#default_value' => $this->getTranslatedNoneLabel(),
    ];

    $element['#element_validate'][] = [$this, 'validateSettingsForm'];

    $form_state->set('vocabulary', $vocabulary);

    return $element;
  }

  /**
   * Validates the settings form.
   *
   * @param array $element
   *   The element's form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateSettingsForm(array &$element, FormStateInterface $form_state): void {
    $settings = $form_state->getValue($element['#parents']);
    $options = $element['parent']['#options'];

    foreach ($options as $id => $label) {
      // This always removes at least the first item, which is what we
      // want. If a user selects nothing we remove the `- Please select -`
      // and count only the number of nesting levels. In another case,
      // we remove everything before and including the selected item and
      // count the rest.
      unset($options[$id]);
      // Leave the rest of the list after the selected option.
      if ((string) $id === $settings['parent']) {
        break;
      }
    }

    if ($settings['hierarchy_depth'] > ($max_hierarchy_depth = \count($options))) {
      $form_state->setError($element['hierarchy_depth'], $this->t('The hierarchy depth cannot be @actual because the selection list has @levels levels.', [
        '@actual' => $settings['hierarchy_depth'],
        '@levels' => $max_hierarchy_depth,
      ]));
    }
    elseif ($settings['required_depth'] > $settings['hierarchy_depth']) {
      $form_state->setError($element['required_depth'], $this->t('The required depth cannot be greater than the hierarchy depth.'));
    }
  }

  /**
   * Returns the form for a single widget.
   *
   * @return array
   *   The form elements for a single widget.
   */
  public function formElement(): array {
    $vocabulary = $this->getVocabulary();
    \assert($vocabulary !== NULL);
    $settings = $this->getSettings();

    if (!empty($settings['force_deepest']) || ($max_depth = $settings['hierarchy_depth']) < 1) {
      $max_depth = NULL;
    }

    return [
      '#type' => CshsElement::ID,
      '#labels' => $this->getTranslatedLevelLabels(FALSE),
      '#parent' => $settings['parent'],
      '#options' => $this->getOptions($vocabulary->id(), $settings['parent'], CshsElement::NONE_VALUE, $max_depth),
      '#multiple' => $settings['save_lineage'],
      '#vocabulary' => $vocabulary,
      '#none_value' => CshsElement::NONE_VALUE,
      '#none_label' => $this->getTranslatedNoneLabel(),
      '#default_value' => CshsElement::NONE_VALUE,
      '#force_deepest' => $settings['force_deepest'],
      '#required_depth' => $settings['required_depth'],
    ];
  }

  /**
   * Collects the options.
   *
   * @param string $vocabulary_id
   *   Name of taxonomy vocabulary.
   * @param int $parent
   *   ID of a parent term.
   * @param int|string $none_value
   *   Value for the first option.
   * @param int|null $max_depth
   *   The number of levels of the tree to return.
   *
   * @return array[]
   *   Widget options.
   */
  private function getOptions(string $vocabulary_id, int $parent = 0, $none_value = 0, int $max_depth = NULL): array {
    \assert(\is_int($none_value) || \is_string($none_value));
    static $cache = [];

    $cache_id = "$vocabulary_id:$parent:$none_value:$max_depth";

    if (!isset($cache[$cache_id])) {
      $storage = $this->getTermStorage();
      $cache[$cache_id] = [
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        $none_value => CshsElement::option($this->t(CshsElement::NONE_LABEL)),
      ];

      if ($this->needsTranslatedContent()) {
        $get_name = function (object $term) use ($storage): string {
          return $this->getTranslationFromContext($storage->load($term->tid))->label();
        };
      }
      else {
        // Avoid loading the entity if we don't need its specific translation.
        $get_name = static function (object $term): string {
          return $term->name;
        };
      }

      foreach ($storage->loadTree($vocabulary_id, $parent, $max_depth, FALSE) as $term) {
        \assert($term instanceof \stdClass);
        \assert(\is_array($term->parents));
        \assert(\is_numeric($term->status));
        \assert(\is_numeric($term->depth));
        \assert(\is_numeric($term->tid));
        \assert(\is_string($term->name));

        // Allow only published terms.
        if ((bool) $term->status) {
          $parents = \array_values($term->parents);
          $cache[$cache_id][$term->tid] = CshsElement::option($get_name($term), (int) \reset($parents));
        }
      }
    }

    return $cache[$cache_id];
  }

  /**
   * Returns translated labels with escaped markup.
   *
   * @param bool $return_as_string
   *   Whether returning value have to be a string.
   *
   * @return string|string[]
   *   Translated labels, splitted by comma, or an array of them.
   */
  private function getTranslatedLevelLabels(bool $return_as_string = TRUE) {
    $labels = $this->getSetting('level_labels');

    if (empty($labels)) {
      return $return_as_string ? '' : [];
    }

    $labels = \array_map([$this, 'getTranslatedValue'], Tags::explode($labels));

    return $return_as_string ? \implode(', ', $labels) : $labels;
  }

  /**
   * Returns the translated label for the "no selection" option.
   *
   * @return string
   *   The label.
   */
  private function getTranslatedNoneLabel(): string {
    return $this->getTranslatedValue($this->getSetting('none_label') ?: CshsElement::NONE_LABEL);
  }

  /**
   * Returns the translated label.
   *
   * @param string $value
   *   The value for translate.
   *
   * @return string
   *   The translated value.
   */
  private function getTranslatedValue(string $value): string {
    $value = \trim($value);
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    return $value === '' ? $value : (string) $this->t(Html::escape($value));
  }

}
