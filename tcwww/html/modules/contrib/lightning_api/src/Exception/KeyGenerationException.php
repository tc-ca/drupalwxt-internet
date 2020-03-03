<?php

namespace Drupal\lightning_api\Exception;

/**
 * Exception thrown when an oAuth key cannot be generated.
 */
class KeyGenerationException extends \RuntimeException {

  /**
   * KeyGenerationException constructor.
   *
   * @param string $message
   *   (optional) The exception message.
   * @param int $code
   *   (optional) The error code.
   * @param \Exception $previous
   *   (optional) The previous exception, if any.
   */
  public function __construct($message = "", $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = openssl_error_string() ?: 'An internal error occurred';
    }
    parent::__construct($message, $code, $previous);
  }

}
