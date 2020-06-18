<?php

namespace Drupal\views_url_alias_node\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class ViewsURLAliasNodeAdminForm.
 */
class ViewsURLAliasNodeAdminForm extends ConfirmFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_u_r_l_alias_node_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to rebuild the Views URL alias node table?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('views_ui.settings_basic');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This should only be needed if URL aliases have been updated outside the node or URL alias edit form.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Rebuild table');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    views_url_alias_node_rebuild();
    $form_state->setRedirectUrl(new Url('views_ui.settings_basic'));
  }
}
