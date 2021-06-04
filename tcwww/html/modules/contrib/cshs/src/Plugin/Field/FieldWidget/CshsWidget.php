<?php

namespace Drupal\cshs\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\cshs\IsApplicable;
use Drupal\cshs\CshsOptionsFromHelper;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Provides "cshs" field widget.
 *
 * @FieldWidget(
 *   id = "cshs",
 *   label = @Translation("Client-side hierarchical select"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class CshsWidget extends WidgetBase {

  use IsApplicable {
    isApplicable as helperIsApplicable;
  }
  use CshsOptionsFromHelper {
    defaultSettings as helperDefaultSettings;
    settingsSummary as helperSettingsSummary;
    settingsForm as helperSettingsForm;
    formElement as helperFormElement;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    if (static::helperIsApplicable($field_definition)) {
      /* @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection */
      if (\count($field_definition->getSettings()['handler_settings']['target_bundles'] ?? []) === 1) {
        return TRUE;
      }

      \Drupal::messenger()->addWarning(\t('The client-side hierarchical select widget cannot be used for the %label field. Either change the widget type or configure the %field to use the default entity reference selection handler with only a single vocabulary.', [
        '%label' => $field_definition->getLabel(),
        '%field' => \str_replace('.', ' -> ', $field_definition->id()),
      ]));
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    /* @noinspection AdditionOperationOnArraysInspection */
    return static::helperDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    // Overridden to provide a return type.
    return parent::getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return $this->helperSettingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    return $this->helperSettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['target_id'] = \array_merge($element, $this->helperFormElement(), [
      '#label' => $this->fieldDefinition->getLabel(),
    ]);

    if ($items->isEmpty()) {
      return $element;
    }

    if ($this->handlesMultipleValues()) {
      $element['target_id']['#default_value'] = \array_map(static function (array $item): int {
        return $item['target_id'];
      }, $items->getValue());
    }
    else {
      $element['target_id']['#default_value'] = $items->get($delta)->target_id ?? NULL;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    // This is the case when `$this->handlesMultipleValues()` returns `TRUE`.
    if (!empty($values['target_id']) && \is_array($values['target_id'])) {
      $list = [];

      foreach ($values['target_id'] as $id) {
        $list[] = [
          'target_id' => $id,
        ];
      }

      return $list;
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabulary(): VocabularyInterface {
    return $this
      ->getVocabularyStorage()
      ->load(\reset($this->fieldDefinition->getSettings()['handler_settings']['target_bundles']));
  }

  /**
   * {@inheritdoc}
   */
  protected function handlesMultipleValues(): bool {
    return (bool) $this->getSetting('save_lineage');
  }

}
