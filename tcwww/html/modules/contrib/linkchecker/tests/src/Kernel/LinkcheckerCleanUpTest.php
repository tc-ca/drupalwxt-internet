<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\linkchecker\Entity\LinkCheckerLink;

/**
 * Test for linkchecker.clean_up service.
 *
 * @coversDefaultClass \Drupal\linkchecker\LinkCleanUp
 *
 * @group linkchecker
 */
class LinkcheckerCleanUpTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'linkchecker',
    'dynamic_entity_reference',
  ];

  /**
   * The link clean up service.
   *
   * @var \Drupal\linkchecker\LinkCleanUp
   */
  protected $linkCleanUp;

  /**
   * The link checker link storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkCheckerLinkStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig('linkchecker');

    $this->linkCleanUp = $this->container->get('linkchecker.clean_up');
    $this->linkCheckerLinkStorage = $this->container->get('entity_type.manager')
      ->getStorage('linkcheckerlink')
    ;
  }

  /**
   * @covers ::removeAllBatch
   */
  public function testRemoveAllBatch() {
    $urls = [
      'https://existing.com',
      'https://not-existing.com',
      'https://example.com/existing',
    ];

    foreach ($urls as $url) {
      $this->createDummyLink($url);
    }

    $this->assertCount(3, $this->linkCheckerLinkStorage->loadMultiple(NULL));

    $this->linkCleanUp->removeAllBatch();
    $this->runBatch();

    $this->assertEmpty($this->linkCheckerLinkStorage->loadMultiple($this->linkCheckerLinkStorage->loadMultiple(NULL)));
  }

  /**
   * Helper function for link creation.
   */
  protected function createDummyLink($url) {
    $link = LinkCheckerLink::create([
      'url' => $url,
      'entity_id' => [
        'target_id' => 1,
        'target_type' => 'dummy_type',
      ],
      'entity_field' => 'dummy_field',
      'entity_langcode' => 'en',
    ]);
    $link->save();
    return $link;
  }

  /**
   * Runs the currently set batch, if any exists.
   */
  protected function runBatch() {
    $batch = &batch_get();
    if ($batch) {
      $batch['progressive'] = FALSE;
      batch_process();
    }
  }

}
