<?php

namespace Drupal\blazy\Form;

/**
 * A Trait common for breakpoint methods.
 *
 * @todo deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-2.0. Don't
 *   use it instead.
 * @see https://www.drupal.org/node/3105243
 */
trait BlazyAdminBreakpointTrait {

  /**
   * Defines re-usable breakpoints form.
   *
   * @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-srcset
   * @see http://ericportis.com/posts/2014/srcset-sizes/
   * @see http://www.sitepoint.com/how-to-build-responsive-images-with-srcset/
   */
  public function breakpointsForm(array &$form, $definition = []) {
    $settings = isset($definition['settings']) ? $definition['settings'] : [];
    $title = $this->t('Breakpoints (<a href=":url">deprecated</a>). <small>If provided, Blazy lazyload applies. Ignored if core Responsive image is provided.<br /> If only two is needed, simply leave the rest empty. At any rate, the last should target the largest monitor. <br>Choose an <b>Aspect ratio</b> and use an image effect with <b>CROP</b> in its name for all styles for best performance. <br>It uses <strong>max-width</strong>, not <strong>min-width</strong>.</small>', [':url' => 'https://drupal.org/node/3105243']);

    $form['sizes'] = [
      '#type'               => 'textfield',
      '#title'              => $this->t('Sizes'),
      '#description'        => $this->t('E.g.: (min-width: 1290px) 1290px, 100vw. Use sizes to implement different size image (different height, width) on different screen sizes along with the <strong>w (width)</strong> descriptor below. Ignored by Responsive image.'),
      '#weight'             => 114,
      '#wrapper_attributes' => ['class' => ['form-item--sizes']],
      '#prefix'             => '<h2 class="form__title form__title--breakpoints">' . $title . '</h2>',
    ];

    $form['breakpoints'] = [
      '#type'       => 'table',
      '#tree'       => TRUE,
      '#header'     => [
        $this->t('Breakpoint'),
        $this->t('Image style'),
        $this->t('Max-width/Descriptor'),
      ],
      '#attributes' => ['class' => ['form-wrapper--table', 'form-wrapper--table-breakpoints']],
      '#weight'     => 115,
      '#enforced'   => TRUE,
    ];

    // Unlike D7, D8 form states seem to not recognize individual field form.
    $vanilla = ':input[name$="[vanilla]"]';
    if (isset($definition['field_name'])) {
      $vanilla = ':input[name="fields[' . $definition['field_name'] . '][settings_edit_form][settings][vanilla]"]';
    }

    if (!empty($definition['_views'])) {
      $vanilla = ':input[name="options[settings][vanilla]"]';
    }

    $breakpoints = $this->breakpointElements($definition);
    foreach ($breakpoints as $breakpoint => $elements) {
      foreach ($elements as $key => $element) {
        $form['breakpoints'][$breakpoint][$key] = $element;

        if (!empty($definition['vanilla'])) {
          $form['breakpoints'][$breakpoint][$key]['#states']['enabled'][$vanilla] = ['checked' => FALSE];
        }

        $value = isset($settings['breakpoints'][$breakpoint][$key]) ? $settings['breakpoints'][$breakpoint][$key] : '';
        if ($key != 'breakpoint') {
          $form['breakpoints'][$breakpoint][$key]['#default_value'] = $value;
        }
      }
    }
  }

  /**
   * Defines re-usable breakpoints form.
   */
  public function breakpointElements($definition = []) {
    foreach ($definition['breakpoints'] as $breakpoint) {
      $form[$breakpoint]['breakpoint'] = [
        '#type'               => 'markup',
        '#markup'             => $breakpoint,
        '#weight'             => 1,
        '#wrapper_attributes' => ['class' => ['form-item--right']],
      ];

      $form[$breakpoint]['image_style'] = [
        '#type'               => 'select',
        '#title'              => $this->t('Image style'),
        '#title_display'      => 'invisible',
        '#options'            => $this->getEntityAsOptions('image_style'),
        '#empty_option'       => $this->t('- None -'),
        '#weight'             => 2,
        '#wrapper_attributes' => ['class' => ['form-item--left']],
      ];

      $form[$breakpoint]['width'] = [
        '#type'               => 'textfield',
        '#title'              => $this->t('Width'),
        '#title_display'      => 'invisible',
        '#description'        => $this->t('See <strong>XS</strong> for detailed info.'),
        '#maz_length'         => 32,
        '#size'               => 6,
        '#weight'             => 3,
        '#attributes'         => ['class' => ['form-text--width']],
        '#wrapper_attributes' => ['class' => ['form-item--width']],
      ];

      if ($breakpoint == 'xs') {
        $form[$breakpoint]['width']['#description'] = $this->t('E.g.: <strong>640</strong>, or <strong>2x</strong>, or for <strong>small devices</strong> may be combined into <strong>640w 2x</strong> where <strong>x (pixel density)</strong> descriptor is used to define the device-pixel ratio, and <strong>w (width)</strong> descriptor is the width of image source and works in tandem with <strong>sizes</strong> attributes. Use <strong>w (width)</strong> if any issue/ unsure. Default to <strong>w</strong> if no descriptor provided for backward compatibility.');
      }
    }

    return $form;
  }

}
