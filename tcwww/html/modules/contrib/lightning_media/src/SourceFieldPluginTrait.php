<?php

namespace Drupal\lightning_media;

@trigger_error(__NAMESPACE__ . '\SourceFieldPluginTrait is deprecated in lightning:8.x-2.2 and will be removed in lightning_media:8.x-4.0. Use \Drupal\media\MediaSourceInterface::getSourceFieldDefinition() instead. See https://www.drupal.org/node/2923515', E_USER_DEPRECATED);

use Drupal\media\MediaTypeInterface;

/**
 * Trait implementation of SourceFieldInterface.
 */
trait SourceFieldPluginTrait {

  /**
   * Implements InputMatchInterface::getSourceFieldDefinition().
   */
  public function getSourceFieldDefinition(MediaTypeInterface $media_type) {
    return $this->getSourceFieldDefinition($media_type);
  }

}
