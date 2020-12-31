<?php

namespace CasaDev_WP_Utils;

/**
 * ACF_Helpers class.
 *
 * Helplers for retrieving ACF data
 *
 */
class ACF_Helpers {
  /**
   * Returns all acf data for an array of posts
   *
   * @param  array $posts array of post objects or post ids
   * @return array
   */
  public static function get_acf_for_posts( $posts, $is_single = false ) {
  	if ( $is_single ) {
  		$post = get_post( $posts );
  		$fields = (array) $post;
  		$fields['permalink'] = get_post_permalink( $post->ID );

  		if ( function_exists( 'get_fields' ) ) {
  			$acf = get_fields( $post->ID );
  		} else {
  			trigger_error( 'casadev_get_acf_for_posts called but ACF is not installed' );
  			$acf = [];
  		}

  		return array_merge( $fields, $acf );
  	}

  	return array_map(
  		function( $post ) {
  			if ( is_object( $post ) ) {
  				$fields = (array) $post;
  				$fields['permalink'] = get_post_permalink( $post->ID );
  			} else {
  				$post   = get_post( $post );
  				$fields = (array) $post;
  				$fields['permalink'] = get_post_permalink( $post->ID );
  			}

  			if ( function_exists( 'get_fields' ) ) {
  				$acf = get_fields( $post->ID );
  			} else {
  				trigger_error( 'casadev_get_acf_for_posts called but ACF is not installed' );
  				$acf = [];
  			}

  			return array_merge( $fields, $acf );
  		},
  		$posts
  	);
  }
}
