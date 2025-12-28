<?php

namespace CasaDev_WP_Utils;

/**
 * Class Logging
 *
 * Helplers for logging
 *
 * @package CasaDev_WP_Utils
 * @subpackage Logging
 * @since 1.0.0
 */
class Logging {
  /**
   * Log something to the PHP error log
   * @param  string $label
   * @param  mixed array|object|string $data  the data to log to the php error console
   */
  public static function print_log($label, $data) {
    $print = print_r($data, true);
    error_log("[$label]: \n$print");
  }

  /**
   * var_dump something to the PHP error log
   * @param  string $label
   * @param  mixed array|object|string $data  the data to log to the php error console
   */
  public static function dump_log($label, $data) {
    ob_start();
    var_dump($data);
    $buffer = ob_get_clean();

    self::print_log($label, $buffer);
  }

  public static function print_screen($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
  }

  /**
   * Logs messages/variables/data to browser console from within php.
   * This was breaking in the docker container, but keeping it here anyway...
   *
   * @param $name: message to be shown for optional data/vars
   * @param $data: variable (scalar/mixed) arrays/objects, etc to be logged
   *
   * @return void
   */
  public static function js_console($name, $data = NULL) {
    if (! $name) return false;

    $isevaled = false;
    $type = ($data || gettype($data)) ? 'Type: ' . gettype($data) : '';
    $data = json_encode($data);

    $js = <<<JSCODE
    \n<script>
    console.log('$name');
    console.log('------------------------------------------');
    console.log('$type');
    console.log($data);
    console.log('\\n');
    </script>
    JSCODE;

    echo $js;
  }
}
