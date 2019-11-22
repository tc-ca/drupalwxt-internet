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
 *   id = "openplus_node_post_add",
 *   label = @Translation("Post process nodes"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/add-node-post",
 *   }
 * )
 */
class OpenplusAddNodePost extends ResourceBase {

  public function post($vars) {
    //  gid, uuid, label (from migration node), json_file?
    $values = array();

    // base values
    // Replace - with _ for db compatibility
    $uuid = str_replace('-', '_', $vars['uuid']);
    $values['id'] = 'maas__ndp__en__' . $uuid;
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
      'item_selector' => 'rows/',
      'ids' => ['id' => ['type' => 'string']],
      'headers' => [
        'Accept' => 'application/json; charset=utf-8',
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer 8c336cb91315d25ac6a2ff43d2975d1a'
      ],
      'urls' => [
        ConfigUtil::GetHarvesterBaseUrl() . $vars['uuid'] . '/page/en',
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
    ];
    // Process
    $values['process'] = array();
    // Skip any locked nodes
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
    $values['process']['nid'] = [
      'plugin' => 'migration_lookup',
      'migration' => 'maas__nd__en__' . $uuid,
      'source' => 'id',
    ];

    $values['process']['type'] = [
      'plugin' => 'default_value',
      'default_value' => 'page'
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
      'default_value' => 'rich_text'
    ];

    $migration = Migration::create($values);
    $migration->save();

    $response = ['message' => 'Created migration ID: ' . $migration->id() . ' with label: ' . $migration->label()];

    return new ResourceResponse($response);

  }
}
