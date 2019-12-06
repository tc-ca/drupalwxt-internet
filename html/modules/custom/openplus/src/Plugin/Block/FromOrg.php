<?php
  
namespace Drupal\openplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a course variant right side Block.
 *
 * @Block(
 *   id = "op_from_org",
 *   admin_label = @Translation("From: <org> block"),
 *   category = @Translation("Openplus"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */
class FromOrg extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $config = \Drupal::config('openplus.settings');
    $org = $config->get('org_name');
    $build = [];

    // Node context.
    if (is_object($node)) {
      if ($node->hasField('field_show_org')) {
        $value = $node->get('field_show_org')->getValue();
        if ($value[0]['value']) {
          $url = Url::fromRoute('<front>');
          $link = Link::fromTextAndUrl($this->t($org), $url)->toRenderable();
          $build['from_org_block']['#markup'] = '<p class="gc-byline"><strong>' . t('From: ') . render($link) . '</strong></p>'; 
        }
      }
    }

    return $build;

  }

}

