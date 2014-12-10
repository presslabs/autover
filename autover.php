<?php
/**
 * Plugin Name: AutoVer
 * Plugin URI: http://wordpress.org/extend/plugins/autover/
 * Description: Automatically version your CSS and JS files.
 * Author: PressLabs
 * Version: 1.3
 * Author URI: http://presslabs.com/
 */

require_once( 'lib/simplehtmldom.php' );

function autover_activate() {
	autover_delete_options();

	add_option( 'autover_dev_mode', array( '1', '1' ) );
	add_option( 'autover_is_working', true );

	add_option( 'autover_versioned_css_files', array() );
	add_option( 'autover_not_versioned_css_files', array() );
	add_option( 'autover_not_correct_css_files', array() );

	add_option( 'autover_versioned_js_files', array() );
	add_option( 'autover_not_versioned_js_files', array() );
	add_option( 'autover_not_correct_js_files', array() );
}
register_activation_hook( __FILE__, 'autover_activate' );

function autover_deactivate() {
	autover_delete_options();
}
register_deactivation_hook( __FILE__, 'autover_deactivate' );

function autover_delete_options() {
	delete_option( 'autover_dev_mode' );
	delete_option( 'autover_is_working' );

	delete_option( 'autover_versioned_css_files' );
	delete_option( 'autover_not_versioned_css_files' );
	delete_option( 'autover_not_correct_css_files' );

	delete_option( 'autover_versioned_js_files' );
	delete_option( 'autover_not_versioned_js_files' );
	delete_option( 'autover_not_correct_js_files' );
}

function autover_settings_link( $links ) {
	// Add settings link on plugin page
	$plugin = plugin_basename( __FILE__ );
	$settings_link = '<a href="tools.php?page=' . $plugin . '">' . __( 'Settings' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'autover_settings_link' );

function autover_str_between( $start, $end, $content ) {
	// Return the string between 'start' and 'end' from 'conent'
	$r = explode( $start, $content );

	if ( isset( $r[1] ) ) {
		$r = explode( $end, $r[1] );
		return $r[0];
	}
	return '';
}

function autover_string2link( $string ) {
	return '<a href="' . $string . '">' . $string . '</a>';
}

function autover_remove_query( $src ) {
	// Remove the query from src
	return preg_replace( '/\?.*/', '', $src );
}

function autover_add_incorrect_style_and_script() {
	//  Just for debug
	echo '
<link rel="stylesheet" type="text/css" href="' . plugins_url( '/autover.css', __FILE__ ) . '" />
<script src="' . plugins_url( '/autover.js', __FILE__ ) . '"></script>
';
}
//add_action('wp_head', 'autover_add_incorrect_style_and_script');
//add_action('admin_head', 'autover_add_incorrect_style_and_script');

function autover_version_filter( $src ) {
	// Return the  file with the new version
	$out_src = $src;

	// Remove the old version if exist.
	$src_with_no_query = autover_remove_query( $out_src );

	// Get the filetype of the file (JS or CSS)
	( strtolower( substr( $src_with_no_query, -2 ) ) == 'js' ) ? $filetype = 'js' : null ;
	( strtolower( substr( $src_with_no_query, -3 ) ) == 'css' ) ? $filetype = 'css' : null ;

	$active = false;
	$autover_dev_mode = get_option( 'autover_dev_mode', array( '', '' ) );
	if ( ( ( $autover_dev_mode[0] <> '' ) && ( $filetype == 'css' ) ) || ( ( $autover_dev_mode[1] <> '' ) && ( $filetype == 'js' ) ) ) {
		$active = true;
	}
	// get versioned/not_versioned files
	$autover_versioned_files = get_option( 'autover_versioned_' . $filetype . '_files', array() );
	$autover_not_versioned_files = get_option( 'autover_not_versioned_' . $filetype . '_files', array() );

	// Parse the url of the input file
	$src_parsed = parse_url( $out_src );
	$src_path   = $src_parsed['path'];

	$filename = $_SERVER['DOCUMENT_ROOT'] . $src_path;
	if ( is_file( $filename ) ) {
		$timestamp_version = filemtime( $filename ); // Extract the modification time of the input file

		if ( null == $timestamp_version ) {
			$timestamp_version = filemtime( utf8_decode( $filename ) );
		}
	} else {
		// Add not versioned files
		array_push( $autover_not_versioned_files, $out_src );
		$autover_not_versioned_files = array_unique( $autover_not_versioned_files );
		sort( $autover_not_versioned_files );
		update_option( 'autover_not_versioned_' . $filetype . '_files', $autover_not_versioned_files );

		return $out_src;
	}

	// If the file is not on the server then return the input file.
	if ( ( $timestamp_version == '' ) || ( null == $timestamp_version ) ) {
		// Add not versioned files
		array_push( $autover_not_versioned_files, $out_src );
		$autover_not_versioned_files = array_unique( $autover_not_versioned_files );
		sort( $autover_not_versioned_files );
		update_option( 'autover_not_versioned_' . $filetype . '_files', $autover_not_versioned_files );

		return $out_src;
	}

	// Create the new version
	$src_with_new_version = $src_with_no_query . '?ver=' . $timestamp_version;

	// Add versioned files
	array_push( $autover_versioned_files, $src_with_no_query );
	$autover_versioned_files = array_unique( $autover_versioned_files );
	sort( $autover_versioned_files );
	update_option( 'autover_versioned_' . $filetype . '_files', $autover_versioned_files );

	if ( $active ) { $out_src = $src_with_new_version; }

	return $out_src;
}

// Activate/deactivate the filter if the options are set
//$autover_dev_mode = get_option('autover_dev_mode', array('','') );

//if ( $autover_dev_mode[0] <> '' )
	add_filter( 'style_loader_src', 'autover_version_filter', 10, 1 );

//if ( $autover_dev_mode[1] <> '' )
	add_filter( 'script_loader_src', 'autover_version_filter', 10, 1 );

function autover_update_options() {
	$autover_dev_mode_value = array( '', '' );

	$autover_is_working = false;
	if ( isset( $_POST['autover_dev_mode_style'] ) ) {
		$autover_dev_mode_value[0] = '1';
		$autover_is_working = true;
	} else {
		delete_option( 'autover_versioned_css_files' );
		delete_option( 'autover_not_versioned_css_files' );
		delete_option( 'autover_not_correct_css_files' );
	}

	if ( isset( $_POST['autover_dev_mode_script'] ) ) {
		$autover_dev_mode_value[1] = '1';
		$autover_is_working = true;
	} else {
		delete_option( 'autover_versioned_js_files' );
		delete_option( 'autover_not_versioned_js_files' );
		delete_option( 'autover_not_correct_js_files' );
	}

	update_option( 'autover_dev_mode', $autover_dev_mode_value );
	update_option( 'autover_is_working', $autover_is_working );

	if ( $autover_is_working ) { ?>
		<div id="message" class="updated fade">
			<p><strong>Saved options!</strong></p>
		</div>
	<?php } else {
		delete_option( 'autover_dev_mode' );
		delete_option( 'autover_versioned_css_files' );
		delete_option( 'autover_not_versioned_css_files' );
		delete_option( 'autover_not_correct_css_files' );

		delete_option( 'autover_versioned_js_files' );
		delete_option( 'autover_not_versioned_js_files' );
		delete_option( 'autover_not_correct_js_files' );
		}
}

function autover_options() {
	if ( isset( $_POST['submit_settings'] ) ) {
		autover_update_options();
	}
	isset($_GET['tab']) ? $tab = $_GET['tab'] : $tab = 'important';
	?>
	<div class="wrap">

	<div id="icon-tools" class="icon32">&nbsp;</div>
	<h2 class="nav-tab-wrapper">
	<a class="nav-tab<?php if ( 'important' == $tab ) { echo ' nav-tab-active'; } ?>" href="tools.php?page=autover/autover.php&tab=important"><span style="color:red;">IMPORTANT!</span></a>
	<a class="nav-tab<?php if ( 'settings' == $tab ) { echo ' nav-tab-active'; } ?>" href="tools.php?page=autover/autover.php&tab=settings">Settings</a>
	<a class="nav-tab<?php if ( 'lists' == $tab ) { echo ' nav-tab-active'; } ?>" href="tools.php?page=autover/autover.php&tab=lists">Lists</a>
	</h2>

	<?php if ( $tab == 'important' ) { ?>

	<?php
	$autover_is_working = get_option( 'autover_is_working', false );
		if ( ! $autover_is_working ) { ?>
			<div id="message" class="error fade">
			<p>
				<strong>
					<span style="color:brown;">
						This plugin is currently not used!
					</span>
				</strong>
			</p>
			</div>
	<?php
	}
?>

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
	wp_enqueue_style('autover', plugins_url('/autover/mystyle.css',__FILLE_));
	wp_enqueue_script('autover', plugins_url('/autover/myscript.js',__FILLE_));
}
add_action('wp_enqueue_scripts','autover_add_style_and_script');
add_action('admin_enqueue_scripts','autover_add_style_and_script');
?>";
highlight_string( $string ); ?>
</p>

<!--img src="<?php echo plugins_url( '/img/wp-enqueue.png', __FILE__ ); ?>" alt="wp-enqueue" title="CORRECT CODE"-->

<h3><span style="color:red;font-weight:bold;">NO</span></h3>
<p style="background:#eaeaea; padding:5px;" title="DO NOT USE THIS CODE">
<?php
$string = "<?php
function autover_add_style_and_script() {
echo \"<link rel='stylesheet' href='\".plugins_url('/autover/mystyle.js',__FILLE_)
	.\"' type='test/css' media='all' />
<script src='\".plugins_url('/autover/mystyle.css',__FILLE_).\"'></script>
\";
}
add_action('wp_head','autover_add_style_and_script');
add_action('admin_head','autover_add_style_and_script');
?>";
highlight_string( $string ); ?>
</p>

<!--img src="<?php echo plugins_url( '/img/wp-head.png', __FILE__ ); ?>" alt="wp-head" title="DO NOT USE THIS CODE"-->

<h3 style="font-weight:normal;">If you want to use <strong>'wp_enqueue_style'</strong> to add your <strong>'style.css'</strong> of your theme.<br />Add the next code to your theme file <strong>'functions.php'</strong> <span style="color:red;font-weight:bold;">and remove your &lt;link&gt; tag</span> from <strong>'header.php'</strong> which refer to your <strong>'style.css'</strong>.</h3>
<p style="background:#eaeaea; padding:5px;" title="add this code to 'functions.php' file">
<?php
$string = "<?php
function mythemename_style() {
	\$style_url = get_stylesheet_directory_uri() . '/style.css';

	wp_enqueue_style('my_style_id', \$style_url, __FILE__);
}
add_action('wp_enqueue_scripts','mythemename_style');
?>";
highlight_string( $string ); ?>
</p>

<!--img src="<?php echo plugins_url( '/img/my-theme-name.png', __FILE__ ); ?>" alt="mythemename" title="add this code to 'functions.php' file"-->
</p>

<?php } ?>

	<?php if ( $tab == 'settings' ) { ?>

	<?php
		$autover_is_working = get_option( 'autover_is_working', false );
		if ( ! $autover_is_working ) { ?>
			<div id="message" class="error fade">
			<p>
				<strong>
					<span style="color:brown;">
						This plugin is currently not used!
					</span>
				</strong>
			</p>
			</div>
		<?php
		}
		$autover_dev_mode = get_option( 'autover_dev_mode' );
		$dev_mode_checked = array( '', '' );
		for ( $k = 0; $k < 2; $k++ ) {
			if ( '1' == $autover_dev_mode[ $k ] ) {
				$dev_mode_checked[ $k ] = ' checked="checked"';
			}
		}
?>

<form method="post">
<table class="form-table">
<tbody>
	<tr valign="top">
	<th scope="row">
		<label for="autover_dev_mode">Developer mode</label>
	</th>
	<td>
		<fieldset>

		<legend class="screen-reader-text"><span>Developer mode</span></legend>
		<label for="autover_dev_mode_style">
			<input name="autover_dev_mode_style" id="autover_dev_mode_style" value="<?php echo $autover_dev_mode[0]; ?>" type="checkbox"<?php echo $dev_mode_checked[0]; ?>>
			<span>CSS files</span>
		</label><br />

		<label for="autover_dev_mode_script">
			<input name="autover_dev_mode_script" id="autover_dev_mode_script" value="<?php echo $autover_dev_mode[1]; ?>" type="checkbox"<?php echo $dev_mode_checked[1]; ?>>
			<span>JavaScript files</span>
		</label><br />

		<p class="description">The file type you want to rewrite the version.</p>

		</fieldset>
	</td>
	</tr>

	<tr valign="top">
	<td>
	</td>
	</tr>
</tbody>
</table>

<p class="submit">
<input type="submit" class="button button-primary" name="submit_settings" value="Save Changes">
</p>

</form>

<?php } ?>

	<?php if ( $tab == 'lists' ) { ?>

	<?php
		$autover_is_working = get_option( 'autover_is_working', false );
		if ( ! $autover_is_working ) { ?>
			<div id="message" class="error fade">
			<p>
				<strong>
					<span style="color:brown;">
						This plugin is currently not used!
					</span>
				</strong>
			</p>
			</div>
		<?php
	}
?>

	<?php if ( isset( $_POST['reset_lists'] ) ) { autover_reset_lists(); } ?>
	<?php if ( isset( $_POST['refresh_lists'] ) ) { autover_refresh_lists(); } ?>

<form method="post">
<p class="submit">
  Remove all data from the file lists (CSS and JS): <input type="submit" class="button button-primary" name="reset_lists" value="Reset"><br />
  Scan the Homepage and detect the files added wrong (CSS and JS files): <input type="submit" class="button" name="refresh_lists" value="Refresh"><br />
</p>
</form>

	<?php
		if ( $autover_is_working ) {
			autover_show_not_correct_files();
			autover_show_versioned_files();
			autover_show_not_versioned_files();
		}
	?>

	<?php } ?>
	</div><!-- .wrap -->
	<?php
}

function autover_reset_lists() {
	// Remove all data from the file lists (CSS and JS)
	update_option( 'autover_versioned_css_files', array() );
	update_option( 'autover_versioned_js_files', array() );
	update_option( 'autover_not_versioned_css_files', array() );
	update_option( 'autover_not_versioned_js_files', array() );
	update_option( 'autover_not_correct_css_files', array() );
	update_option( 'autover_not_correct_js_files', array() );
}

function autover_refresh_lists() {
	// Scan the Homepage and detect the files added wrong (CSS and JS files)
	autover_scan_not_correct_files();
}

function autover_show_versioned_files() {
	$autover_versioned_css_files = get_option( 'autover_versioned_css_files', array() );
	if ( ! empty( $autover_versioned_css_files ) ) { ?>
		<fieldset>
		<pre>
		<legend><strong>Versioned CSS files:</strong></legend>
	<?php
		$k = 1;
		$empty_list = true;
		sort( $autover_versioned_css_files );
		foreach ( $autover_versioned_css_files as $versioned_css_file ) {
			echo $k . ') ' . autover_string2link( $versioned_css_file ) . "\n";
			$empty_list = false;
			$k++;
	} ?>
		</pre>
		</fieldset>
	<?php if ( ! $empty_list ) { echo '<hr>'; }
	}

	$autover_versioned_js_files = get_option( 'autover_versioned_js_files', array() );
	if ( ! empty( $autover_versioned_js_files ) ) { ?>
		<fieldset>
		<pre>
		<legend><strong>Versioned JS files:</strong></legend>
	<?php
		$k = 1;
		$empty_list = true;
		sort( $autover_versioned_js_files );
		foreach ( $autover_versioned_js_files as $versioned_js_file ) {
			echo $k . ') ' . autover_string2link( $versioned_js_file ) . "\n";
			$empty_list = false;
			$k++;
		} ?>
		</pre>
		</fieldset>
	<?php if ( ! $empty_list ) { echo '<hr>'; }
	}
}

function autover_show_not_versioned_files() {
	$message = false;
	$autover_not_versioned_css_files = get_option( 'autover_not_versioned_css_files', array() );
	if ( ! empty( $autover_not_versioned_css_files ) ) {
		$message = true;
		?>
		<p>From various reasons, the next files are not versioned!</p>
		<fieldset>
		<pre>
		<legend><strong>Not versioned CSS files:</strong></legend>
	<?php
		$k = 1;
		$empty_list = true;

		sort( $autover_not_versioned_css_files );
		foreach ( $autover_not_versioned_css_files as $not_versioned_css_file ) {
			echo $k . ') ' . autover_string2link( $not_versioned_css_file ) . "\n";
			$empty_list = false;
			$k++;
		} ?>
		</pre>
		</fieldset>
	<?php if ( ! $empty_list ) { echo '<hr>'; }
	}

	$autover_not_versioned_js_files = get_option( 'autover_not_versioned_js_files', array() );
	if ( ! empty( $autover_not_versioned_js_files ) ) {
		if ( ! $message ) { ?>
			<p>For various reason, the next files are not versioned!</p>
		<?php } ?>
		<fieldset>
		<pre>
		<legend><strong>Not versioned JS files:</strong></legend>
	<?php
		$k = 1;
		$empty_list = true;

		sort( $autover_not_versioned_js_files );
		foreach ( $autover_not_versioned_js_files as $not_versioned_js_file ) {
			echo $k . ') ' . autover_string2link( $not_versioned_js_file ) . "\n";
			$empty_list = false;
			$k++;
		} ?>
		</pre>
		</fieldset>
		<?php if ( ! $empty_list ) { echo '<hr>'; }
	}
}

function autover_scan_not_correct_files() {
	$autover_versioned_css_files     = get_option( 'autover_versioned_css_files', array() );
	$autover_versioned_js_files      = get_option( 'autover_versioned_js_files', array() );
	$autover_not_versioned_css_files = get_option( 'autover_not_versioned_css_files', array() );
	$autover_not_versioned_js_files  = get_option( 'autover_not_versioned_js_files', array() );

	// Create DOM from URL or file
	$html = file_get_html( get_home_url() ); //$_SERVER['HTTP_REFERER'] );

	// Find all external style sheet
	$links = array();
	foreach ( $html->find( 'link' ) as $link_element ) {
		$src = autover_remove_query( $link_element->href );
		if ( '.css' == substr( $src, -4 ) ) {
			array_push( $links, $src );
		}
	}
	$autover_css_files = array_merge( $autover_versioned_css_files, $autover_not_versioned_css_files );
	$out_intersect     = array_intersect( $autover_css_files, $links );
	$out_links         = array_unique( array_diff( $links, $out_intersect ) );

	// Find all external JS scripts
	$scripts = array();
	foreach ( $html->find( 'script' ) as $script_element ) {
		$src = autover_remove_query( $script_element->src );
		array_push( $scripts, $src );
	}
	$autover_js_files = array_merge( $autover_versioned_js_files, $autover_not_versioned_js_files );
	$out_intersect    = array_intersect( $autover_js_files, $scripts );
	$out_scripts      = array_unique( array_diff( $scripts, $out_intersect ) );

	sort( $out_links );   //print_r($out_links);
	sort( $out_scripts ); //print_r($out_scripts);

	update_option( 'autover_not_correct_css_files', $out_links );
	update_option( 'autover_not_correct_js_files', $out_scripts );
}

function autover_show_not_correct_files() {
	$out_links   = get_option( 'autover_not_correct_css_files', null );
	$out_scripts = get_option( 'autover_not_correct_js_files', null );
	?>
	<fieldset>
	<pre>
	<?php if ( ! empty( $out_links ) || ! empty( $out_scripts ) ) { ?>
	<legend><strong>Add the next files with correct method:</strong></legend>
	<?php
		$k = 1;
		$empty_list = true;

		sort( $out_links );
		foreach ( $out_links as $link ) {
			echo $k . ') ' . autover_string2link( $link ) . "\n";
			$empty_list = false;
			$k++;
		}
		sort( $out_scripts );
		foreach ( $out_scripts as $script ) {
			echo $k . ') ' . autover_string2link( $script ) . "\n";
			$empty_list = false;
			$k++;
		}
	}
	?>
	</pre>
	</fieldset>

	<?php if ( ! $empty_list ) { echo '<hr>'; }
}

function autover_menu() {
	add_management_page(
		'AutoVer - Options', //'custom menu title',
		'AutoVer', //'custom menu',
		'administrator', //'add_users',
		__FILE__, //$menu_slug,
		'autover_options'
	);
}
add_action( 'admin_menu', 'autover_menu' );
