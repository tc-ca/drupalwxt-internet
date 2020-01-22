<?php

namespace Drupal\openplus_webform\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for a contact element.
 *
 * @FormElement("op_webform_contact")
 */
class OpWebformContact extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'op_webform_composite_contact'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['first_name'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
    ];
    $elements['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
    ];
    $elements['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
    ];
    $elements['phone'] = [
      '#type' => 'tel',
      '#title' => t('Phone'),
    ];
    return $elements;
  }

}
