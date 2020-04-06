<?php

declare(strict_types = 1);

namespace Drupal\administration_language_negotiation;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for administration language administration condition.
 */
abstract class AdministrationLanguageNegotiationConditionBase extends ConditionPluginBase implements
    AdministrationLanguageNegotiationConditionInterface,
    ContainerFactoryPluginInterface {
  /**
   * The condition's weight, order of execution.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function block() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return $this->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $definition = $this->getPluginDefinition();

    return !empty($definition['description']) ? $definition['description'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $definition = $this->getPluginDefinition();

    return !empty($definition['name']) ? $definition['name'] : $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return !empty($this->weight) ? $this->weight : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function pass() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function postConfigSave(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration[$this->getPluginId()] = $form_state->getValue($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // This should return a summary but it's not used in our case.
  }

}
