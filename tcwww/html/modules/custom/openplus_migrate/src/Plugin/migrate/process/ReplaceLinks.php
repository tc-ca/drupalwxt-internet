<?php

namespace Drupal\openplus_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\openplus_migrate\Util\ConfigUtil;

/**
 * Process the node body and replace link UUID's with linkit formatted links.
 *
 * @MigrateProcessPlugin(
 *   id = "replace_links",
 *   handle_multiples = TRUE
 * )
* @codingStandardsIgnoreStart
 *
 * To do a link replacement use the following:
 * @code
 * body/value:
 *   plugin: replace_links
 *   source: text
 *   migration_uuid: 41ba1708-839f-4fa8-9d8f-8ba452b98534
 * @endcode
 *
 * @codingStandardsIgnoreEnd
 */

class ReplaceLinks extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['migration_uuid'])) {
      throw new \InvalidArgumentException('The "migration uuid" must be provided.');
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // get the UUID from config to call the correct harvest DB/endpoint
    $mig_uuid = $this->configuration['migration_uuid'];

    $matches = [];
    preg_match_all('/(\[NJS_LINK:.*?\])/', $value, $matches, PREG_SET_ORDER);
    /*
     * Returns an array of all matches in the format:
     * array(
     *   0 => array(
     *     0 => "<a href="[NJS_LINK:migration_uuid:link_uuid]"
     *     1 => "[NJS_LINK:migration_uuid:link_uuid]"
     */

    if (!empty($matches)) {
      foreach ($matches as $match) {
        list($placeholder, $harvest_mig_uuid, $link_uuid) = explode(':', str_replace(array('[', ']'),'' , $match[1]));

        // See if we are running in post process ALL mode
        if ($mig_uuid == 'all') {
          $mig_uuid = $harvest_mig_uuid;
        } 

        // get source_url from harvester EP using UUID
        $uri = ConfigUtil::GetHarvesterBaseUrl() . 'get-harvest-item/' .  $mig_uuid . '/component_links/' . $link_uuid;
        $headers = [
          'Accept' => 'application/json; charset=utf-8',
          'Content-Type' => 'application/json',
        ];
        $request = \Drupal::httpClient()
          ->get($uri, array(
            'headers' => $headers,
            'auth' => [ConfigUtil::GetHarvesterUser(), ConfigUtil::GetHarvesterPass()],
          ));
        $response = json_decode($request->getBody());

        // only do replacement if we found a link
        if (isset($response->rows->link)) {
          $source_url = $response->rows->link;
          $link_text = isset($response->rows->metadata) ? $response->rows->metadata : 'link text';
          $fragment = isset($response->rows->fragment) && !empty($response->rows->fragment) ? '#' . $response->rows->fragment : NULL;
          $attributes = isset($response->rows->attributes) && !empty($response->rows->attributes) ? json_decode($response->rows->attributes, true) : [];

          // see if we have the node migrated by looking for its source url
          $query = \Drupal::entityQuery('node');
          $query->condition('field_source_url', $source_url, '=');
          $results = $query->execute();

          if (!empty($results)) {
            $node = Node::load(array_pop($results));
            //\Drupal::logger('openplus')->notice('Found a link from source node: ' . $row->getSourceProperty('nid') . ' to ' . $node->id());

            $find = $match[0];
            // The entire replacement string.
            $replacement = '<a data-entity-substitution="canonical"';
            $replacement .= ' data-entity-type="node"';
            $replacement .= ' data-entity-uuid="' . $node->uuid() . '"';
            foreach ($attributes as $attrKey => $attrValue) {
              $replacement .= " $attrKey=\"$attrValue\"";
            }
            $replacement .= ' href="/node/' . $node->id() . $fragment . '">' . $link_text . '</a>';
            // Do the actual string replacement.
            $value = str_replace($find, $replacement, $value);
          }
          else {
            // @TODO flag an error that no node was found for the link
          }
        }
        else {
          // @TODO flag an error that the link was not found in the harvest
        }
      }
    }

    return $value;
  }

}
