<?php

namespace CasaDev_WP_Utils;

use CasaDev_WP_Utils\Logging;

/**
 * Requests Class.
 *
 * Helplers for remote requests
 *
 */
class Requests {
  /**
   * Checks a wp_remote_get request for errors and optionally sends an email on error
   * @param  array|WP_Error $request 	  either a reponse from the wp_remote_request call
   * 																	  or a WP_Error object
   * @param string $error_title         used as subject for the email
   * @param bool $send_email            whether to send an email notification on request error
   * @param string $email email         address to notify (defaults to site admin email)
   * @return bool                       false if $request had an error, true otherwise
   */
  public static function error_check($request, $error_title, $send_email = true, $email = null) {
    $notif_email = $email ?? get_option('admin_email');

    if ( is_wp_error( $request ) ) {
      $error_print = print_r(
        $request->get_error_message(),
        true
      );

      if ( $send_email ) {
        wp_mail($notif_email, $error_title, $error_print);
      }

      Logging::print_log($error_title, $error_print);

      return false;
    }

    if ( $request['response']['code'] < 200 || $request['response']['code'] >= 300 ) {
      $error_print = print_r(
        $request['response'],
        true
      );

      $request_print = print_r($request, true);

      if ( $send_email ) {
        wp_mail($notif_email, $error_title, "$error_print\n\n$request_print");
      }

      Logging::print_log($error_title, $error_print);

      return false;
    }

	  return true;
  }
}
