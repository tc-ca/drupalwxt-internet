<?php

namespace Drupal\openplus\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\openplus_migrate\Util\ConfigUtil;

/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * @Action(
 *   id = "fix_badfrags_action",
 *   label = @Translation("Fix improperly linked fragments"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class FixBadFragsAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {


    $vids = \Drupal::entityManager()->getStorage('node')->revisionIds($entity);

    // get the fragments on the original
    $rvids = array_reverse($vids);
    $oldest_vid = array_pop($rvids);
    $node_first = node_revision_load($oldest_vid);
    $fragments = SELF::getOriginalFragments($orig_body);

    // get links on node
    $body = $entity->get('body')->value;
    $pattern = '/<a\s+([^>]*?\s+)?href="([^"]+)#([^"]+)"\s?(.*?)>(.+?)<\/a>/s';
    $matches = [];
    preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);

    if ($matches) {
      // we have links in the page
      $updated = FALSE;
      foreach ($matches as $match) {
        if (strpos($match[2], '/node/') !== FALSE && !empty($match[3])) {
          // we have a link with a fragment in the node body
          if (isset($fragments[$match[3]]) && $match[5] == $fragments[$match[3]]['title']) {
            // we have a bad fragment
            // it exists as a same page anchor in the old revision and the link title matches
            $updated = TRUE;
            $find = $match[0];
            $replacement = $fragments[$match[3]]['link'];
            $body = str_replace($find, $replacement, $body);
          }
        }

        if ($updated) {
          $entity->set('body', ['value' => $body, 'format' => 'rich_text']);
          $entity->save();
        }
      }
    }

    return $this->t('Updated links with fragments.');
  }

  public function getOriginalFragments($body) {
    $pattern = '/<a\s+([^>]*?\s+)?href="#([^"]+)"\s?(.*?)>(.+?)<\/a>/s';
    $matches = [];
    preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);
    $fragments = [];
    foreach ($matches as $match) {
      $fragments[$match[2]] = ['link' => $match[0], 'title' => $match[4]];
    }

    return $fragments;
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
