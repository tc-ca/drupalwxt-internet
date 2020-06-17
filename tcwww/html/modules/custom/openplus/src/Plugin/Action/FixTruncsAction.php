<?php

namespace Drupal\openplus\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\openplus_migrate\Util\ConfigUtil;

/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * @Action(
 *   id = "fix_truncated_action",
 *   label = @Translation("Fix truncated link text"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class FixTruncsAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

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

    $source = $entity->get('field_source_url')->getValue();

    $mig_uuid = "a2373ba9-aad8-4650-991a-660aa4f97e28";
    $vars = [
      'mig_uuid' => $mig_uuid,
      'page' => $source[0]['uri'],
      'ext_only' => FALSE,
    ];

    $uri = ConfigUtil::GetHarvesterBaseUrl() . 'get-harvest-links';
    $headers = [
      'Accept' => 'application/json; charset=utf-8',
      'Content-Type' => 'application/json',
    ];
    $request = \Drupal::httpClient()
      ->post($uri, array(
        'headers' => $headers,
        'auth' => [ConfigUtil::GetHarvesterUser(), ConfigUtil::GetHarvesterPass()],
        'body' => json_encode($vars),
      ));
    $links = json_decode($request->getBody());

    $body = $entity->get('body')->first()->getValue();
    $body = $body['value'];
    $pattern = '/<a\s+([^>]*?\s+)?href="([^"]+)"\s?(.*?)>(.+?)<\/a>/s';
    $matches = [];
    preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);

    $found = [];
    foreach ($matches as $match) {
      $found_key = NULL;
      foreach ($links as $key => $link) {
        $link_title = rtrim(preg_replace("/[\r\n]+/", '', $link->metadata));
        $drupal_link_title = rtrim($match[4]);
        if (strpos($link_title, $drupal_link_title) !== FALSE && strlen($drupal_link_title) != strlen($link_title)) {
          $replacement = str_replace($drupal_link_title, $link_title, $match[0]); 
          $body = str_replace($match[0], $replacement, $body);
          $found_key = $key;
          break;
        }
      }
      if (is_numeric($found_key)) {
        unset($links[$found_key]);
      }
    }

    $entity->set('body', ['value' => $body, 'format' => 'rich_text']);
    $entity->save();

    return $this->t('Updated truncated links.');
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
