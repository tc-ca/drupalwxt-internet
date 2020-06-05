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
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Process the node body and replace media images.
 *
 * @MigrateProcessPlugin(
 *   id = "replace_media_images",
 *   handle_multiples = TRUE
 * )
* @codingStandardsIgnoreStart
 *
 * To do a link replacement use the following:
 * @code
 * body/value:
 *   plugin: replace_media_images
 *   source: text
 *   migration_uuid: 41ba1708-839f-4fa8-9d8f-8ba452b98534
 * @endcode
 *
 * @codingStandardsIgnoreEnd
 */

class ReplaceMediaImages extends ProcessPluginBase {
  /**
  * {@inheritdoc}
  */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $mig_uuid = $this->configuration['migration_uuid'];

    $matches = [];
    // match the entire image tag
    //preg_match_all('/<img .*src="(\[NODEJSHARVEST_IMG:.*?\]).*\/>/', $value, $matches, PREG_SET_ORDER);
    preg_match_all('/(\[NJS_IMG:.*?\])/', $value, $matches, PREG_SET_ORDER);
    /*
     * Returns an array of all matches in the format:
     * array(
     *   0 => array(
     *     0 => "[NJS_IMG:file_id]"
     *     1 => "[NJS_IMG:file_id]"
     */

    if (!empty($matches)) {
      foreach ($matches as $match) {
        list($placeholder, $harvest_mig_uuid, $media_id) = explode(':', str_replace(array('[', ']'),'' , $match[1]));
        // See if we are running in post process ALL mode
        if ($mig_uuid == 'all') {
          $mig_uuid = $harvest_mig_uuid;
        }

        $db_table = "migrate_map_maas__mdf__en__" . str_replace('-', '_', $mig_uuid);

        // lookup mid in migrate map
        $connection = \Drupal::database();
        $query = $connection->query("SELECT destid1 FROM {$db_table} where sourceid1 = '$media_id'");
        $results = $query->fetchAll();

        if (!empty($results)) {
          $media = Media::load($results[0]->destid1);
          if ($media) {

            $image = $media->get('image')->getValue();

            $uri = ConfigUtil::GetHarvesterBaseUrl() . 'get-harvest-item/' .  $mig_uuid . '/component_media/' . $media_id;
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

            // get the image width in order to find an appropriate image style
            $width = isset($response->rows->width) ? $response->rows->width : 0;
            if ($width < 100) {
              $style = NULL;
            }
            elseif ($width >= 100 && $width < 200) {
              $style = 'one_twelth';
            }
            elseif ($width >= 200 && $width < 300) {
              $style = 'one_sixth';
            }
            elseif ($width >= 300 && $width < 600) {
              $style = 'one_quarter';
            }
            elseif ($width >= 600 && $width < 900) {
              $style = 'one_half';
            }
            elseif ($width >= 900 && $width < 1200) {
              $style = 'three_quarter';
            }
            else {
              $style = 'full_width';
            }

            $find = $match[0];
            // The entire replacement string.
            $replacement = '<drupal-entity ';
            $replacement .= 'alt="' . $image[0]['alt'] . '" ';
            $replacement .= ' data-embed-button="media_browser" data-entity-embed-display="media_image" data-entity-embed-display-settings="{&quot;image_style&quot;:&quot;' . $style . '&quot;,&quot;image_link&quot;:&quot;&quot;}" data-entity-type="media" ';
            $replacement .=  'data-entity-uuid="' . $media->uuid() . '"  title="' . $media->label() . '">';
            $replacement .=  '</drupal-entity>';
            // @TODO get actual ALT text and ensure title value is correct
            // Do the actual string replacement.
            $value = str_replace($find, $replacement, $value);
          }
          else {
            $migrate_executable->saveMessage('Could load media image: ' . $results[0]->destid1);
          }
        }
        else {
          $migrate_executable->saveMessage('Could not find media image in db map: ' . $mig_uuid . ':' . $media_id);
        }
      }
    }

    return $value;
  }

}
