<?php

namespace Drupal\lightning_workflow\Form;

@trigger_error(__NAMESPACE__ . '\NodeForm is deprecated in lightning_workflow:8.x-3.8 and will be removed in lightning_workflow:8.x-4.0. All functionality provided by this form is now provided by Content Moderation. See https://www.drupal.org/node/2923517', E_USER_DEPRECATED);

use Drupal\node\NodeForm as BaseNodeForm;

/**
 * A moderation state-aware version of the node entity form.
 */
class NodeForm extends BaseNodeForm {
}
