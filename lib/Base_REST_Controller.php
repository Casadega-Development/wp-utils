<?php

namespace CasaDev_WP_Utils;

use CasaDev_WP_Utils\Logging;
use CasaDev_WP_Utils\HttpError;
use CasaDev_WP_Utils\DownstreamServiceError;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Exception;


/**
 * Base REST API Controller for Quantca
 *
 * Provides common functionality for all Quantca REST API controllers including:
 * - Error handling with standardized logging and Sentry integration
 * - Common properties and constructor patterns
 * - Helper methods for consistent API responses
 *
 * @package CasaDev_WP_Utils
 * @subpackage Base_REST_Controller
 * @since 2.0.0
 */
abstract class Base_REST_Controller extends WP_REST_Controller {

  /**
   * The REST API namespace
   * @var string
   */
  public $namespace;

  /**
   * The REST API base route
   * @var string
   */
  public $rest_base;

  /**
   * The resource group for plugin dependency management
   * @var string
   */
  public static $resource_group;

  /**
   * Array of plugin dependencies required for this controller
   * @var array
   */
  public static $plugin_dependencies = [];

  /**
   * Validation mode for this controller
   * @var string 'verbose' | 'obfuscated'
   */
  public $validation_mode = 'verbose';

  /**
   * Constructor - should be called by child classes
   */
  public function __construct() {
    // Child classes should implement their own constructor logic
    // but can call parent::__construct() if needed

    // Any required dependencies should be loaded here
    // and any required classes should be included here
    // and any required constants should be defined here
    // and any required functions should be defined here
    // and any required hooks should be added here
    // and any required filters should be added here
    // and any required actions should be added here
  }

  static function get_name() {
    return get_called_class();
  }

  /**
   * Helper method to handle common error patterns
   *
   * @param WP_REST_Request $request The request object
   * @param callable $callback The function to execute
   * @param string $fallback_error_message The user-friendly error message
   * @param array $param_schema The parameter schema
   * @return WP_REST_Response|WP_Error The response or error object
   */
  protected function handle_request(WP_REST_Request $request, $callback, $fallback_error_message, $param_schema = null) {
    // Get the calling method name dynamically
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $method_name = $backtrace[1]['function'] ?? 'unknown_method';
    $class_name = get_class($this);

    try {
      if ($param_schema) {
        // run validations bucket-by-bucket
        foreach (
          [
            'validate_url_params',
            'validate_query_params',
            'validate_body_params',
          ] as $validator
        ) {

          $result = $this->$validator($request, $param_schema);

          if (is_wp_error($result)) {
            if ($this->validation_mode === 'verbose') {
              $result->add_data(['source' => $validator]);
              return $result;
            } else {
              return $this->error_response('rest_invalid_param', 'bad request', 400);
            }
          }
        }
      }

      return $callback();
    } catch (HttpError $e) {
      do_action('sentry/captureException', $e);
      Logging::print_log("{$class_name}::{$method_name} : error", $e->getMessage());
      return new WP_Error('http_error', $e->getMessage(), ['status' => $e->getStatus()]);
    } catch (DownstreamServiceError $e) {
      do_action('sentry/captureException', $e);
      Logging::print_log("{$class_name}::{$method_name} : error", $e->getMessage());
      return new WP_Error('downstream_service_error', $e->getMessage(), ['status' => $e->getStatus(), ...$e->getResponse()]);
    } catch (Exception $e) {
      do_action('sentry/captureException', $e);
      Logging::print_log("{$class_name}::{$method_name} : error", $e->getMessage());
      return new WP_Error('server_error', $fallback_error_message, ['status' => 500]);
    }
  }

  /**
   * Create a successful REST response
   *
   * @param mixed $data The response data
   * @param int $status_code The HTTP status code (default: 200)
   * @return WP_REST_Response
   */
  protected function success_response($data, $status_code = 200) {
    return new WP_REST_Response($data, $status_code);
  }

  /**
   * Create an error response
   *
   * @param string $code The error code
   * @param string $message The error message
   * @param int $status_code The HTTP status code (default: 400)
   * @param array $additional_data Additional error data
   * @return WP_Error
   */
  protected function error_response($code, $message, $status_code = 400, $additional_data = []) {
    return new WP_Error($code, $message, array_merge(['status' => $status_code], $additional_data));
  }

  protected function validate_url_params(WP_REST_Request $request, RestRouteSchema $schema) {
    if (!isset($schema->url_params)) {
      return true;
    }

    // Logging::print_log('validate_url_params', $schema->url_params);

    foreach ($schema->url_params as $key => $schema_details) {
      $value = $request->get_url_params()[$key];

      if (isset($value)) {
        $checked = rest_validate_value_from_schema($value, $schema_details, $key);
        // Logging::print_log('checked', $checked);
        if (is_wp_error($checked)) {
          // Enhance the error message to specify it's a URL parameter
          $error_data = $checked->get_error_data();
          $message = $checked->get_error_message();

          // Modify the message to indicate it's a URL parameter
          if (strpos($message, 'Missing parameter(s):') !== false) {
            $message = str_replace('Missing parameter(s):', 'Missing URL parameter(s):', $message);
          } elseif (strpos($message, 'Invalid parameter(s):') !== false) {
            $message = str_replace('Invalid parameter(s):', 'Invalid URL parameter(s):', $message);
          } else {
            $message = 'URL parameter error: ' . $message;
          }

          return new WP_Error($checked->get_error_code(), $message, $error_data);
        }

        // Sanitize the validated value
        $sanitized = rest_sanitize_value_from_schema($value, $schema_details, $key);
        // Logging::print_log('sanitized', $sanitized);
        $request->set_param($key, $sanitized);
      } elseif ($schema_details['required']) {
        return new WP_Error('rest_missing_required_param', 'Missing required URL parameter: ' . $key, array('param' => $key));
      }
    }
    return true;
  }

  /**
   * Validate only query-string parameters ( ?context=view ).
   */
  protected function validate_query_params(WP_REST_Request $request, RestRouteSchema $schema) {
    if (!isset($schema->query_params)) {
      return true;
    }

    foreach ($schema->query_params as $key => $schema_details) {
      $value = $request->get_query_params()[$key];

      if (isset($value)) {
        $checked = rest_validate_value_from_schema($value, $schema_details, $key);
        if (is_wp_error($checked)) {
          // Enhance the error message to specify it's a query parameter
          $error_data = $checked->get_error_data();
          $message = $checked->get_error_message();

          // Modify the message to indicate it's a query parameter
          if (strpos($message, 'Missing parameter(s):') !== false) {
            $message = str_replace('Missing parameter(s):', 'Missing query parameter(s):', $message);
          } elseif (strpos($message, 'Invalid parameter(s):') !== false) {
            $message = str_replace('Invalid parameter(s):', 'Invalid query parameter(s):', $message);
          } else {
            $message = 'Query parameter error: ' . $message;
          }

          return new WP_Error($checked->get_error_code(), $message, $error_data);
        }

        // Sanitize the validated value
        $sanitized = rest_sanitize_value_from_schema($value, $schema_details, $key);
        $request->set_param($key, $sanitized);
      } elseif ($schema_details['required']) {
        return new WP_Error('rest_missing_required_param', 'Missing required query parameter: ' . $key, array('param' => $key));
      }
    }
    return true;
  }

  /**
   * Validate JSON / form body parameters.
   */
  protected function validate_body_params(WP_REST_Request $request, RestRouteSchema $schema) {
    if (!isset($schema->body_params)) {
      return true;
    }

    // Prefer JSON, then fallback to x-www-form-urlencoded
    $params = $request->get_json_params();
    if (empty($params)) {
      $params = $request->get_body_params();
    }

    foreach ($schema->body_params as $key => $schema_details) {
      $value = $params[$key];

      if (isset($value)) {
        $checked = rest_validate_value_from_schema($value, $schema_details, $key);
        if (is_wp_error($checked)) {
          // Enhance the error message to specify it's a body parameter
          $error_data = $checked->get_error_data();
          $message = $checked->get_error_message();

          // Modify the message to indicate it's a body parameter
          if (strpos($message, 'Missing parameter(s):') !== false) {
            $message = str_replace('Missing parameter(s):', 'Missing body parameter(s):', $message);
          } elseif (strpos($message, 'Invalid parameter(s):') !== false) {
            $message = str_replace('Invalid parameter(s):', 'Invalid body parameter(s):', $message);
          } else {
            $message = 'Body parameter error: ' . $message;
          }

          return new WP_Error($checked->get_error_code(), $message, $error_data);
        }

        // Sanitize the validated value
        $sanitized = rest_sanitize_value_from_schema($value, $schema_details, $key);
        $request->set_param($key, $sanitized);
      } elseif ($schema_details['required']) {
        return new WP_Error('rest_missing_required_param', 'Missing required body parameter: ' . $key, array('param' => $key));
      }
    }
    return true;
  }

  protected $regex_patterns = [
    'numeric' => '[0-9]+',
    'alphanumeric' => '[a-zA-Z0-9]+',
    'alphanumeric_hyphen' => '[a-zA-Z0-9-]+',
    'alpha' => '[a-zA-Z]+',
    'alpha_hyphen' => '[a-zA-Z-]+',
    'uuid' => '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}',
    'slug' => '[a-z0-9-]+',
    'any' => '[^/]+'
  ];

  /**
   * Generate a path parameter pattern for REST API routes
   *
   * @param string $param_name The name of the parameter (e.g., 'id', 'type')
   * @param string $type The type of parameter validation
   * @return string The path parameter pattern
   */
  protected function path_param($param_name, $type = 'any') {
    $pattern = $this->regex_patterns[$type] ?? $this->regex_patterns['any'];
    return "(?P<{$param_name}>{$pattern})";
  }
}

class RestRouteSchema {
  public $url_params;
  public $query_params;
  public $body_params;

  /**
   * @param array{
   *   url_params:array,
   *   query_params:array,
   *   body_params:array,
   * } $schema The schema for the REST route.
   * @see https://developer.wordpress.org/reference/functions/register_rest_route/
   */
  public function __construct(array $schema) {
    $this->url_params = $schema['url_params'];
    $this->query_params = $schema['query_params'];
    $this->body_params = $schema['body_params'];
  }
}
