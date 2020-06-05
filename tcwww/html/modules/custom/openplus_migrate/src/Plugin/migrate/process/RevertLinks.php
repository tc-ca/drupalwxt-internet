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
use Drupal\Core\Url;

/**
 * Process the node body and revert unreplaceable link tokens back to their original href. This is
 * used at the very end of an migration to put back links to pages that were not part of the migration.
 *
 * @MigrateProcessPlugin(
 *   id = "revert_links",
 *   handle_multiples = TRUE
 * )
* @codingStandardsIgnoreStart
 *
 * To do a link replacement use the following:
 * @code
 * body/value:
 *   plugin: revert_links
 *   source: text
 *   mode: test 
 * @endcode
 *
 * @codingStandardsIgnoreEnd
 */

class RevertLinks extends ProcessPluginBase {

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
    $mode = $this->configuration['mode'];

    $matches = [];
    //preg_match_all('/<a href="(\[NODEJSHARVEST_LINK:.*?\])/', $value, $matches, PREG_SET_ORDER);
    preg_match_all('/(\[NJS_LINK:.*?\])/', $value, $matches, PREG_SET_ORDER);
    /*
     * Returns an array of all matches in the format:
     * array(
     *   0 => array(
     *     0 => "<a href="[NODEJSHARVEST_LINK:a1b11aad-9ca2-4ecf-8b19-29c4d2bb5e40]"
     *     1 => "[NODEJSHARVEST_LINK:a1b11aad-9ca2-4ecf-8b19-29c4d2bb5e40]"
     */
    $nid = $row->getSourceProperty('nid');
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
        //\Drupal::logger('openplus')->notice('Processing page: ' . $url);

        // only do replacement if we found a link
        if (!empty($link_info)) {
          $find = $match[0];
          $link_url = $link_info->link;
          $link_text = $link_info->metadata;
          $fragment = isset($link_info->fragment) && !empty($link_info->fragment) ? '#' . $link_info->fragment : NULL;
          $attributes = isset($link_info->attributes) && !empty($link_info->attributes) ? json_decode($link_info->attributes, true) : [];

          $replacement = '<a href="' . $link_url . '"';
          // add any attributes / classes etc.
          foreach ($attributes as $attrKey => $attrValue) {
            $replacement .= " $attrKey=\"$attrValue\"";
          }
          // close off the link
          $replacement .= '>' . $link_text . '</a>';
          // Do the actual string replacement if we are in 'makeitso' mode... as opposed to just test mode
          if ($mode == 'makeitso') {
            $value = str_replace($find, $replacement, $value);
          }
          $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid], ['absolute' => FALSE]);
          $url = $url->toString();
          if (strpos($link_url, 'www.tc.gc.ca') !== FALSE) {
            $migrate_executable->saveMessage('Reverting|' . $link_url . '|on node|' . $url);
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
