<?php

namespace Drupal\Tests\blog\Functional;

use Drupal;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Breadcrumb test for blog module.
 *
 * @group blog
 */
class BreadcrumbTest extends BlogTestBase {

  use BlockCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'blog',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add breadcrumb block.
    $this->placeBlock('system_breadcrumb_block', ['region' => 'content']);
  }

  /**
   * Blog node type breadcrumb test.
   */
  public function testBlogNodeBreadcrumb() {
    $blog_nid = array_rand($this->blogNodes1);
    $blog_owner = $this->blogNodes1[$blog_nid]->getOwner();
    $this->drupalGet('node/' . $blog_nid);
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', '.block-system-breadcrumb-block li a');
    $this->assertEquals(count($links), 3, 'Breadcrumb element number is correctly.');
    [$home, $blogs, $personal_blog] = $links;
    $this->assertTrue(($home->getAttribute('href') == '/' && $home->getHtml() == 'Home'), 'Home link correctly.');
    $this->assertTrue(($blogs->getAttribute('href') == '/blog' && $blogs->getHtml() == 'Blogs'), 'Blogs link correctly.');
    $blog_name = Drupal::service('blog.lister')->userBlogTitle($blog_owner);
    $blog_url = '/blog/' . $blog_owner->id();
    $this->assertTrue(($personal_blog->getAttribute('href') == $blog_url && $personal_blog->getHtml() == $blog_name), 'Personal blog link correctly.');
  }

  /**
   * Other node type breadcrumb test.
   */
  public function testOtherNodeBreadcrumb() {
    $article_nid = array_rand($this->articleNodes1);
    $article_owner = $this->articleNodes1[$article_nid]->getOwner();
    $blog_name = Drupal::service('blog.lister')->userBlogTitle($article_owner);
    $this->drupalGet('node/' . $article_nid);
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', '.block-system-breadcrumb-block li a');
    $link = array_pop($links);
    $this->assertFalse($link->getHtml() == $blog_name, 'Other node type breadcrumb is correct.');
  }

}
