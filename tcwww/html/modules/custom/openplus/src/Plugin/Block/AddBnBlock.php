<?php

namespace Drupal\openplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides the bn add block 
 *
 * @Block(
 *   id = "add_bn_block",
 *   admin_label = @Translation("Add briefing note block"),
 *   category = @Translation("Openplus"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */


class AddBnBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    
    // Get the current user
    $user = \Drupal::currentUser();
    $node = $this->getContextValue('node');
    
    $url = Url::fromUserInput('/node/add/briefing-note');
    $link_options = [
      'attributes' => [
        'class' => [
          'btn',
          'btn-primary',
          'btn-raised',
        ],
      ],
      'query' => [
        'destination' => '/admin/bn-admin/' . $node->id() . '/manage',
        'edit[field_binder][widget][0][target_id]' => $node->id(),
      ],
    ];
    
    $url->setOptions($link_options);
    $link = Link::fromTextAndUrl($this->t('Add binder'), $url)->toRenderable();
    $build['#markup'] = '<div>' . render($link) . '</div>'; 
    
    return $build;
    
  }
  
}

