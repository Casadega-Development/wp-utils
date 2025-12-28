<?php

namespace CasaDev_WP_Utils;

/**
 * HttpError class for errors in REST API controllers.
 *
 * @package CasaDev_WP_Utils
 * @subpackage Errors
 * @since 2.1.0
 */
class HttpError extends \Exception {
  /**
   * The status code for the error response
   *
   * @var int
   */
  protected $status_code;

  public function __construct(string $message, int $status_code = 0) {
    parent::__construct($message);
    $this->status_code = $status_code;
  }

  public function getStatus() {
    return $this->status_code;
  }
}
