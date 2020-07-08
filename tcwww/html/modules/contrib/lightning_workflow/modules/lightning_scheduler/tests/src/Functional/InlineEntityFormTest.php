<?php

namespace Drupal\Tests\lightning_scheduler\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\lightning_scheduler\Traits\SchedulerUiTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * @group lightning_workflow
 * @group lightning_scheduler
 *
 * @requires inline_entity_form
 */
class InlineEntityFormTest extends BrowserTestBase {

  use SchedulerUiTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'inline_entity_form',
    'lightning_scheduler',
    'lightning_workflow',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'alpha']);
    $this->createContentType(['type' => 'beta']);

    $field_storage = FieldStorageConfig::create([
      'type' => 'entity_reference',
      'entity_type' => 'user',
      'settings' => [
        'target_type' => 'node',
      ],
      'field_name' => 'field_inline_entity',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'user',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'alpha' => 'alpha',
          ],
        ],
      ],
      'label' => 'Inline entity',
    ])->save();

    lightning_workflow_entity_get_form_display('user', 'user', 'default')
      ->setComponent('field_inline_entity', [
        'type' => 'inline_entity_form_simple',
      ])
      ->save();

    $field_storage = FieldStorageConfig::create([
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'settings' => [
        'target_type' => 'node',
      ],
      'field_name' => 'field_inline_entity',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'alpha',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'beta' => 'beta',
          ],
        ],
      ],
      'label' => 'Inline entity',
    ])->save();

    lightning_workflow_entity_get_form_display('node', 'alpha', 'default')
      ->setComponent('field_inline_entity', [
        'type' => 'inline_entity_form_simple',
      ])
      ->save();

    /** @var \Drupal\workflows\Entity\Workflow $workflow */
    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $plugin */
    $plugin = $workflow->getTypePlugin();
    $plugin->addEntityTypeAndBundle('node', 'alpha');
    $plugin->addEntityTypeAndBundle('node', 'beta');
    $workflow->save();

    // Inline Entity Form has a problem referencing entities with other than
    // admin users.
    // @see https://www.drupal.org/project/inline_entity_form/issues/2753553
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Asserts that an inline entity form for field_inline_entity exists.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The inline entity form element.
   */
  private function assertInlineEntityForm() {
    return $this->assertSession()
      ->elementExists('css', '#edit-field-inline-entity-wrapper');
  }

  public function testHostEntityWithoutModeration() {
    $assert_session = $this->assertSession();

    // Test with an un-moderated host entity.
    $this->drupalGet('/user/' . $this->rootUser->id() . '/edit');
    $assert_session->statusCodeEquals(200);
    $inline_entity_form = $this->assertInlineEntityForm();
    $inline_entity_form->fillField('Title', 'Kaboom?');
    $assert_session->selectExists('field_inline_entity[0][inline_entity_form][moderation_state][0][state]', $inline_entity_form);
    $this->getSession()->getPage()->pressButton('Save');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * @depends testHostEntityWithoutModeration
   */
  public function testHostEntityWithModeration() {
    $page = $this->getSession()->getPage();

    // Test with a moderated host entity.
    $this->drupalGet('node/add/alpha');
    $page->fillField('Title', 'Foobar');
    $this->assertInlineEntityForm()->fillField('Title', 'Foobar');

    $host_field = 'moderation_state[0][scheduled_transitions][data]';
    $inline_field = 'field_inline_entity[0][inline_entity_form][moderation_state][0][scheduled_transitions][data]';

    $transition_1 = [
      [
        'state' => 'published',
        'when' => time() + 100,
      ],
    ];
    $transition_2 = [
      [
        'state' => 'published',
        'when' => time() + 200,
      ],
    ];
    $this->setTransitionData($host_field, $transition_1);
    $this->setTransitionData($inline_field, $transition_2);
    $page->pressButton('Save');

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $alpha = $storage->loadByProperties(['type' => 'alpha']);
    $beta = $storage->loadByProperties(['type' => 'beta']);
    $this->assertCount(1, $alpha);
    $this->assertCount(1, $beta);

    $this->drupalGet(reset($alpha)->toUrl('edit-form'));
    $this->assertTransitionData($host_field, $transition_1);

    $this->drupalGet(reset($beta)->toUrl('edit-form'));
    $this->assertTransitionData($host_field, $transition_2);
  }

}

/**
 * Returns the entity form display associated with a bundle and form mode.
 *
 * This is an exact copy of the deprecated entity_get_form_display() from Core
 * 8.6.x except for one change: the default value of the $form_mode parameter.
 *
 * @todo Eliminate this in favor of
 *   \Drupal::service('entity_display.repository')->getFormDisplay() in Core
 *   8.8.x once that is the lowest supported version.
 *
 * @param string $entity_type
 *   The entity type.
 * @param string $bundle
 *   The bundle.
 * @param string $form_mode
 *   The form mode.
 *
 * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
 *   The entity form display associated with the given form mode.
 *
 * @see \Drupal\Core\Entity\EntityStorageInterface::create()
 * @see \Drupal\Core\Entity\EntityStorageInterface::load()
 */
function lightning_workflow_entity_get_form_display($entity_type, $bundle, $form_mode = 'default') {
  // Try loading the entity from configuration.
  $entity_form_display = EntityFormDisplay::load($entity_type . '.' . $bundle . '.' . $form_mode);

  // If not found, create a fresh entity object. We do not preemptively create
  // new entity form display configuration entries for each existing entity type
  // and bundle whenever a new form mode becomes available. Instead,
  // configuration entries are only created when an entity form display is
  // explicitly configured and saved.
  if (!$entity_form_display) {
    $entity_form_display = EntityFormDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => $form_mode,
      'status' => TRUE,
    ]);
  }

  return $entity_form_display;
}
