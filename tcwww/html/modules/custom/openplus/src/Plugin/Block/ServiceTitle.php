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
 *   id = "op_service_title",
 *   admin_label = @Translation("Service title"),
 *   category = @Translation("Openplus"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */
class ServiceTitle extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $build = [];

    // Node context.
    if (is_object($node)) {
      if ($node->hasField('field_service_block') && !$node->get('field_service_block')->isEmpty()) {
        if ($node->hasField('field_hide_subtitle') && !$node->get('field_hide_subtitle')->isEmpty()) {
          $hide_subtitle = $node->get('field_hide_subtitle')->getValue();
          if ($hide_subtitle[0]['value'] !=1) {
            $serviceblock = $node->get('field_service_block')->getValue();
            $entityqueue = \Drupal::entityTypeManager()->getStorage('entity_subqueue')->load($serviceblock[0]['target_id']);
            $config_name = 'entityqueue.entity_queue.' . $entityqueue->id();
            $translatedLabel = \Drupal::config($config_name)->get('label');
            if ($entityqueue) {
              $build['service_title']['#markup'] = '<div class="h4">' . $translatedLabel . '</div>';
            }
          }
        }
      }
    }

    return $build;
  }

}
