<?php

namespace Drupal\Tests\lightning_workflow;

use Drupal\block\Entity\Block;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\lightning_core\FixtureBase;
use Drupal\views\Entity\View;

final class FixtureContext extends FixtureBase {

  /**
   * @BeforeScenario
   */
  public function setUp() {
    $this->installModule('views');

    // Cache the original state of the editorial workflow.
    $this->config('workflows.workflow.editorial');

    // Create a temporary content type specifically for testing.
    $node_type = NodeType::create([
      'type' => 'moderated',
      'name' => 'Moderated',
    ]);
    $node_type->setThirdPartySetting('lightning_workflow', 'workflow', 'editorial');
    $node_type->setThirdPartySetting('lightning_workflow', 'autosave', TRUE);
    $this->save($node_type);
    node_add_body_field($node_type);

    // Cache the original state of the content view.
    $this->config('views.view.content');

    // Allow the content view to filter by moderation state.
    $view = View::load('content')->enforceIsNew();
    lightning_workflow_view_presave($view);
    $view->enforceIsNew(FALSE)->save();

    // Ensure that the main content block exists.
    $values = [
      'theme' => $this->container->get('theme.manager')->getActiveTheme()->getName(),
      'plugin' => 'system_main_block',
    ];

    $main_content_block = $this->container->get('entity_type.manager')
      ->getStorage('block')
      ->loadByProperties($values);

    if (empty($main_content_block)) {
      $values['id'] = $values['theme'] . '_content';
      $values['region'] = 'content';
      $block = Block::create($values);
      $this->save($block);
    }

    $this->installTheme('seven');
    $this->config('system.date')->clear('timezone.default')->save();
    $this->config('system.theme')->set('admin', 'seven')->save();
    $this->config('lightning_scheduler.settings')->set('time_step', 1)->save();
  }

  /**
   * @AfterScenario
   */
  public function tearDown() {
    // This pointless if check is here to work around a needlessly strict rule
    // in the coding standards.
    if (TRUE) {
      parent::tearDown();
    }
  }

}
