<?php

// Exit if accessed directly.

defined('ABSPATH') || exit;

namespace CasaDev_WP_Utils;

/**
 * DownstreamServiceError class for downstream service errors
 * in REST API controllers.
 *
 * @package CasaDev_WP_Utils
 * @subpackage Errors
 * @since 2.1.0
 */
class DownstreamServiceError extends \Exception {
  /**
   * The response body from the downstream service.
   *
   * @var array
   */
  protected $response;

  /**
   * The status code from the downstream service.
   *
   * @var int
   */
  protected $status_code;

  public function __construct(string $message, int $status_code = 0, $response_body = []) {
    parent::__construct($message);
    $this->response = $response_body;
    $this->status_code = $status_code;
  }

  public function getResponse() {
    return $this->response;
  }

  public function getStatus() {
    return $this->status_code;
  }
}
