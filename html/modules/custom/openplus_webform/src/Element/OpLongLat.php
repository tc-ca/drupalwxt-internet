<?php

namespace Drupal\openplus_webform\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for a contact element.
 *
 * @FormElement("op_webform_longlat")
 */
class OpLongLat extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'op_webform_composite_longlat'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [ 
        'class' => ['row'],
      ],
    ];
    $elements['wrapper']['long'] = [
      '#type' => 'container',
      '#attributes' => [ 
        'class' => ['col-md-6'],
      ],
    ];
    $elements['wrapper']['long']['long_title'] = [
      '#title' => t('N Longitude'),
      '#type' => 'item',
    ];
    $elements['wrapper']['long']['long_deg'] = [
      '#type' => 'textfield',
      '#title' => t('Deg:'),
      '#size' => 14,
      '#maxlength' => 14,
    ];
    $elements['wrapper']['long']['long_min'] = [
      '#type' => 'textfield',
      '#title' => t('Min:'),
      '#size' => 14,
      '#maxlength' => 14,
    ];
    $elements['wrapper']['long']['long_sec'] = [
      '#type' => 'textfield',
      '#title' => t('Sec:'),
      '#size' => 14,
      '#maxlength' => 14,
    ];
    $elements['wrapper']['lat'] = [
      '#type' => 'container',
      '#attributes' => [ 
        'class' => ['col-md-6'],
      ],
    ];
    $elements['wrapper']['lat']['lat_title'] = [
      '#title' => t('W Latitude'),
      '#type' => 'item',
    ];
    $elements['wrapper']['lat']['lat_deg'] = [
      '#type' => 'textfield',
      '#title' => t('Deg:'),
      '#size' => 14,
      '#maxlength' => 14,
    ];
    $elements['wrapper']['lat']['lat_min'] = [
      '#type' => 'textfield',
      '#title' => t('Min:'),
      '#size' => 14,
      '#maxlength' => 14,
    ];
    $elements['wrapper']['lat']['lat_sec'] = [
      '#type' => 'textfield',
      '#title' => t('Sec:'),
      '#size' => 14,
      '#maxlength' => 14,
    ];

    return $elements;
  }

}
