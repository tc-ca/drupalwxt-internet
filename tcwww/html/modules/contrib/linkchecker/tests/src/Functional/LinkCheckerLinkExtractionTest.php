<?php

namespace Drupal\Tests\linkchecker\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\Entity\FilterFormat;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Test Link checker module link extraction functionality.
 *
 * @group linkchecker
 */
class LinkCheckerLinkExtractionTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'filter',
    'linkchecker',
    'node',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Full HTML text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ]);
    $filtered_html_format->save();

    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $full_html_format->save();

    // Create Basic page and Article node types.
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
      'format' => 'full_html',
    ]);
    $node_type->save();

    // Create a body field instance for the 'page' node type.
    $node_body_field = node_add_body_field($node_type);
    $node_body_field->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $node_body_field->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $node_body_field->save();

    // Configure basic settings.
    $this->config('linkchecker.settings')->set('default_url_scheme', 'http://')->save();
    $this->config('linkchecker.settings')->set('base_path', 'unexistingdomain.org/')->save();

    $this->config('linkchecker.settings')->set('check.disable_link_check_for_urls', '')->save();
    $this->config('linkchecker.settings')->set('check_links_types', LinkCheckerLinkInterface::TYPE_ALL)->save();

    // Core enables the URL filter for "Full HTML" by default.
    // -> Blacklist / Disable URL filter for testing.
    $this->config('linkchecker.settings')->set('extract.filter_blacklist', ['filter_url' => 'filter_url'])->save();

    // Extract from all link checker supported HTML tags.
    $this->config('linkchecker.settings')->set('extract.from_a', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_audio', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_embed', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_iframe', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_img', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_object', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_video', 1)->save();

    // User to set up link checker.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer url aliases',
      'create page content',
      'create url aliases',
      'edit own page content',
      $filtered_html_format->getPermissionName(),
      $full_html_format->getPermissionName(),
    ]);
    $this->drupalLogin($this->adminUser);
  }

  public function testLinkCheckerCreateNodeWithLinks() {
    $body = <<<EOT
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
    <param name="url" value="my_ogg_video.ogg">
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

    // Save folder names in variables for reuse.
    $random = new \Drupal\Component\Utility\Random();
    $folder1 = $random->name(10);
    $folder2 = $random->name(5);

    // Fill node array.
    $edit = [];
    $edit["title[0][value]"] = $random->name(32);
    $edit["body[0][value]"] = $body;
    //$edit["body[0][format]"] = 'full_html';
    $edit['path[0][alias]'] = '/' . $folder1 . '/' . $folder2;

    // Extract only full qualified URLs.
    $this->config('linkchecker.settings')->set('check_links_types', LinkCheckerLinkInterface::TYPE_EXTERNAL)->save();

    // Verify path input field appears on add "Basic page" form.
    $this->drupalGet('node/add/page');
    // Verify path input is present.
    $this->assertFieldByName('path[0][alias]', '', 'Path input field present on add Basic page form.');

    // Save node.
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertText($this->t('@type @title has been created.', ['@type' => 'Basic page', '@title' => $edit["title[0][value]"]]), 'Node was created.');

    $key = \Drupal::state()->get('system.cron_key');
    $this->drupalGet('cron/' . $key);
    $this->assertResponse(204);

    // Verify if the content links are extracted properly.
    $urls_fqdn = [
      'http://www.lagrandeepicerie.fr/#e-boutique/Les_produits_du_moment,2/coffret_vins_doux_naturels,149',
      'http://wetterservice.msn.de/phclip.swf?zip=60329&ort=Frankfurt',
      'http://www.msn.de/',
      'http://www.adobe.com/',
      'http://www.apple.com/qtactivex/qtplugin.cab',
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
      'http://www.theora.org/cortado.jar',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.ogg',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.mov',
      'http://v2v.cc/~j/theora_testsuite/320x240.ogg',
      'http://example.com/foo bar/is_valid-hack.test',
      'http://example.com/ajax.html#key1=value1&key2=value2',
      'http://example.com/test.html#test',
      'http://example.com/test.html#test%20ABC',
    ];

    foreach ($urls_fqdn as $org_url => $check_url) {
      $link = $this->getLinkCheckerLinkByUrl($check_url);

      if ($link) {
        $this->assertIdentical($link->get('url')->value, $check_url, new FormattableMarkup('Absolute URL %org_url matches expected result %check_url.', ['%org_url' => $org_url, '%check_url' => $check_url]));
      }
      else {
        $this->fail(new FormattableMarkup('URL %check_url not found.', ['%check_url' => $check_url]));
      }
    }

    // Check if the number of links is correct.
    // - Verifies if all HTML tag regexes matched.
    // - Verifies that the linkchecker filter blacklist works well.
    $urls_in_database = \Drupal::entityQuery('linkcheckerlink')->count()->execute();
    $urls_expected_count = count($urls_fqdn);
    $this->assertEqual($urls_in_database, $urls_expected_count, new FormattableMarkup('Found @urls_in_database URLs in database matches expected result of @urls_expected_count.', ['@urls_in_database' => $urls_in_database, '@urls_expected_count' => $urls_expected_count]));

    // Extract all URLs including relative path.
    // @FIXME
    //variable_set('clean_url', 1);
    $this->config('linkchecker.settings')->set('check_links_types', LinkCheckerLinkInterface::TYPE_ALL)->save();

    $node = $this->drupalGetNodeByTitle($edit["title[0][value]"]);
    $this->assertNotEmpty($node);
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, $this->t('Save'));
    //$this->assertRaw($this->t('@type %title has been updated.', ['@type' => 'Basic page', '%title' => $edit["title[0][value]"]]));
    $this->assertText($this->t('@type @title has been updated.', ['@type' => 'Basic page', '@title' => $edit['title[0][value]']]));

    // @todo Path alias seems not saved!???
    //$this->assertIdentical($node->path, '/' . $edit[0]['path'], format_string('URL alias "@node-path" matches path "@edit-path".', array('@node-path' => $node->path, '@edit-path' => $edit[0]['path'])));

    // Verify if the content links are extracted properly.
    $base_path = $this->config('linkchecker.settings')->get('default_url_scheme') . $this->config('linkchecker.settings')->get('base_path');

    $urls_relative = [
      '../foo1/test.png' => $base_path . 'foo1/test.png',
      'test.png' => $base_path . $folder1 . '/test.png',
      '../foo1/bar1' => $base_path . 'foo1/bar1',
      './foo2/bar2' => $base_path . $folder1 . '/foo2/bar2',
      '../foo3/../foo4/foo5' => $base_path . 'foo4/foo5',
      './foo4/../foo5/foo6' => $base_path . $folder1 . '/foo5/foo6',
      './foo4/./foo5/foo6' => $base_path . $folder1 . '/foo4/foo5/foo6',
      './test/foo bar/is_valid-hack.test' => $base_path . $folder1 . '/test/foo bar/is_valid-hack.test',
      'flash.png' => $base_path . $folder1 . '/flash.png',
      'ritmo.mid' => $base_path . $folder1 . '/ritmo.mid',
      'my_ogg_video.ogg' => $base_path . $folder1 . '/my_ogg_video.ogg',
      'video.ogv' => $base_path . $folder1 . '/video.ogv',
      'flvplayer1.swf' => $base_path . $folder1 . '/flvplayer1.swf',
      'flvplayer2.swf' => $base_path . $folder1 . '/flvplayer2.swf',
      'foo.ogg' => $base_path . $folder1 . '/foo.ogg',
    ];

    foreach ($urls_relative as $org_url => $check_url) {
      $link = $this->getLinkCheckerLinkByUrl($check_url);

      if ($link) {
        //$this->assertIdentical($link->get('url')->value, $check_url, new FormattableMarkup('Relative URL %org_url matches expected result %check_url.', ['%org_url' => $org_url, '%check_url' => $check_url]));
      }
      else {
        //$this->fail(new FormattableMarkup('URL %check_url not found.', ['%check_url' => $check_url]));
      }
    }

    // Check if the number of links is correct.
    $urls_in_database = \Drupal::entityQuery('linkcheckerlink')->count()->execute();
    $urls_expected_count = count($urls_fqdn + $urls_relative);
    $this->assertEqual($urls_in_database, $urls_expected_count, new FormattableMarkup('Found @urls_in_database URLs in database matches expected result of @urls_expected_count.', ['@urls_in_database' => $urls_in_database, '@urls_expected_count' => $urls_expected_count]));

    // Verify if link check has been enabled for normal URLs.
    $urls = [
      'http://www.lagrandeepicerie.fr/#e-boutique/Les_produits_du_moment,2/coffret_vins_doux_naturels,149',
      'http://wetterservice.msn.de/phclip.swf?zip=60329&ort=Frankfurt',
      'http://www.msn.de/',
      'http://www.adobe.com/',
      'http://www.apple.com/qtactivex/qtplugin.cab',
      'http://www.theora.org/cortado.jar',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.ogg',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.mov',
      'http://v2v.cc/~j/theora_testsuite/320x240.ogg',
      $base_path . 'foo1/test.png',
      $base_path . $folder1 . '/test.png',
      $base_path . 'foo1/bar1',
      $base_path . $folder1 . '/foo2/bar2',
      $base_path . 'foo4/foo5',
      $base_path . $folder1 . '/foo5/foo6',
      $base_path . $folder1 . '/foo4/foo5/foo6',
      $base_path . $folder1 . '/test/foo bar/is_valid-hack.test',
      $base_path . $folder1 . '/flash.png',
      $base_path . $folder1 . '/ritmo.mid',
      $base_path . $folder1 . '/my_ogg_video.ogg',
      $base_path . $folder1 . '/video.ogv',
      $base_path . $folder1 . '/flvplayer1.swf',
      $base_path . $folder1 . '/flvplayer2.swf',
      $base_path . $folder1 . '/foo.ogg',
    ];

    foreach ($urls as $url) {
      $link = $this->getLinkCheckerLinkByUrl($url);

      // @FIXME
      //$this->assertTrue($link->status, format_string('Link check for %url is enabled.', ['%url' => $url]));
    }

    // Verify if link check has been disabled for example.com/net/org URLs.
    $documentation_urls = [
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

    foreach ($documentation_urls as $documentation_url) {
      $link = $this->getLinkCheckerLinkByUrl($documentation_url);

      // @FIXME
      //$this->assertFalse($link->status, format_string('Link check for %url is disabled.', ['%url' => $documentation_url]));
    }
  }

  /**
   * Get a link checker link entity by the given URl.
   *
   * @param string $url
   *   The url.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   A link checker link entity when found, else NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLinkCheckerLinkByUrl(string $url) {
    $links = \Drupal::entityTypeManager()
      ->getStorage('linkcheckerlink')
      ->loadByProperties([
        'urlhash' => LinkCheckerLink::generateHash($url),
      ]);
    return current($links);
  }

}
