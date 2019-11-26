<?php

namespace Drupal\Tests\lightning_landing_page\Kernel;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests installation of Lightning Landing Page.
 *
 * @group lightning_layout
 */
class InstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'lightning_landing_page',
    'node',
    'workflows',
  ];

  /**
   * Tests installing Lightning Workflow.
   *
   * @param bool $should_apply
   *   (optional) Whether or not the workflow will apply to the landing_page
   *   content type. Defaults to TRUE.
   */
  public function testInstallWorkflow($should_apply = TRUE) {
    // The module should not directly react to the installation of
    // lightning_workflow.
    lightning_landing_page_modules_installed(['lightning_workflow']);

    // Create a fake module handler which will report that lightning_workflow
    // is installed. It is too cumbersome to bring it in as a real dev
    // dependency, so this is a decent way to hack it.
    $this->container->set('module_handler', new TestModuleHandler(
      $this->root,
      $this->container->getParameter('container.modules'),
      new NullBackend('discovery')
    ));

    Workflow::create([
      'id' => 'editorial',
      'label' => 'Editorial',
      'type' => 'content_moderation',
    ])->save();

    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $type_plugin */
    $type_plugin = Workflow::load('editorial')->getTypePlugin();
    $this->assertSame($should_apply, $type_plugin->appliesToEntityTypeAndBundle('node', 'landing_page'));
  }

  /**
   * Tests installing Lightning Workflow during config sync.
   */
  public function testInstallWorkflowDuringSync() {
    $this->container->get('config.installer')->setSyncing(TRUE);
    $this->testInstallWorkflow(FALSE);
  }

}

/**
 * Fake module handler that always reports lightning_workflow is installed.
 */
class TestModuleHandler extends ModuleHandler {

  /**
   * {@inheritdoc}
   */
  public function moduleExists($module) {
    return $module === 'lightning_workflow' ? TRUE : parent::moduleExists($module);
  }

}
