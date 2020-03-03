<?php

namespace Drupal\openplus_webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
/**
 * Provides a 'contact' element.
 *
 * @WebformElement(
 *   id = "op_webform_contact",
 *   label = @Translation("Contact (short)"),
 *   description = @Translation("Provides a form element to collect contact information (first name, last name, phone, email)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class OpWebformContact extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $lines = $this->formatTextItemValue($element, $webform_submission, $options);
    if (!empty($lines['email'])) {
      $lines['email'] = [
        '#type' => 'link',
        '#title' => $lines['email'],
        '#url' => \Drupal::pathValidator()->getUrlIfValid('mailto:' . $lines['email']),
      ];
    }
    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $lines = [];
    if (!empty($value['first_name'])) {
      $lines['first_name'] = $value['first_name'];
    }
    if (!empty($value['last_name'])) {
      $lines['last_name'] = $value['last_name'];
    }
    if (!empty($value['email'])) {
      $lines['email'] = $value['email'];
    }
    if (!empty($value['phone'])) {
      $lines['phone'] = $value['phone'];
    }
    return $lines;
  }

}
