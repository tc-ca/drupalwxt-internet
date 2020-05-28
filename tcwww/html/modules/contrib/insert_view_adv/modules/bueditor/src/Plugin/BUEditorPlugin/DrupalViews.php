<?php

namespace Drupal\insert_view_adv_bueditor\Plugin\BUEditorPlugin;


use Drupal\editor\Entity\Editor;
use Drupal\bueditor\BUEditorPluginBase;
use Drupal\bueditor\Entity\BUEditorEditor;
use Drupal\bueditor\BUEditorToolbarWrapper;

/**
 * Defines BUEditor Embedded Views plugin.
 *
 * @BUEditorPlugin(
 *   id = "drupalviews",
 *   label = "Embedded Views"
 * )
 */
class DrupalViews extends BUEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'drupalviews' => $this->t('Views Embed'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterEditorJS(array &$js, BUEditorEditor $bueditor_editor, Editor $editor = NULL) {
    $toolbar = BUEditorToolbarWrapper::set($js['settings']['toolbar']);
    // Check drupal views button.
    if ($toolbar->has('drupalviews')) {
      $js['libraries'][] = 'insert_view_adv_bueditor/drupalviews';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterToolbarWidget(array &$widget) {
    // Make  drupalviews definition available to toolbar widget
    $widget['libraries'][] = 'insert_view_adv_bueditor/drupalviews';
  }

}
