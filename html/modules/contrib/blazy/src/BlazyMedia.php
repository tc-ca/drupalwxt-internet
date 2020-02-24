<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;

/**
 * Impelements BlazyMediaInterface.
 */
class BlazyMedia implements BlazyMediaInterface {

  /**
   * {@inheritdoc}
   */
  public static function build($media, array $settings = []) {
    // Prevents fatal error with disconnected internet when having ME Facebook,
    // ME SlideShare, resorted to static thumbnails to avoid broken displays.
    if (!empty($settings['input_url'])) {
      try {
        \Drupal::httpClient()->get($settings['input_url'], ['timeout' => 7]);
      }
      catch (\Exception $e) {
        return FALSE;
      }
    }

    $settings['type'] = 'rich';
    $options = $settings['media_source'] == 'video_file' ? ['type' => 'file_video'] : $settings['view_mode'];
    $build = $media->get($settings['source_field'])->view($options);
    $build['#settings'] = $settings;

    return isset($build[0]) ? self::wrap($build) : $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function wrap(array $field = []) {
    $item       = $field[0];
    $settings   = $field['#settings'];
    $iframe     = isset($item['#tag']) && $item['#tag'] == 'iframe';
    $attributes = [];

    if (isset($item['#attributes'])) {
      $attributes = &$item['#attributes'];
    }

    // Update iframe/video dimensions based on configurable image style, if any.
    foreach (['width', 'height'] as $key) {
      if (!empty($settings[$key])) {
        $attributes[$key] = $settings[$key];
      }
    }

    // Converts iframes into lazyloaded ones.
    // Iframes: Googledocs, SlideShare. Hardcoded: Soundcloud, Spotify.
    if ($iframe && !empty($attributes['src'])) {
      $settings['embed_url'] = $attributes['src'];
      $attributes = NestedArray::mergeDeep($attributes, Blazy::iframeAttributes($settings));
    }
    // Media with local files: video.
    elseif (isset($item['#files'], $item['#files'][0]['file'])) {
      self::videoItem($item, $settings);
    }

    // Clone relevant keys since field wrapper is no longer in use.
    foreach (['attached', 'cache', 'third_party_settings'] as $key) {
      if (!empty($field["#$key"])) {
        $item["#$key"] = isset($item["#$key"]) ? NestedArray::mergeDeep($field["#$key"], $item["#$key"]) : $field["#$key"];
      }
    }
    // Keep original formatter configurations intact here for custom works.
    $item['#settings'] = new BlazySettings(array_filter($settings));
    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public static function mediaItem(array &$data, $media) {
    $item     = NULL;
    $settings = &$data['settings'];

    $settings['bundle']       = $media->bundle();
    $settings['source_field'] = $media->getSource()->getConfiguration()['source_field'];
    $settings['media_url']    = $media->toUrl()->toString();
    $settings['media_id']     = $media->id();
    $settings['media_source'] = $media->getSource()->getPluginId();
    $settings['view_mode']    = empty($settings['view_mode']) ? 'default' : $settings['view_mode'];

    // Prioritize custom high-res or poster image such as (remote|file) video.
    if (!empty($settings['image'])) {
      $item = $media->hasField($settings['image']) ? $media->get($settings['image'])->first() : NULL;
      $settings['_hires'] = !empty($item);
    }

    // If Media has a defined thumbnail, add it to data item, not all has this.
    if (!$item && $media->hasField('thumbnail')) {
      /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      // Title is NULL from thumbnail, likely core bug, so use source.
      $item = $media->get($settings['media_source'] == 'image' ? $settings['source_field'] : 'thumbnail')->first();
    }

    // Checks if Image item is available.
    if ($item) {
      $settings['file_tags'] = ['file:' . $item->target_id];
      $settings['uri'] = Blazy::uri($item);

      // Pass through image item including poster image overrides.
      $data['item'] = $item;
    }

    // If the expected fails, at least check for metadata, likely unknown media.
    // @todo remove if we know Media better.
    if (empty($settings['uri'])) {
      try {
        // Without internet, this screwed up the site.
        $settings['uri'] = $media->getSource()->getMetadata($media, 'thumbnail_uri');
      }
      catch (\Exception $ignore) {
        // Do nothing, no need to be chatty on this.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function videoItem(array &$item, array $settings) {
    // Do this as $item['#settings'] is not available as file_video variables.
    foreach ($item['#files'] as &$file) {
      $file['blazy'] = new BlazySettings($settings);
    }
    $item['#attributes']->setAttribute('data-b-lazy', TRUE);
    if (!empty($settings['is_preview'])) {
      $item['#attributes']->setAttribute('data-b-preview', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function fakeImageItem(array &$data, $entity, $image) {
    /** @var \Drupal\file\Entity\File $entity */
    list($type,) = explode('/', $entity->getMimeType(), 2);
    $uri = $entity->getFileUri();

    if ($type == 'image' && $image->isValid()) {
      $item            = new \stdClass();
      $item->target_id = $entity->id();
      $item->width     = $image->getWidth();
      $item->height    = $image->getHeight();
      $item->uri       = $uri;
      $settings        = (array) $item;
      $item->alt       = $entity->getFilename();
      $item->title     = $entity->getFilename();
      $item->entity    = $entity;

      // Build item and settings.
      $settings['type'] = 'image';
      $data['item']     = $item;
      $data['settings'] = $settings;
      unset($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function imageItem(array &$data, $entity) {
    $settings = &$data['settings'];
    $stage = $settings['image'];

    // The actual video thumbnail has already been downloaded earlier.
    // This fetches the highres image if provided and available.
    // With a mix of image and video, image is not always there.
    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $file */
    if (isset($entity->{$stage}) && $file = $entity->get($stage)) {
      $value = $file->getValue();

      // Do not proceed if it is a Media entity video.
      if (isset($value[0]) && !empty($value[0]['target_id'])) {
        // If image, even if multi-value, we can only have one stage per slide.
        if (method_exists($file, 'referencedEntities') && isset($file->referencedEntities()[0])) {
          /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
          $data['item'] = $file->get(0);

          // Collects cache tags to be added for each item in the field.
          $settings['file_tags'] = $file->referencedEntities()[0]->getCacheTags();
          $settings['uri'] = Blazy::uri($data['item']);
        }
      }
    }
  }

}
