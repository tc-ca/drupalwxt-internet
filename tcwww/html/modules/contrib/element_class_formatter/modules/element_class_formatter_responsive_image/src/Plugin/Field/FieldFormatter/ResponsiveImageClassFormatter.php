<?php

namespace Drupal\element_class_formatter_responsive_image\Plugin\Field\FieldFormatter;

use Drupal\element_class_formatter\Plugin\Field\FieldFormatter\ElementEntityClassTrait;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;

/**
 * Plugin implementation of the 'responsive image with class' formatter.
 *
 * @FieldFormatter(
 *   id = "responsive_image_class",
 *   label = @Translation("Responsive image (with class)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ResponsiveImageClassFormatter extends ResponsiveImageFormatter {

  use ElementEntityClassTrait;

}
