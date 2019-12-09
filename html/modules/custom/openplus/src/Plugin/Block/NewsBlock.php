<?php
  
namespace Drupal\openplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Provides home page news Block.
 *
 * @Block(
 *   id = "op_news_block",
 *   admin_label = @Translation("News block"),
 *   category = @Translation("Openplus"),
 * )
 */
class NewsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['source_url_en'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source url (en)'),
      '#default_value' => !isset($config['source_url_en']) ? '' : $config['source_url_en'],
      '#required' => true,
    ];

    $form['source_url_fr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source url (fr)'),
      '#default_value' => !isset($config['source_url_fr']) ? '' : $config['source_url_fr'],
      '#required' => true,
    ];

    $form['max_items'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Items to display'),
      '#default_value' => !isset($config['max_items']) ? 3 : $config['max_items'],
      '#attributes' => array(
        'type' => 'number',
      ),
      '#required' => true,
      '#maxlength' => 3,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['source_url_en'] = $values['source_url_en'];
    $this->configuration['source_url_fr'] = $values['source_url_fr'];
    $this->configuration['max_items'] = $values['max_items'];
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $uri = $this->configuration['source_url_' . $language];
    $max_items = $this->configuration['max_items'];

    $headers = [
      'Accept' => 'application/json; charset=utf-8',
      'Content-Type' => 'application/json',
    ];

    $request = \Drupal::httpClient()
      ->get($uri, array(
       'headers' => $headers,
    ));

    $response = json_decode($request->getBody());
    $data = $response->feed->entry;

    $output = array();
    for ($i = 0; $i < $max_items; $i++) {
      $item_date = new DrupalDateTime($data[$i]->publishedDate, 'UTC');
      $formatted_date = \Drupal::service('date.formatter')->format($item_date->getTimestamp(), 'short_time');
      $output[] = Markup::create('<a href="' . $data[$i]->link . '">' . $data[$i]->title . '</a><br /> <small>[' . $formatted_date . ']</small>');
      //$url = Url::fromUri('http://www.example.com/');
      //$link = Link::fromTextAndUrl($data[$i]->title, $url);
      //$output[] = $link;
    }

    $build = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => $output, 
      '#attributes' => ['class' => 'home-news-feed'],
      '#allowed_tags' => ['a', 'small', 'br'],
    ];

    return $build;

  }

}

