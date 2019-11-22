<?php

namespace Drupal\core_context_test\Plugin\Block;

/**
 * @Block(
 *   id = "context_block_optional",
 *   admin_label = @Translation("Optional context block"),
 *   context_definitions = {
 *     "value" = @ContextDefinition("any", required = FALSE),
 *     "letter" = @ContextDefinition("string", required = FALSE),
 *   },
 * )
 */
class ContextBlockOptional extends ContextBlock {
}
