<?php
namespace Drupal\openplus_migrate\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\openplus_migrate\Util\ConfigUtil;

/**
 *
 * @RestResource(
 *   id = "openplus_node_post_trans_add",
 *   label = @Translation("Post process node translations"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/add-node-trans-post",
 *   }
 * )
 */
class OpenplusAddNodeTransPost extends ResourceBase {

  public function post($vars) {
    // Replace - with _ for db compatibility
    $uuid = str_replace('-', '_', $vars['uuid']);

    // base values
    $values = array();
    $values['id'] = 'maas__ndp__fr__' . str_replace('-', '_', $vars['uuid']);
    $values['class'] = 'Drupal\migrate\Plugin\Migration';
    $values['migration_group'] = 'maas__group__' . str_replace('-', '_', $vars['uuid']);
    $values['migration_tags'] = null;
    $values['migration_dependencies'] = null;
    $values['label'] = $vars['label'];

    // Source
    $values['source'] = [
      'plugin' => 'url',
      'data_fetcher_plugin' => 'http',
      'data_parser_plugin' => 'json',
      // based on source plugin example: https://www.lullabot.com/articles/pull-content-from-a-remote-drupal-8-site-using-migrate-and-json-api
      'item_selector' => 'rows/',  // added the trailing slash
      'ids' => ['id' => ['type' => 'string']], // our json has ids like 7 and 7_fr so I think this is correct but not sure
      'headers' => [
        'Accept' => 'application/json; charset=utf-8',
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer 8c336cb91315d25ac6a2ff43d2975d1a'
      ],
      'urls' => [
        ConfigUtil::GetHarvesterBaseUrl() . $vars['uuid'] . '/page/fr',
      ],
      'fields' => [
         ['name' => 'id', 'label' => 'ID', 'selector' => 'id'],
         ['name' => 'title', 'label' => 'Title', 'selector' => 'title'],
         ['name' => 'body', 'label' => 'Body', 'selector' => 'body'],
         ['name' => 'language', 'label' => 'Language', 'selector' => 'language'],
      ]
    ];

    // Destination
    $values['destination'] = [
      'plugin' => 'entity:node',
      'translations' => 'true',
    ];

    // Process
    $values['process'] = array();
    // skip any locked nodes
    $values['process']['id'] = [
      [ 
        'plugin' => 'op_migration_lookup',
        'migration' => 'maas__nd__en__' . $uuid,
        'source' => 'id',
      ],
      [ 
        'plugin' => 'skip_on_lock',
        'method' => 'row',
      ],
    ];

    // explode 7_fr into (7, 'fr') theen look up the node for id 7
    $values['process']['nid'] = [
      [
        'plugin' => 'explode',
        'source' => 'id', 
        'delimiter' => '_',
      ],
      [
        'plugin' => 'array_shift',
      ],
      [
        'plugin' => 'migration_lookup',
        'migration' => 'maas__nd__en__' . $uuid,
      ],
    ];

    $values['process']['body/value'] = [
      [
        'plugin'         => 'replace_tables',
        'migration_uuid' => $vars['uuid'],
        'source'         => 'body'
      ],
      [
        'plugin'         => 'replace_links',
        'migration_uuid' => $vars['uuid'],
      ],
      [
        'plugin'         => 'replace_media_images',
        'migration_uuid' => $vars['uuid'],
      ],
      [
        'plugin'         => 'replace_media_files',
        'migration_uuid' => $vars['uuid'],
      ],
    ];

    $values['process']['body/format'] = [
      'plugin' => 'default_value',
      'default_value' => 'rich_text',
    ];

    $values['process']['langcode'] = [
      'plugin' => 'default_value',
      'default_value' => 'fr',
    ];

    $migration = Migration::create($values);
    $migration->save();

    $response = ['message' => 'Created migration ID: ' . $migration->id() . ' with label: ' . $migration->label()];

    return new ResourceResponse($response);

  }
}
