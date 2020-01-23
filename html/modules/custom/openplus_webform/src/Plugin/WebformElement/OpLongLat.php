<?php

namespace Drupal\openplus_webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
/**
 * Provides a 'longitude / latitude' element.
 *
 * @WebformElement(
 *   id = "op_webform_longlat",
 *   label = @Translation("Longitude / Latitude"),
 *   description = @Translation("Provides a form element to collect Longitude / Latitude (Deg, Min, Sec)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class OpLongLat extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $lines = $this->formatTextItemValue($element, $webform_submission, $options);

    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $lines = [];
    if (!empty($value['long_deg'])) {
      $lines['long_deg'] = $value['long_deg'];
    }
    if (!empty($value['long_min'])) {
      $lines['long_min'] = $value['long_min'];
    }
    if (!empty($value['long_sec'])) {
      $lines['long_sec'] = $value['long_sec'];
    }
    if (!empty($value['lat_deg'])) {
      $lines['lat_deg'] = $value['lat_deg'];
    }
    if (!empty($value['lat_min'])) {
      $lines['lat_min'] = $value['lat_min'];
    }
    if (!empty($value['lat_sec'])) {
      $lines['lat_sec'] = $value['lat_sec'];
    }

    return $lines;
  }

}
