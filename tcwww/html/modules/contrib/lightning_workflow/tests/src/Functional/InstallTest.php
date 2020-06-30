<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests that our configuration is correctly installed in various contexts.
 *
 * @group lightning_workflow
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_moderation'];

  /**
   * Tests that the editorial workflow is installed if it doesn't already exist.
   */
  public function testWorkflowNotExists() {
    $this->assertEmpty(Workflow::load('editorial'));
    $this->container->get('module_installer')->install(['lightning_workflow']);
    $this->assertInstanceOf(Workflow::class, Workflow::load('editorial'));
  }

  /**
   * Tests that a pre-existing editorial workflow is preserved.
   */
  public function testWorkflowExists() {
    Workflow::create([
      'id' => 'editorial',
      'label' => 'Editorial',
      'type' => 'content_moderation',
    ])->save();

    $this->container->get('module_installer')->install(['lightning_workflow']);

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load('editorial');
    $this->assertFalse($workflow->getTypePlugin()->hasState('review'));
  }

}
