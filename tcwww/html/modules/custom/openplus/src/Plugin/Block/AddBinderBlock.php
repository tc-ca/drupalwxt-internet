<?php

namespace Drupal\openplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides the add binder block 
 *
 * @Block(
 *   id = "add_binder_block",
 *   admin_label = @Translation("Add binder block"),
 *   category = @Translation("Openplus"),
 * )
 */

class AddBinderBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    
    // Get the current user
    $user = \Drupal::currentUser();
    
    $url = Url::fromUserInput('/node/add/binder');
    $link_options = [
      'attributes' => [
        'class' => [
          'button',
          'button--primary',
          'button--action',
        ],
      ],
      'query' => [
        'destination' => '/admin/binder-admin',
      ],
    ];
    
    $url->setOptions($link_options);
    $link = Link::fromTextAndUrl($this->t('Add binder'), $url)->toRenderable();
    $build['#markup'] = '<div>' . render($link) . '</div>'; 
    
    return $build;
    
  }
  
}

