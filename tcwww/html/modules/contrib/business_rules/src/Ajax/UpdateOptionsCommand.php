<?php

namespace Drupal\business_rules\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command to update form options.
 *
 * @package Drupal\business_rules\Ajax
 */
class UpdateOptionsCommand implements CommandInterface {

  protected $elementId;

  protected $options;

  protected $formatter;

  /**
   * UpdateOptionsCommand constructor.
   *
   * @param string $elementId
   *   The element html id.
   * @param array $options
   *   The element options [key, value].
   * @param string $formatter
   *   The field formatter.
   */
  public function __construct($elementId, array $options, $formatter) {
    $this->elementId = $elementId;
    $this->options = $options;
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'updateOptionsCommand',
      'method' => 'html',
      'elementId' => $this->elementId,
      'options' => $this->options,
      'formatter' => $this->formatter,
    ];
  }

}
