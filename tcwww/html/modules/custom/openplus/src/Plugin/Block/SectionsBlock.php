<?php
  
namespace Drupal\openplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the title for a service with a service block (entityqueue).
 *
 * @Block(
 *   id = "op_sections_block",
 *   admin_label = @Translation("Sections block (services)"),
 *   category = @Translation("Openplus"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */
class SectionsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $build = [];

    // Node context.
    if (is_object($node)) {
      if ($node->hasField('field_service_block') && !$node->get('field_service_block')->isEmpty()) {
        $serviceblock = $node->get('field_service_block')->getValue();
        $entityqueue = \Drupal::entityTypeManager()->getStorage('entity_subqueue')->load($serviceblock[0]['target_id']);
        if ($entityqueue) {
          $items = $entityqueue->get('items')->getValue();
          $list = [];
          foreach ($items as $item) {
            $target_node = node_load($item['target_id']);
            $link = $target_node->toLink()->toRenderable();
            $list_item = [
              'data' => $target_node->toLink()->toRenderable(),
            ];
            if ($target_node->id() == $node->id()) {
              $list_item['#wrapper_attributes']['class'] = ['active'];
            }
            $list[] = $list_item;
          }



          $build['sections'] = [
            '#theme' => 'item_list',
            '#list_type' => 'ul',
            '#items' => $list,
            '#attributes' => ['class' => 'gc-navseq'],
            //'#wrapper_attributes' => ['class' => 'container'],
          ];
          //$build['sections'] = views_embed_view('section_block_queue', 'block_1', $serviceblock[0]['target_id']);
        }
      }
    }

    return $build;
  }

}
