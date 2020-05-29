<?php

namespace Drupal\openplus\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * @Action(
 *   id = "fix_rdims_action",
 *   label = @Translation("Fix RDIMS field"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class FixRdimsAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /*
     * All config resides in $this->configuration.
     * Passed view rows will be available in $this->context.
     * Data about the view used to select results and optionally
     * the batch context are available in $this->context or externally
     * through the public getContext() method.
     * The entire ViewExecutable object  with selected result
     * rows is available in $this->view or externally through
     * the public getView() method.
     */

    //$this->messenger()->addMessage($entity->label() . ' - ' . $entity->language()->getId() . ' - ' . $entity->id());
    //return sprintf('Example action (configuration: %s)', print_r($this->configuration, TRUE));
    if ($entity->hasField('field_reference_number')) {
      $rdims = $entity->get('field_reference_number')->getValue();
      if (isset($rdims[0]['value']) && !empty($rdims[0]['value'])) {
        $v = ltrim($rdims[0]['value']);
        $v = str_replace("&#xA;", ' ', $v);
        $v = str_replace(' - ', ' ', $v);
        $v = str_replace('#', '', $v);
        $parts = explode(' ', $v);
        if (count($parts) > 1) {
          list($sym, $ref) = $parts;
          if (strlen($sym) < 10) {
            $entity->set('field_reference_number', $ref);
            $entity->set('field_routing_symbol', $sym);
            $entity->save();
          }
          else {
            \Drupal::logger('op')->notice('Skipping node: ' . $entity->id() . ' : ' . $v);
          }
        }
      }
    }

    return $this->t('Updated routing symbol/reference.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Configuration form builder.
   *
   * If this method has implementation, the action is
   * considered to be configurable.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Submit handler for the action configuration form.
   *
   * If not implemented, the cleaned form values will be
   * passed direclty to the action $configuration parameter.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // This is not required here, when this method is not defined,
    // form values are assigned to the action configuration by default.
    // This function is a must only when user input processing is needed.
    //$this->configuration['example_config_setting'] = $form_state->getValue('example_config_setting');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityType() === 'node') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }

}
