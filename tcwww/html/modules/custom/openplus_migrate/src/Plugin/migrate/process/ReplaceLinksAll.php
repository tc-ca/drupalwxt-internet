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
 * Process the node body and replace link UUID's if possible and put back originalk link if not 
 *  (a) uses all nodes as source 
 *  (b) replace link tokens - just to be sure
 *  (c) reverts unreplaceable link tokens back to their original.
 *
 * @MigrateProcessPlugin(
 *   id = "replace_links_all",
 *   handle_multiples = TRUE
 * )
* @codingStandardsIgnoreStart
 *
 * To do a link replacement use the following:
 * @code
 * body/value:
 *   plugin: replace_links_all
 *   source: text
 * @endcode
 *
 * @codingStandardsIgnoreEnd
 */

class ReplaceLinksAll extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $matches = [];
    preg_match_all('/(\[NJS_LINK:.*?\])/', $value, $matches, PREG_SET_ORDER);
    /*
     * Returns an array of all matches in the format:
     * array(
     *   0 => array(
     *     0 => "<a href="[NODEJSHARVEST_LINK:a1b11aad-9ca2-4ecf-8b19-29c4d2bb5e40]"
     *     1 => "[NODEJSHARVEST_LINK:a1b11aad-9ca2-4ecf-8b19-29c4d2bb5e40]"
     */

    $source_nid = $row->getSourceProperty('nid');
    //\Drupal::logger('openplus')->notice('Processing node: ' . $source_nid);
    if (!empty($matches)) {
      foreach ($matches as $match) {

        list($placeholder, $mig_uuid, $link_uuid) = explode(':', str_replace(array('[', ']'),'' , $match[1]));

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
        $link_info = $response->rows;

        //\Drupal::logger('openplus')->notice('Link info: ' . json_encode($link_info));
        // only do replacement if we found a link
        if (!empty($link_info)) {
          $link_url = $link_info->link;
          $link_text = $link_info->metadata;
          $fragment = isset($link_info->fragment) && !empty($link_info->fragment) ? '#' . $link_info->fragment : NULL;
          //\Drupal::logger('openplus')->notice('Link url: ' . $link_url);
          $attributes = isset($link_info->attributes) && !empty($link_info->attributes) ? json_decode($link_info->attributes, true) : [];

          // see if we have the node migrated
          $query = \Drupal::entityQuery('node');
          $query->condition('field_source_url', $link_url, '=');
          $results = $query->execute();

          $find = $match[0];
          $replacement = '<a';
          if (!empty($results)) {
            $node = Node::load(array_pop($results));

            // The entire replacement string.
            $replacement .= ' data-entity-substitution="canonical"';
            $replacement .= ' data-entity-type="node"';
            $replacement .= ' data-entity-uuid="' . $node->uuid() . '"';
            $replacement .= ' href="/node/' . $node->id() . $fragment . '"';
            foreach ($attributes as $attrKey => $attrValue) {
              $replacement .= " $attrKey=\"$attrValue\"";
            }
            $replacement .= '>' . $link_text . '</a>';
            // Do the actual string replacement.
            $value = str_replace($find, $replacement, $value);
          }
        }
        else {
          // Likely log an error here that we found a token that was not in harvester DB - which should not happen since harvester creates the tokens
        }
      }
    }

    return $value;
  }

}
