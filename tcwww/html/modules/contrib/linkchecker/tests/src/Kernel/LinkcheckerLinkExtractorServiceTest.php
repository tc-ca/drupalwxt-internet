<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Test link extractor service.
 *
 * @group linkchecker
 */
class LinkcheckerLinkExtractorServiceTest extends KernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'user',
    'system',
    'field',
    'filter',
    'text',
    'dynamic_entity_reference',
    'linkchecker',
    'path_alias',
  ];

  /**
   * HTTP protocol.
   *
   * @var string
   */
  protected $httpProtocol;

  /**
   * Base url.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * First folder in node path alias.
   *
   * @var string
   */
  protected $folder1;

  /**
   * Second folder in node path alias.
   *
   * @var string
   */
  protected $folder2;

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $linkcheckerSetting;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Extractor service.
   *
   * @var \Drupal\linkchecker\LinkExtractorService
   */
  protected $extractorService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installSchema('linkchecker', 'linkchecker_index');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig(['field', 'node', 'filter', 'linkchecker']);

    $this->linkcheckerSetting = $this->container->get('config.factory')
      ->getEditable('linkchecker.settings');

    $this->request = $this->container->get('request_stack')
      ->getCurrentRequest();

    if (isset($this->request)) {
      $this->httpProtocol = $this->request->getScheme() . '://';
      $this->baseUrl = $this->request->getSchemeAndHttpHost() . $this->request->getBasePath();
    }
    else {
      $this->httpProtocol = $this->linkcheckerSetting->get('default_url_scheme');
      $this->baseUrl = $this->httpProtocol . $this->linkcheckerSetting->get('base_path');
    }

    // Save folder names in variables for reuse.
    $this->folder1 = $this->randomMachineName(10);
    $this->folder2 = $this->randomMachineName(5);

    $this->extractorService = $this->container->get('linkchecker.extractor');
  }

  /**
   * Test external URLs.
   */
  public function testExternalUrls() {
    // Disable blacklist URLs.
    $this->linkcheckerSetting->set('check.disable_link_check_for_urls', '');

    // Enable external URLs only.
    $this->linkcheckerSetting->set('check_links_types', LinkCheckerLinkInterface::TYPE_EXTERNAL);
    $this->linkcheckerSetting->save(TRUE);

    $extracted = $this->extractorService->getLinks($this->getTestUrlList());

    $countExpected = count($this->getExternalUrls()) + count($this->getBlacklistedUrls());
    $countExtracted = count($extracted);
    $this->assertEquals($countExpected, $countExtracted, new FormattableMarkup('Expected to extract @count but get @actual links.', [
      '@count' => $countExpected,
      '@actual' => $countExtracted,
    ]));

    foreach ($this->getExternalUrls() + $this->getBlacklistedUrls() as $url) {
      $this->assertTrue(in_array($url, $extracted), new FormattableMarkup('URL @url was not extracted!', ['@url' => $url]));
    }
  }

  /**
   * Test relative URLs.
   */
  public function testRelativeUrls() {
    // Disable blacklist URLs.
    $this->linkcheckerSetting->set('check.disable_link_check_for_urls', '');

    // Enable internal links URLs only.
    $this->linkcheckerSetting->set('check_links_types', LinkCheckerLinkInterface::TYPE_INTERNAL);
    $this->linkcheckerSetting->save(TRUE);

    $extracted = $this->extractorService->getLinks($this->getTestUrlList(), $this->baseUrl . '/' . $this->folder1 . '/' . $this->folder2);

    $countExpected = count($this->getRelativeUrls());
    $countExtracted = count($extracted);
    $this->assertEquals($countExpected, $countExtracted, new FormattableMarkup('Expected to extract @count but get @actual links.', [
      '@count' => $countExpected,
      '@actual' => $countExtracted,
    ]));

    foreach ($this->getRelativeUrls() as $relativeUrl => $url) {
      $this->assertTrue(in_array($url, $extracted), new FormattableMarkup('URL @url was not extracted!', ['@url' => $url]));
    }
  }

  /**
   * Test blacklisted URLs.
   */
  public function testBlacklistedUrls() {
    // Enable blacklist URLs.
    $this->linkcheckerSetting->set('check.disable_link_check_for_urls', "example.com\nexample.net\nexample.org");

    // Enable internal links URLs only.
    $this->linkcheckerSetting->set('check_links_types', LinkCheckerLinkInterface::TYPE_ALL);
    $this->linkcheckerSetting->save(TRUE);

    $extracted = $this->extractorService->getLinks($this->getTestUrlList(), $this->baseUrl . '/' . $this->folder1 . '/' . $this->folder2);

    $countExpected = count($this->getTestUrlList()) - count($this->getBlacklistedUrls()) - count($this->getUnsupportedUrls());
    $countExtracted = count($extracted);
    $this->assertEquals($countExpected, $countExtracted, new FormattableMarkup('Expected to extract @count but get @actual links.', [
      '@count' => $countExpected,
      '@actual' => $countExtracted,
    ]));

    foreach ($this->getBlacklistedUrls() as $url) {
      $this->assertNotTrue(in_array($url, $extracted), new FormattableMarkup('Blacklisted URL @url was extracted!', ['@url' => $url]));
    }
  }

  /**
   * Test isLinkExists method.
   */
  public function testIsExists() {
    $type = NodeType::create(['name' => 'Links', 'type' => 'links']);
    $type->save();
    node_add_body_field($type);

    $node = $this->createNode([
      'type' => 'links',
      'body' => [
        [
          'value' => '<a href="https://existing.com"></a>'
          . '<a href="https://example.com/existing"></a>'
          . '<a href="/existing.local"></a>',
        ],
      ],
    ]);

    $fieldDefinition = $node->get('body')->getFieldDefinition();
    $config = $fieldDefinition->getConfig($node->bundle());
    $config->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $config->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $config->save();

    $urls = [
      'https://existing.com',
      'https://not-existing.com',
      'https://example.com/existing',
      $this->baseUrl . '/existing.local',
    ];

    /** @var \Drupal\linkchecker\LinkCheckerLinkInterface[] $links */
    $links = [];
    foreach ($urls as $url) {
      $tmpLink = LinkCheckerLink::create([
        'url' => $url,
        'entity_id' => [
          'target_id' => $node->id(),
          'target_type' => $node->getEntityTypeId(),
        ],
        'entity_field' => 'body',
        'entity_langcode' => 'en',
      ]);
      $tmpLink->save();
      $links[] = $tmpLink;
    }

    // Extract all link with empty blacklist.
    $checkMap = [
      TRUE,
      FALSE,
      TRUE,
      TRUE,
    ];
    // Disable blacklist URLs.
    $this->linkcheckerSetting->set('check.disable_link_check_for_urls', '');
    $this->linkcheckerSetting->set('check_links_types', LinkCheckerLinkInterface::TYPE_ALL);
    $this->linkcheckerSetting->save(TRUE);

    foreach ($links as $key => $link) {
      $this->assertEquals($checkMap[$key], $this->extractorService->isLinkExists($link));
    }

    // Extract all links with example.com blacklisting.
    $checkMap = [
      TRUE,
      FALSE,
      FALSE,
      TRUE,
    ];
    // Enable blacklist URLs.
    $this->linkcheckerSetting->set('check.disable_link_check_for_urls', "example.com");
    $this->linkcheckerSetting->save(TRUE);

    foreach ($links as $key => $link) {
      $this->assertEquals($checkMap[$key], $this->extractorService->isLinkExists($link));
    }

    // Extract external only.
    $checkMap = [
      TRUE,
      FALSE,
      FALSE,
      FALSE,
    ];
    // Enable blacklist URLs.
    $this->linkcheckerSetting->set('check.disable_link_check_for_urls', "example.com");
    $this->linkcheckerSetting->set('check_links_types', LinkCheckerLinkInterface::TYPE_EXTERNAL);
    $this->linkcheckerSetting->save(TRUE);

    foreach ($links as $key => $link) {
      $this->assertEquals($checkMap[$key], $this->extractorService->isLinkExists($link));
    }

    // Extract internal only.
    $checkMap = [
      FALSE,
      FALSE,
      FALSE,
      TRUE,
    ];
    // Enable blacklist URLs.
    $this->linkcheckerSetting->set('check.disable_link_check_for_urls', "example.com");
    $this->linkcheckerSetting->set('check_links_types', LinkCheckerLinkInterface::TYPE_INTERNAL);
    $this->linkcheckerSetting->save(TRUE);

    foreach ($links as $key => $link) {
      $this->assertEquals($checkMap[$key], $this->extractorService->isLinkExists($link));
    }

    // If parent entity was removed.
    $node->delete();
    // Disable blacklist URLs.
    $this->linkcheckerSetting->set('check.disable_link_check_for_urls', '');
    $this->linkcheckerSetting->set('check_links_types', LinkCheckerLinkInterface::TYPE_ALL);
    $this->linkcheckerSetting->save(TRUE);
    // We should reload links to clear internal runtime cache.
    foreach (LinkCheckerLink::loadMultiple() as $link) {
      $this->assertFalse($this->extractorService->isLinkExists($link));
    }
  }

  /**
   * List of blacklisted links to test.
   *
   * @return array
   *   Links.
   */
  protected function getBlacklistedUrls() {
    return [
      'http://example.net/video/foo1.mov',
      'http://example.net/video/foo2.mov',
      'http://example.net/video/foo3.mov',
      'http://example.org/video/foo1.mp4',
      'http://example.org/video/foo2.mp4',
      'http://example.org/video/foo3.mp4',
      'http://example.org/video/foo4.mp4',
      'http://example.org/video/foo5.mp4',
      'http://example.org/video/foo6.mp4',
      'http://example.org/video/player1.swf',
      'http://example.org/video/player2.swf',
      'http://example.org/video/player3.swf',
      'http://example.com/iframe/',
      'http://example.com/foo bar/is_valid-hack.test',
      'http://example.com/ajax.html#key1=value1&key2=value2',
      'http://example.com/test.html#test',
      'http://example.com/test.html#test%20ABC',
    ];
  }

  /**
   * List of relative links to test.
   *
   * @return array
   *   Links.
   */
  protected function getRelativeUrls() {
    return [
      '../foo1/test.png' => $this->baseUrl . '/foo1/test.png',
      '/foo2/test.png' => $this->baseUrl . '/foo2/test.png',
      'test.png' => $this->baseUrl . '/' . $this->folder1 . '/test.png',
      '../foo1/bar1' => $this->baseUrl . '/foo1/bar1',
      './foo2/bar2' => $this->baseUrl . '/' . $this->folder1 . '/foo2/bar2',
      '../foo3/../foo4/foo5' => $this->baseUrl . '/foo4/foo5',
      './foo4/../foo5/foo6' => $this->baseUrl . '/' . $this->folder1 . '/foo5/foo6',
      './foo4/./foo5/foo6' => $this->baseUrl . '/' . $this->folder1 . '/foo4/foo5/foo6',
      './test/foo bar/is_valid-hack.test' => $this->baseUrl . '/' . $this->folder1 . '/test/foo bar/is_valid-hack.test',
      'flash.png' => $this->baseUrl . '/' . $this->folder1 . '/flash.png',
      'ritmo.mid' => $this->baseUrl . '/' . $this->folder1 . '/ritmo.mid',
      'my_ogg_video.ogg' => $this->baseUrl . '/' . $this->folder1 . '/my_ogg_video.ogg',
      'video.ogv' => $this->baseUrl . '/' . $this->folder1 . '/video.ogv',
      'flvplayer1.swf' => $this->baseUrl . '/' . $this->folder1 . '/flvplayer1.swf',
      'flvplayer2.swf' => $this->baseUrl . '/' . $this->folder1 . '/flvplayer2.swf',
      'foo.ogg' => $this->baseUrl . '/' . $this->folder1 . '/foo.ogg',
    ];
  }

  /**
   * List of external links to test.
   *
   * @return array
   *   Links.
   */
  protected function getExternalUrls() {
    return [
      'http://www.lagrandeepicerie.fr/#e-boutique/Les_produits_du_moment,2/coffret_vins_doux_naturels,149',
      'http://wetterservice.msn.de/phclip.swf?zip=60329&ort=Frankfurt',
      'http://www.msn.de/',
      'http://www.adobe.com/',
      'http://www.apple.com/qtactivex/qtplugin.cab',
      'http://www.theora.org/cortado.jar',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.ogg',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.mov',
      'http://v2v.cc/~j/theora_testsuite/320x240.ogg',
    ];
  }

  /**
   * List of unsupported links to test.
   *
   * @return array
   *   Links.
   */
  protected function getUnsupportedUrls() {
    return [
      'mailto:test@example.com',
      'javascript:foo()',
      '',
    ];
  }

  /**
   * List of links to test.
   *
   * @return array
   *   Key is a link, value is a config.
   */
  protected function getTestUrlList() {
    return array_merge($this->getExternalUrls(), $this->getBlacklistedUrls(), array_keys($this->getRelativeUrls()), $this->getUnsupportedUrls());
  }

}
