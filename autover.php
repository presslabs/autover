<?php
/**
 * Plugin Name: Autover
 * Plugin URI: http://wordpress.org/extend/plugins/autover/
 * Description: Automatically version your CSS and JS files.
 * Author: Presslabs
 * Version: 1.4
 * Author URI: http://www.presslabs.com/
 */

register_activation_hook( __FILE__, 'autover_activate' );
function autover_activate() {
	autover_delete_old_options();
}

register_deactivation_hook( __FILE__, 'autover_deactivate' );
function autover_deactivate() {}

function autover_delete_old_options() {
	delete_option( 'autover_dev_mode' );
	delete_option( 'autover_is_working' );

	delete_option( 'autover_versioned_css_files' );
	delete_option( 'autover_not_versioned_css_files' );
	delete_option( 'autover_not_correct_css_files' );

	delete_option( 'autover_versioned_js_files' );
	delete_option( 'autover_not_versioned_js_files' );
	delete_option( 'autover_not_correct_js_files' );
}

function autover_str_between( $start, $end, $content ) {
	$r = explode( $start, $content );

	if ( isset( $r[1] ) ) {
		$r = explode( $end, $r[1] );
		return $r[0];
	}

	return '';
}

function autover_remove_query( $src ) {
	$src_query = autover_str_between( '?', '##', $src . '##' );      // Find the query
	$src_with_no_query = str_replace( $src_query, '', $src );        // Remove query if exist
	$src_with_no_query = str_replace( '?', '', $src_with_no_query ); // Remove '?' char

	return $src_with_no_query;
}

add_filter( 'style_loader_src', 'autover_version_filter', 10, 1 );
add_filter( 'script_loader_src', 'autover_version_filter', 10, 1 );
function autover_version_filter( $src ) {
	$src_with_no_query = autover_remove_query( $src );

	$termination = strtolower( substr( $src_with_no_query, -3 ) );
	( $termination === '.js' ) ? $filetype = 'js' : ( ( $termination === 'css' ) ? $filetype = 'css' : null );

	if ( null === $filetype ) {
		return $src;
	}
	if ( defined( 'AUTOVER_DISABLE_' . strtoupper( $filetype ) ) ) {
		return $src;
	}

	$src_path = parse_url( $src, PHP_URL_PATH );
	$filename = rtrim( ABSPATH, '/' )  . urldecode( $src_path );

	if ( ! is_file( $filename ) ) {
		return $src;
	}
	$timestamp_version = filemtime( $filename );
	if ( ! $timestamp_version ) {
		$timestamp_version = filemtime( utf8_decode( $filename ) );
	}
	if ( ! $timestamp_version ) {
		return $src;
	}

	$src_with_new_version = $src_with_no_query . '?ver=' . $timestamp_version;

	return $src_with_new_version;
}
