<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Test html link extractor.
 *
 * @group linkchecker
 */
class LinkcheckerHtmlLinkExtractorTest extends KernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'node',
    'filter',
    'system',
    'field',
    'text',
    'dynamic_entity_reference',
    'linkchecker',
  ];

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $linkcheckerSetting;

  /**
   * HTML link extractor.
   *
   * @var \Drupal\linkchecker\Plugin\LinkExtractor\HtmlLinkExtractor
   */
  protected $htmlLinkExtractor;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installSchema('linkchecker', 'linkchecker_index');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig(['field', 'node', 'filter', 'linkchecker']);

    $this->linkcheckerSetting = $this->container->get('config.factory')
      ->getEditable('linkchecker.settings');

    /** @var \Drupal\linkchecker\Plugin\LinkExtractorManager $extractorManager */
    $extractorManager = $this->container->get('plugin.manager.link_extractor');
    $this->htmlLinkExtractor = $extractorManager->createInstance('html_link_extractor');
  }

  /**
   * Test HTML extractor.
   */
  public function testHtmlExtractor() {
    $type = NodeType::create(['name' => 'Links', 'type' => 'links']);
    $type->save();
    node_add_body_field($type);

    $node = $this->createNode([
      'type' => 'links',
      'body' => [
        [
          'value' => $this->getTestBody(),
        ],
      ],
    ]);

    $htmlTagConfig = [
      'from_a',
      'from_audio',
      'from_embed',
      'from_iframe',
      'from_img',
      'from_object',
      'from_video',
    ];

    // First disable extraction from each tag.
    foreach ($htmlTagConfig as $tagConfigName) {
      $this->linkcheckerSetting->set('extract.' . $tagConfigName, FALSE);
    }
    $this->linkcheckerSetting->save(TRUE);
    // Test extraction for each HTML-tag.
    // In this case we will check if config conditions works well.
    foreach ($htmlTagConfig as $tagConfigName) {
      $this->linkcheckerSetting->set('extract.' . $tagConfigName, TRUE);
      $this->linkcheckerSetting->save(TRUE);

      $testCases = array_filter($this->getTestUrlList(), function ($item) use ($tagConfigName) {
        return $item == $tagConfigName;
      });
      $testCases = array_keys($testCases);

      $extractedUrls = $this->htmlLinkExtractor->extract($node->get('body')
        ->getValue());

      foreach ($testCases as $url) {
        $this->assertTrue(in_array($url, $extractedUrls), new FormattableMarkup('URL @url was not extracted from tag @tag!', [
          '@url' => $url,
          '@tag' => str_replace('from_', '', $tagConfigName),
        ]));
      }

      $countTestCases = count($testCases);

      $countExtractedLinks = count($extractedUrls);
      $this->assertEquals($countTestCases, $countExtractedLinks, new FormattableMarkup('Expected to extract @count but get @actual links.', [
        '@count' => $countTestCases,
        '@actual' => $countExtractedLinks,
      ]));

      $this->linkcheckerSetting->set('extract.' . $tagConfigName, FALSE);
      $this->linkcheckerSetting->save(TRUE);
    }
  }

  /**
   * Get test HTML string.
   *
   * @return string
   *   HTML.
   */
  protected function getTestBody() {
    return <<<EOT
<!-- UNSUPPORTED for link checking: -->

<a href="mailto:test@example.com">Send email</a>
<a href="javascript:foo()">Execute JavaScript</a>

<!-- SUPPORTED for link checking: -->

<!-- URL in HTML comment: http://example.com/test-if-url-filter-is-disabled -->

<!-- Relative URLs -->
<img src="test.png" alt="Test image 1" />
<img src="../foo1/test.png" alt="Test image 2" />

<a href="../foo1/bar1">../foo1/bar1</a>
<a href="./foo2/bar2">./foo2/bar2</a>
<a href="../foo3/../foo4/foo5">../foo3/../foo4/foo5</a>
<a href="./foo4/../foo5/foo6">./foo4/../foo5/foo6</a>
<a href="./foo4/./foo5/foo6">./foo4/./foo5/foo6</a>
<a href="./test/foo bar/is_valid-hack.test">./test/foo bar/is_valid-hack.test</a>

<!-- URL with uncommon chars that could potentially fail to extract. See http://drupal.org/node/465462. -->
<a href="http://www.lagrandeepicerie.fr/#e-boutique/Les_produits_du_moment,2/coffret_vins_doux_naturels,149">URL with uncommon chars</a>
<a href="http://example.com/foo bar/is_valid-hack.test">URL with space</a>
<a href="http://example.com/ajax.html#key1=value1&key2=value2">URL with ajax query params</a>
<a href="http://example.com/test.html#test">URL with standard anchor</a>
<a href="http://example.com/test.html#test%20ABC">URL with standard anchor and space</a>
<a name="test ABC">Anchor with space</a>

<!-- object tag: Embed SWF files -->
<object width="150" height="116"
  type="application/x-shockwave-flash"
  data="http://wetterservice.msn.de/phclip.swf?zip=60329&ort=Frankfurt">
    <param name="movie" value="http://wetterservice.msn.de/phclip.swf?zip=60329&ort=Frankfurt" />
    <img src="flash.png" width="150" height="116" alt="" /> <br />
      No weather report visible? At <a href="http://www.msn.de/">MSN</a>
      you are able to find the weather report missing here and the
      Flash plugin can be found at <a href="http://www.adobe.com/">Adobe</a>.
</object>

<!-- object tag: Embed Quicktime Movies on HTML pages -->
<object width="420" height="282"
  classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
  codebase="http://www.apple.com/qtactivex/qtplugin.cab">
  <param name="src" value="http://example.net/video/foo1.mov" />
  <param name="href" value="http://example.net/video/foo2.mov" />
  <param name="controller" value="true" />
  <param name="autoplay" value="false" />
  <param name="scale" value="aspect" />
  <!--[if gte IE 7]> <!-->
  <object type="video/quicktime" data="http://example.net/video/foo3.mov" width="420" height="282">
    <param name="controller" value="true" />
    <param name="autoplay" value="false" />
  </object>
  <!--<![endif]-->
</object>

<!-- object tag: Play MP4 videos on HTML pages -->
<object data="http://example.org/video/foo1.mp4" type="video/mp4" width="420" height="288">
  <param name="src" value="http://example.org/video/foo2.mp4" />
  <param name="autoplay" value="false" />
  <param name="autoStart" value="0" />
  <a href="http://example.org/video/foo3.mp4">/video/foo3.mp4</a>
</object>

<!-- object tag: Play MP4 videos with Quicktime -->
<object width="420" height="282" codebase="http://www.apple.com/qtactivex/qtplugin.cab">
  <param name="src" value="http://example.org/video/foo4.mp4" />
  <param name="href" value="http://example.org/video/foo5.mp4" />
  <param name="controller" value="true" />
  <param name="autoplay" value="false" />
  <param name="scale" value="aspect" />
  <!--[if gte IE 7]> <!-->
  <object type="video/quicktime" data="http://example.org/video/foo6.mp4" width="420" height="282">
    <param name="controller" value="true" />
    <param name="autoplay" value="false" />
  </object>
  <!--<![endif]-->
</object>

<!-- object tag: Play flash videos on HTML pages -->
<object type="application/x-shockwave-flash" data="http://example.org/video/player1.swf" width="420" height="270">
    <param name="movie" value="http://example.org/video/player2.swf" />
    <param src="movie" value="http://example.org/video/player3.swf" />
    <param name="flashvars" value="file=http://example.org/video/foo1.flv&width=420&height=270" />
</object>

<!-- Embed ActiveX control as objekt -->
<object width="267" height="175" classid="CLSID:05589FA1-C356-11CE-BF01-00AA0055595A">
  <param name="filename" value="ritmo.mid">
</object>

<!-- Add inline frames -->
<iframe src="http://example.com/iframe/" name="ExampleIFrame" width="300" height="200">
  <p>Your browser does not support inline frames.</p>
</iframe>

<!-- https://developer.mozilla.org/en/Using_audio_and_video_in_Firefox -->

<!-- http://www.theora.org/cortado/ -->
<video src="my_ogg_video.ogg" controls width="320" height="240">
  <object type="application/x-java-applet" width="320" height="240">
    <param name="archive" value="http://www.theora.org/cortado.jar">
    <param name="code" value="com.fluendo.player.Cortado.class">
    <param name="url" value="my_ogg_video2.ogg">
    <p>You need to install Java to play this file.</p>
  </object>
</video>

<video src="video.ogv" controls>
  <object data="flvplayer1.swf" type="application/x-shockwave-flash">
    <param name="movie" value="flvplayer2.swf" />
  </object>
</video>

<video controls>
  <source src="http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.ogg" type="video/ogg">
  <source src="http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.mov">
  Your browser does not support the <code>video</code> element.
</video>

<video controls>
  <source src="foo.ogg" type="video/ogg; codecs=&quot;dirac, speex&quot;">
  Your browser does not support the <code>video</code> element.
</video>

<video src="http://v2v.cc/~j/theora_testsuite/320x240.ogg" controls>
  Your browser does not support the <code>video</code> element.
</video>
EOT;
  }

  /**
   * List of links to test.
   *
   * @return array
   *   Key is a link, value is a config.
   */
  protected function getTestUrlList() {
    return [
      'http://www.lagrandeepicerie.fr/#e-boutique/Les_produits_du_moment,2/coffret_vins_doux_naturels,149' => 'from_a',
      'http://wetterservice.msn.de/phclip.swf?zip=60329&ort=Frankfurt' => 'from_object',
      'http://www.msn.de/' => 'from_a',
      'http://www.adobe.com/' => 'from_a',
      'http://www.apple.com/qtactivex/qtplugin.cab' => 'from_object',
      'http://example.net/video/foo1.mov' => 'from_object',
      'http://example.net/video/foo2.mov' => 'from_object',
      'http://example.net/video/foo3.mov' => 'from_object',
      'http://example.org/video/foo1.mp4' => 'from_object',
      'http://example.org/video/foo2.mp4' => 'from_object',
      'http://example.org/video/foo3.mp4' => 'from_a',
      'http://example.org/video/foo4.mp4' => 'from_object',
      'http://example.org/video/foo5.mp4' => 'from_object',
      'http://example.org/video/foo6.mp4' => 'from_object',
      'http://example.org/video/player1.swf' => 'from_object',
      'http://example.org/video/player2.swf' => 'from_object',
      'http://example.org/video/player3.swf' => 'from_object',
      'http://example.com/iframe/' => 'from_iframe',
      'http://www.theora.org/cortado.jar' => 'from_object',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.ogg' => 'from_video',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.mov' => 'from_video',
      'http://v2v.cc/~j/theora_testsuite/320x240.ogg' => 'from_video',
      'http://example.com/foo bar/is_valid-hack.test' => 'from_a',
      'http://example.com/ajax.html#key1=value1&key2=value2' => 'from_a',
      'http://example.com/test.html#test' => 'from_a',
      'http://example.com/test.html#test%20ABC' => 'from_a',
      '../foo1/test.png' => 'from_img',
      'test.png' => 'from_img',
      '../foo1/bar1' => 'from_a',
      './foo2/bar2' => 'from_a',
      '../foo3/../foo4/foo5' => 'from_a',
      './foo4/../foo5/foo6' => 'from_a',
      './foo4/./foo5/foo6' => 'from_a',
      './test/foo bar/is_valid-hack.test' => 'from_a',
      'flash.png' => 'from_img',
      'ritmo.mid' => 'from_object',
      'my_ogg_video.ogg' => 'from_video',
      'my_ogg_video2.ogg' => 'from_object',
      'video.ogv' => 'from_video',
      'flvplayer1.swf' => 'from_object',
      'flvplayer2.swf' => 'from_object',
      'foo.ogg' => 'from_video',
      'mailto:test@example.com' => 'from_a',
      'javascript:foo()' => 'from_a',
    ];
  }

}
