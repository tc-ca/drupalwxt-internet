<?php

declare(strict_types = 1);

namespace Drupal\administration_language_negotiation\Annotation;

use Drupal\Core\Condition\Annotation\Condition;

/**
 * Defines a administration language negotiation condition annotation object.
 *
 * Plugin Namespace: Plugin\AdministrationLanguageNegotiationCondition.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AdministrationLanguageNegotiationCondition extends Condition {

  /**
   * Description of the administration language negotiation condition plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Administration language negotiation condition plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Human-readable name of the condition plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * Weight of the administration language negotiation condition plugin.
   *
   * @var int
   */
  public $weight;

}
