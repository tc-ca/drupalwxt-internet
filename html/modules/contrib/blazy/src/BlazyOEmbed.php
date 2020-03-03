<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Image\ImageFactory;
use Drupal\file\Entity\File;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements BlazyOEmbedInterface.
 */
class BlazyOEmbed implements BlazyOEmbedInterface {

  /**
   * Core Media oEmbed url resolver.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * Core Media oEmbed resource fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * Core Media oEmbed iframe url helper.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iframeUrlHelper;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The Media oEmbed Resource.
   *
   * @var \Drupal\media\OEmbed\Resource[]
   */
  protected $resource;

  /**
   * The request service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Constructs a BlazyManager object.
   */
  public function __construct(RequestStack $request, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, IFrameUrlHelper $iframe_url_helper, ImageFactory $image_factory, BlazyManagerInterface $blazy_manager) {
    $this->request = $request;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->iframeUrlHelper = $iframe_url_helper;
    $this->imageFactory = $image_factory;
    $this->blazyManager = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('image.factory'),
      $container->get('blazy.manager')
    );
  }

  /**
   * Returns the Media oEmbed resource fecther.
   */
  public function getResourceFetcher() {
    return $this->resourceFetcher;
  }

  /**
   * Returns the Media oEmbed url resolver fecthers.
   */
  public function getUrlResolver() {
    return $this->urlResolver;
  }

  /**
   * Returns the Media oEmbed url resolver fecthers.
   */
  public function getIframeUrlHelper() {
    return $this->iframeUrlHelper;
  }

  /**
   * Returns the image factory.
   */
  public function imageFactory() {
    return $this->imageFactory;
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getResource($input_url) {
    if (!isset($this->resource[hash('md2', $input_url)])) {
      $resource_url = $this->urlResolver->getResourceUrl($input_url, 0, 0);
      $this->resource[hash('md2', $input_url)] = $this->resourceFetcher->fetchResource($resource_url);
    }

    return $this->resource[hash('md2', $input_url)];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$settings = []) {
    $resource = NULL;
    try {
      $this->blazyManager->getCommonSettings($settings);
      $settings['input_url'] = UrlHelper::stripDangerousProtocols($settings['input_url']);
      $resource = $this->getResource($settings['input_url']);

      // @todo support other types (link, photo), if reasonable for Blazy.
      if ($resource && ($resource->getType() === Resource::TYPE_VIDEO || $resource->getType() === Resource::TYPE_RICH)) {
        $width = empty($settings['width']) ? $resource->getWidth() : $settings['width'];
        $height = empty($settings['height']) ? $resource->getHeight() : $settings['height'];
        $url = Url::fromRoute('media.oembed_iframe', [], [
          'query' => [
            'url' => $settings['input_url'],
            'max_width' => $width,
            'max_height' => $height,
            'hash' => $this->iframeUrlHelper->getHash($settings['input_url'], $width, $height),
            'blazy' => 1,
            'autoplay' => empty($settings['media_switch']) ? 0 : 1,
          ],
        ]);

        if ($domain = $this->blazyManager->configLoad('iframe_domain', 'media.settings')) {
          $url->setOption('base_url', $domain);
        }

        // The top level iframe url relative to the site, or iframe_domain.
        $settings['embed_url'] = $url->toString();

        // Extracts the actual video url from html, and provides autoplay url.
        $settings = array_merge($settings, $this->getAutoPlayUrl($resource));

        // Only applies when Image style is empty, no file API, no $item,
        // with unmanaged VEF/ WYSIWG/ filter image without image_style.
        // Prevents 404 warning when video thumbnail missing for a reason.
        if (empty($settings['image_style']) && !empty($settings['uri'])) {
          if ($data = @getimagesize($settings['uri'])) {
            list($settings['width'], $settings['height']) = $data;
          }
        }
      }
    }
    catch (\Exception $e) {
      // Do nothing, likely local work without internet, or the site is down.
    }

    return $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutoPlayUrl(Resource $resource, \DOMDocument $dom = NULL) {
    $data = [];
    if ($dom || $resource->getHtml()) {
      $dom = $dom ?: Html::load($resource->getHtml());
      $iframe = $dom->getElementsByTagName('iframe');
      $url = $iframe->length > 0 ? $iframe->item(0)->getAttribute('src') : NULL;

      if (!empty($url)) {
        $data['oembed_url'] = $url;
        $data['scheme']     = mb_strtolower($resource->getProvider()->getName());
        $data['type']       = $resource->getType();

        // Adds autoplay for media URL on lightboxes, saving another click.
        if (strpos($url, 'autoplay') === FALSE || strpos($url, 'autoplay=0') !== FALSE) {
          $data['autoplay_url'] = strpos($url, '?') === FALSE ? $url . '?autoplay=1' : $url . '&autoplay=1';
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getMediaItem(array &$data, $media) {
    // Only proceed if we do have Media.
    if ($media->getEntityTypeId() != 'media') {
      return;
    }

    BlazyMedia::mediaItem($data, $media);
    $settings = $data['settings'];

    // @todo support local video/ audio file, and other media sources.
    // @todo check for Resource::TYPE_PHOTO, Resource::TYPE_RICH, etc.
    switch ($settings['media_source']) {
      case 'oembed':
      case 'oembed:video':
        // Input url != embed url. For Youtube, /watch != /embed.
        $input_url = $media->getSource()->getSourceFieldValue($media);
        $input_url = trim(strip_tags($input_url));
        if ($input_url) {
          $settings['input_url'] = $input_url;

          $this->build($settings);
        }
        break;

      case 'image':
        $settings['type'] = 'image';
        break;

      // No special handling for anything else for now, pass through.
      default:
        break;
    }

    // Do not proceed if it has type, already managed by theme_blazy().
    // Supports other Media entities: Facebook, Instagram, local video, etc.
    if (empty($settings['type']) && ($build = BlazyMedia::build($media, $settings))) {
      $data['content'][] = $build;
    }

    // Collect what's needed for clarity.
    $data['settings'] = $settings;
  }

  /**
   * {@inheritdoc}
   *
   * @todo compare and merge with BlazyMedia::imageItem().
   */
  public function getImageItem($file) {
    $data = [];
    $entity = $file;

    /** @var Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $file */
    if (isset($file->entity) && !isset($file->alt)) {
      $entity = $file->entity;
    }

    if ($entity instanceof File) {
      if ($image = $this->imageFactory->get($entity->getFileUri())) {
        BlazyMedia::fakeImageItem($data, $entity, $image);
      }
    }

    return $data;
  }

  /**
   * Overrides variables for media-oembed-iframe.html.twig templates.
   *
   * @todo recheck this in case core provides a more flexible way post 8.8+.
   */
  public function preprocessMediaOembedIframe(array &$variables) {
    // Without internet, this may be empty, bail out.
    if (empty($variables['media'])) {
      return;
    }

    // Only needed to autoplay video, and make responsive iframe.
    try {
      // Blazy formatters with oEmbed provide contextual params to the query.
      $request = $this->request->getCurrentRequest();
      $is_blazy = $request->query->getInt('blazy', NULL);
      $is_autoplay = $request->query->getInt('autoplay', NULL);
      $url = $request->query->get('url');

      // Only replace url if it is required by Blazy.
      if ($url && $is_blazy == 1) {
        // Load iframe string as a DOMDocument as alternative to regex.
        $dom = Html::load($variables['media']);
        $iframe = $dom->getElementsByTagName('iframe');
        $resource = $this->getResource($url);

        // Fetches autoplay_url.
        $settings = $this->getAutoPlayUrl($resource, $dom);

        // Replace old oEmbed url with autoplay support, and save the DOM.
        if ($iframe->length > 0) {
          // Only replace if autoplay == 1 for Image to iframe, or lightboxes.
          if ($is_autoplay == 1 && !empty($settings['autoplay_url'])) {
            $iframe->item(0)->setAttribute('src', $settings['autoplay_url']);
          }

          // Make responsive iframe with/ without autoplay.
          // The following ensures iframe does not shrink due to its attributes.
          $iframe->item(0)->setAttribute('height', '100%');
          $iframe->item(0)->setAttribute('width', '100%');
          $dom->getElementsByTagName('body')->item(0)->setAttribute('class', 'is-b-oembed');
          $variables['media'] = $dom->saveHTML();
        }
      }
    }
    catch (\Exception $ignore) {
      // Do nothing, likely local work without internet, or the site is down.
      // No need to be chatty on this.
    }
  }

}
