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
 *   id = "openplus_migrate_add",
 *   label = @Translation("Migration ADD"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/add-migration",
 *   }
 * )
 */
class OpenplusAddNode extends ResourceBase {

  public function post($vars) {
    // base values
    $values = array();
    $uuid = str_replace('-', '_', $vars['uuid']); // Replace - with _ for db compatibility
    $id = [
      'maas',
      'nd',
      'en',
      $uuid,
    ];
    $values['id'] = implode('__', $id);
    $values['class'] = 'Drupal\migrate\Plugin\Migration';
    $values['migration_group'] = 'maas__group__' . $uuid;
    $values['migration_tags'] = null;
    $values['migration_dependencies'] = null;
    $values['label'] = $vars['label'];

    // @TODO use https://gccloud.ca/api/v1/get-migration-node/72452dc7-2a3d-480e-a3bf-c7eec5048d94?_format=json to get migration config

    // Source
    $values['source'] = [
      'plugin' => 'url',
      'data_fetcher_plugin' => 'http',
      'data_parser_plugin' => 'json',
      'item_selector' => 'rows/',  // added the trailing slash
      'ids' => ['id' => ['type' => 'string']], // our json has ids like 7 and 7_fr so I think this is correct but not sure
      'headers' => [
        'Accept' => 'application/json; charset=utf-8',
        'Content-Type' => 'application/json',
        //'Authorization' => 'Bearer 8c336cb91315d25ac6a2ff43d2975d1a'
      ],
      'urls' => [
        ConfigUtil::GetHarvesterBaseUrl() . $vars['uuid'] . '/page/en',
      ],
      'fields' => [
        ['name' => 'id', 'label' => 'ID', 'selector' => 'id'],
        ['name' => 'title', 'label' => 'Title', 'selector' => 'title'],
        ['name' => 'body', 'label' => 'Body', 'selector' => 'body'],
        ['name' => 'language', 'label' => 'Language', 'selector' => 'language'],
        ['name' => 'website', 'label' => 'Website', 'selector' => 'website'],
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
        'migration' => 'maas__nd__en__' . str_replace('-', '_', $vars['uuid']),
        'source' => 'id',
      ],
      [
        'plugin' => 'skip_on_lock',
        'method' => 'row',
      ],
    ];
    $values['process']['type'] = [
      'plugin' => 'default_value',
      'default_value' => 'page'
    ];
    $values['process']['moderation_state'] = [
      'plugin' => 'default_value',
      'default_value' => 'published',
    ];
    $values['process']['uid'] = [
      'plugin' => 'default_value',
      'default_value' => 1,
    ];

    $values['process']['title'] = 'title';
    $values['process']['body/value'] = 'body';
    $values['process']['body/format'] = [
      'plugin' => 'default_value',
      'default_value' => 'rich_text'
    ];
    $values['process']['langcode'] = [
      'plugin' => 'default_value',
      'default_value' => 'en'
    ];
    $values['process']['field_source_url'] = 'website';

    $migration = Migration::create($values);
    $migration->save();

    $response = ['message' => 'Created migration ID: ' . $migration->id() . ' with label: ' . $migration->label()];

    return new ResourceResponse($response);
  }
}
