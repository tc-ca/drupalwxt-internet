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
 * Provides the book title Block.
 *
 * @Block(
 *   id = "op_book_title",
 *   admin_label = @Translation("Book title"),
 *   category = @Translation("Openplus"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */
class BookTitle extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $build = [];

    // Node context.
    if (is_object($node)) {
      // Do not show title on book page, just the children
      if (isset($node->book) && !empty($node->book) && $node->book['bid'] != $node->id()) {
        $book =  \Drupal::entityTypeManager()->getStorage('node')->load($node->book['bid']);
        if ($book->hasTranslation($langcode)) {
          $book = $book->getTranslation($langcode);
        }
        if ($book) {
          $build['book_title']['#markup'] = '<div class="h4">' . $book->getTitle() . '</div>';
        }
      }
    }

    return $build;
  }

}
