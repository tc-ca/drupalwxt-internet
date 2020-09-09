<?php

namespace Drupal\openplus\Plugin\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\book\BookManagerInterface;


/**
 * Bulk add pages to a book.
 *
 * @Action(
 *   id = "add_to_book",
 *   label = @Translation("Add to book"),
 *   type = "node",
 *   confirm = FALSE,
 *   requirements = {
 *     "_permission" = "administer nodes",
 *   },
 * )
 */

class AddToBook extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

  //use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {

/*
    $form['example'] = [
      '#title' => $this->t('Example'),
      '#type' => 'text',
      '#default_value' => isset($values['example']) ? $values['example'] : '',
    ];
*/

    return $form;
  }

  /**
   * Configuration form builder.
   *
   * If this method has implementation, the action is
   * considered to be configurable.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $options = [];
    $books = \Drupal::service('book.manager')->getAllBooks();
    foreach ($books as $bid => $book) {
      $options[$bid] = $book['title'];
    }

    $form['bid'] = [
      '#title' => $this->t('Select a target book'),
      '#type' => 'select',
      '#options' => $options, 
      '#required' => TRUE,
      '#default_value' => isset($values['bid']) ? $values['bid'] : NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $config = $this->configuration;

    if (!isset($this->context['sandbox']['counter'])) {
      $this->context['sandbox']['counter'] = 0;
    }

    $bid = $config['bid'];
    // $link = \Drupal::service('book.manager')->loadBookLink($bid, FALSE); 

    $link = [
      'nid' => $entity->id(),
      'bid' => $bid,
      'pid' => $bid,
      'weight' => 1,
      'depth' => 2,
    ];
 
    \Drupal::service('book.manager')->saveBookLink($link, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {

    if ($object->getEntityType() === 'node') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }

    return TRUE;
  }

}
