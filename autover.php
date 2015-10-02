<?php
/**
 * Plugin Name: AutoVer
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

function autover_options() {
	?>
	<div class="wrap">

	<h2>AutoVer</h2>

	<p>
	If you want to use the functionality of this plugin you must add
	<a href="http://codex.wordpress.org/Function_Reference/wp_enqueue_script" target="_blank"><strong>'wp_enqueue_script'</strong></a> and
	<a href="http://codex.wordpress.org/Function_Reference/wp_enqueue_style" target="_blank"><strong>'wp_enqueue_style'</strong></a>.
	</p>

	<p>
	<h3><span style="color:black;font-weight:bold;">Example:</span></h3>

	<h3><span style="color:green;font-weight:bold;">YES</span></h3>
	<p style="background:#eaeaea; padding:5px;" title="CORRECT CODE">
	<?php
	$string = "<?php
	function autover_add_style_and_script() {
		wp_enqueue_style( 'style-name', plugins_url( '/autover/mystyle.css', __FILE__ ) );
		wp_enqueue_script( 'script-name', plugins_url( '/autover/myscript.js', __FILE__ ) );
	}
	add_action( 'wp_enqueue_scripts', 'autover_add_style_and_script' );
	add_action( 'admin_enqueue_scripts', 'autover_add_style_and_script' );
	?>";
	highlight_string( $string ); ?>
	</p>

	<h3><span style="color:red;font-weight:bold;">NO</span></h3>
	<p style="background:#eaeaea; padding:5px;" title="DO NOT USE THIS CODE">
	<?php
	$string = "<?php
	function autover_add_style_and_script() {
	echo \"<link rel='stylesheet' href='\".plugins_url( '/autover/mystyle.js', __FILE__ )
		.\"' type='test/css' media='all' />
	<script src='\".plugins_url( '/autover/mystyle.css', __FILE__ ).\"'></script>
	\";
	}
	add_action( 'wp_head', 'autover_add_style_and_script' );
	add_action( 'admin_head', 'autover_add_style_and_script' );
	?>";
	highlight_string( $string ); ?>
	</p>

	<h3 style="font-weight:normal;">If you want to use <strong>'wp_enqueue_style'</strong> to add your <strong>'style.css'</strong> of your theme.<br />Add the next code to your theme file <strong>'functions.php'</strong> <span style="color:red;font-weight:bold;">and remove your &lt;link&gt; tag</span> from <strong>'header.php'</strong> which refer to your <strong>'style.css'</strong>.</h3>
	<p style="background:#eaeaea; padding:5px;" title="add this code to 'functions.php' file">
	<?php
	$string = "<?php
	function mythemename_style() {
		\$style_url = get_stylesheet_directory_uri() . '/style.css';
		wp_enqueue_style( 'my_style_id', \$style_url, __FILE__ );
	}
	add_action( 'wp_enqueue_scripts', 'mythemename_style' );
	?>";
	highlight_string( $string ); ?>
	</p>

	</p>

	</div><!-- .wrap -->
	<?php
}

add_action( 'admin_menu', 'autover_menu' );
function autover_menu() {
	add_management_page( 'AutoVer - Options', 'AutoVer', 'administrator', __FILE__, 'autover_options' );
}

function autover_remove_query( $src ) {
	$src_query = autover_str_between( '?', '##', $src . '##' );      // Find the query.
	$src_with_no_query = str_replace( $src_query, '', $src );        // Remove query if exist.
	$src_with_no_query = str_replace( '?', '', $src_with_no_query ); // Remove '?' char.

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
