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
    $fragments = SELF::getOriginalFragmentsV2($entity);

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
          if (isset($fragments[$match[3]])) {
            // we have a bad fragment - it exists as a same page anchor in the old revision and the link title matches
            $updated = TRUE;
            $find = $match[0];
            $pattern2 = '/<a [^>]+>(.+)*<\/a>/s'; // find link text and put back latest
            $matches2 = [];
            preg_match_all($pattern2, $fragments[$match[3]]['link'], $matches2, PREG_SET_ORDER);
            if ($matches2) {
              $full_link = $matches2[0][0];
              $link_title = $matches2[0][1];
              $newer_title = $match[5]; 
              // in case link title was updated on node later on and link not fixed at that time
              $replacement = str_replace($link_title, $newer_title, $full_link);
              $body = str_replace($find, $replacement, $body);
            }
          }
        }

      }

      if ($updated) {
        $entity->set('body', ['value' => $body, 'format' => 'rich_text']);
        $entity->save();
      }
    }

    return $this->t('Updated links with fragments.');
  }

  public function getOriginalFragments($entity) {
    $fragments = [];

    $migration_groups = MigrationGroup::loadMultiple();
    $migration = $entity->get('field_migration')->getValue();
    if (empty($migration)) {
      \Drupal::logger('openplus')->notice('Migration not tagged: ' . $entity->id());
      return $fragments;
    }
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($migration[0]['target_id']);

    $mig_uuid = NULL;
    foreach ($migration_groups as $migration) {
      if ($term->label() == $migration->label()) {
        $mig_uuid = str_replace('_', '-', str_replace('maas__group__', '', $migration->id()));
        break;
      }
    }

    if (empty($mig_uuid)) {
      \Drupal::logger('openplus')->notice('Could not find entity in migration: ' . $entity->id());
      return $fragments;
    }

    $migration_table = 'migrate_map_maas__nd__en__' . str_replace('-', '_', $mig_uuid);
    $database = \Drupal::database();
    $query = "select sourceid1 from {$migration_table} where destid1 = " . $entity->id();
    $query = $database->query($query);
    $result = $query->fetchAll();
    $id = isset($result[0]->sourceid1) ? $result[0]->sourceid1 : NULL;
   
    if (empty($id)) {
      \Drupal::logger('openplus')->notice('Could not find source id: ' . $entity->id());
      return $fragments;
    }

    // get the links from the harvester
    $source = $entity->get('field_source_url')->getValue();

    $uri = ConfigUtil::GetHarvesterBaseUrl() . 'get-harvest-item/' . $mig_uuid . '/component_page/' . $id;
    $headers = [
      'Accept' => 'application/json; charset=utf-8',
      'Content-Type' => 'application/json',
    ];
    $request = \Drupal::httpClient()
      ->get($uri, array(
       'headers' => $headers,
       'auth' => [ConfigUtil::GetHarvesterUser(), ConfigUtil::GetHarvesterPass()],
    ));
    $item = json_decode($request->getBody());
    $body= $item->rows->body;

    $pattern = '/<a\s+([^>]*?\s+)?href="#([^"]+)"\s?(.*?)>(.+?)<\/a>/s';
    $matches = [];
    preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
      $fragments[$match[2]] = ['link' => $match[0], 'title' => $match[4], 'href' => $match[2]];
    }

    return $fragments;
  }

  public function getOriginalFragmentsV2($entity) {
    $fragments = [];
    $uri = 'http://dv16.openplus.ca/api/v1/get-body/' . $entity->uuid();
    $headers = [
      'Accept' => 'application/json; charset=utf-8',
      'Content-Type' => 'application/json',
    ];
    $request = \Drupal::httpClient()
      ->get($uri, array(
       'headers' => $headers,
    ));
    $item = json_decode($request->getBody());
    $body = $item['0']->body;

    $pattern = '/<a\s+([^>]*?\s+)?href="#([^"]+)"\s?(.*?)>(.+?)<\/a>/s';
    $matches = [];
    preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
      $fragments[$match[2]] = ['link' => $match[0], 'title' => $match[4], 'href' => $match[2]];
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
