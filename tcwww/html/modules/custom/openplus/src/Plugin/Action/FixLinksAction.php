<?php

namespace Drupal\openplus\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * @Action(
 *   id = "fix_links_action",
 *   label = @Translation("Fix links"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class FixLinksAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /*
     * All config resides in $this->configuration.
     * Passed view rows will be available in $this->context.
     * Data about the view used to select results and optionally
     * the batch context are available in $this->context or externally
     * through the public getContext() method.
     * The entire ViewExecutable object  with selected result
     * rows is available in $this->view or externally through
     * the public getView() method.
     */

    //$this->messenger()->addMessage($entity->label() . ' - ' . $entity->language()->getId() . ' - ' . $entity->id());
    //return sprintf('Example action (configuration: %s)', print_r($this->configuration, TRUE));
    $body = $entity->get('body')->value;

    $pattern = '/<a\s+([^>]*?\s+)?href="([^"]+)"\s?(.*?)>(.+?)<\/a>/s';
    $matches = [];
    preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);

    if ($matches) {
      foreach ($matches as $match) {
        $fragment = '';
        $find = $match[0];
        $attr = [];
        if (!empty($match[1])) {
          $attr[] = $match[1];
        }
        if (!empty($match[3])) {
          $attr[] = $match[3];
        }
  
        $url = $match[2];
        $link_text = $match[4];
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
          $parsed['host'] = 'www.tc.gc.ca';
        }
        if (isset($parsed['fragment'])) {
          $fragment = $parsed['fragment'];
          unset($parsed['fragment']);
        }
        if (isset($parsed['scheme'])) {
          unset($parsed['scheme']);
        }
        $lookup = strtolower(openplus_build_url($parsed));
        $query = \Drupal::entityQuery('node')
          ->condition('field_source_url.0.uri', '%' . $lookup . '%', 'LIKE');
        $nids = $query->execute();
        if (!empty($nids)) {
          $node = Node::load(array_pop($nids));
          // The entire replacement string.
          $replacement = '<a data-entity-substitution="canonical"';
          $replacement .= ' data-entity-type="node"';
          $replacement .= ' data-entity-uuid="' . $node->uuid() . '"';
          if (!empty($attr)) {
            $replacement .= ' ' . implode(' ', $attr);
          }
              
          if (!empty($fragment)) {
            $replacement .= ' href="/node/' . $node->id() . '#' . $fragment . '">' . $link_text . '</a>';
          }
          else {
            $replacement .= ' href="/node/' . $node->id() . '">' . $link_text . '</a>';
          }
  
          // Do the actual string replacement.
          $body = str_replace($find, $replacement, $body);
        }
      }
      
       $entity->set('body', ['value' => $body, 'format' => 'rich_text']);
       $entity->save();
    }

    return $this->t('Updated links.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
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
    return $form;
  }

  /**
   * Submit handler for the action configuration form.
   *
   * If not implemented, the cleaned form values will be
   * passed direclty to the action $configuration parameter.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // This is not required here, when this method is not defined,
    // form values are assigned to the action configuration by default.
    // This function is a must only when user input processing is needed.
    //$this->configuration['example_config_setting'] = $form_state->getValue('example_config_setting');
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

    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }

}
