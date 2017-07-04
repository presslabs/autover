<?php
/**
 * Plugin Name: Autover
 * Plugin URI: https://wordpress.org/plugins/autover/
 * Description: Automatically version your CSS and JS files enqueued through the Wordpress API
 * Author: Presslabs
 * Version: 1.5
 * Author URI: https://www.presslabs.com/
 */



add_filter( 'style_loader_src', 'autover_version_filter' );
add_filter( 'script_loader_src', 'autover_version_filter' );
function autover_version_filter( $src ) {
	$url_parts = wp_parse_url( $src );

	$extension = pathinfo( $url_parts['path'], PATHINFO_EXTENSION );
	if ( ! $extension || ! in_array( $extension, [ 'css', 'js' ] ) ) {
		return $src;
	}

	if ( defined( 'AUTOVER_DISABLE_' . strtoupper( $extension ) ) ) {
		return $src;
	}

	$file_path = rtrim( ABSPATH, '/' ) . urldecode( $url_parts['path'] );
	if ( ! is_file( $file_path ) ) {
		return $src;
	}

	$timestamp_version = filemtime( $file_path ) ?: filemtime( utf8_decode( $file_path ) );
	if ( ! $timestamp_version ) {
		return $src;
	}

	if ( ! isset( $url_parts['query'] ) ) {
		$url_parts['query'] = '';
	}

	$query = [];
	parse_str( $url_parts['query'], $query );
	unset( $query['v'] );
	unset( $query['ver'] );
	$query['ver']       = "$timestamp_version";
	$url_parts['query'] = build_query( $query );

	return autover_build_url( $url_parts );
}


function autover_build_url( array $parts ) {
	return ( isset( $parts['scheme'] ) ? "{$parts['scheme']}:" : '' ) .
		   ( ( isset( $parts['user'] ) || isset( $parts['host'] ) ) ? '//' : '' ) .
		   ( isset( $parts['user'] ) ? "{$parts['user']}" : '' ) .
		   ( isset( $parts['pass'] ) ? ":{$parts['pass']}" : '' ) .
		   ( isset( $parts['user'] ) ? '@' : '' ) .
		   ( isset( $parts['host'] ) ? "{$parts['host']}" : '' ) .
		   ( isset( $parts['port'] ) ? ":{$parts['port']}" : '' ) .
		   ( isset( $parts['path'] ) ? "{$parts['path']}" : '' ) .
		   ( isset( $parts['query'] ) ? "?{$parts['query']}" : '' ) .
		   ( isset( $parts['fragment'] ) ? "#{$parts['fragment']}" : '' );
}
