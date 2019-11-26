<?php

namespace Drupal\lightning_media;

@trigger_error(__NAMESPACE__ . '\SourceFieldInterface is deprecated in lightning:8.x-2.2 and will be removed in lightning_media:8.x-4.0. Use \Drupal\media\MediaSourceInterface::getSourceFieldDefinition() instead. See https://www.drupal.org/node/2923515', E_USER_DEPRECATED);

/**
 * Interface for media sources which expose their source field definition.
 */
interface SourceFieldInterface {}
