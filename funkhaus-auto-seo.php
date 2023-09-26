<?php
/**
 * Plugin Name: Auto SEO
 * Description: Automatically implement SEO best practices using the power of AI.
 * Version: 2.11
 * Author: Funkhaus
 * Plugin URI: https://github.com/funkhaus/auto-seo
 * Author URI: http://funkhaus.us
 * Text Domain: funkhaus-auto-seo
 *
 * @package funkhaus-auto-seo
 */

define( 'FH_AS_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Import required files
 */
require_once FH_AS_PATH . 'lib/load.php';

require_once FH_AS_PATH . 'includes/utilities.php';
require_once FH_AS_PATH . 'includes/settings.php';
require_once FH_AS_PATH . 'includes/azure.php';
require_once FH_AS_PATH . 'includes/convert-image.php';
require_once FH_AS_PATH . 'includes/rename-image.php';
require_once FH_AS_PATH . 'includes/focal-point.php';
require_once FH_AS_PATH . 'includes/color-detect.php';
require_once FH_AS_PATH . 'includes/blurhash.php';
require_once FH_AS_PATH . 'includes/api.php';


if ( ! fh_seo_is_gd_enabled() ) {
	add_action( 'admin_notices', 'fh_seo_admin_notices' );
} else {
	/**
	 * Main WP hooks to fire our logic on.
	 */
	add_filter( 'wp_handle_upload', 'fh_seo_convert', 10, 2 );
	add_action( 'add_attachment', 'fh_seo_rename_and_discribe', 10, 1 );
}

/**
 * Plugin activated, setup default options
 */
function fh_seo_plugin_activated() {
	$defaults = array(
		'api_key' => '',
	);
	add_option( 'fh_seo_settings', $defaults );
}
register_activation_hook( __FILE__, 'fh_seo_plugin_activated' );

/**
 * Attempt to convert an image on upload. Currently only works for PNGs
 *
 * @see https://developer.wordpress.org/reference/hooks/wp_handle_upload/
 *
 * @param array       $upload      Reference to a single element of `$_FILES`.
 *                               Call the function once for each uploaded file.
 *                               See _wp_handle_upload() for accepted values.
 * @param array|false $context Optional. An associative array of names => values
 *                               to override default variables. Default false.
 *                               See _wp_handle_upload() for accepted values.
 */
function fh_seo_convert( $upload, $context ) {
	// Attempt to convert image
	$converted = fh_seo_convert_to_jpg(
		$upload['file'],
		$upload['url'],
		$upload['type']
	);
	if ( ! is_wp_error( $converted ) ) {
		$upload = $converted;
	}

	return $upload;
}

/**
 * Rename file and add meta data via Azure.
 *
 * @param int $attachment_id Attachment ID.
 */
function fh_seo_rename_and_discribe( $attachment_id ) {
	$output = array(
		'id' => $attachment_id,
	);

	// Only try this on formats Azure and ColorThief support
	$type = get_post_mime_type( $attachment_id );
	switch ( $type ) {
		case 'image/jpeg':
		case 'image/png':
		case 'image/gif':
			break;

		default:
			return false;
	}

	// Set color first as we don't need Azure for this
	$output['set_color'] = fh_seo_attachment_set_colors( $attachment_id );

	// Set blurhash
	$output['set_blurhash'] = fh_seo_attachment_set_bluehash( $attachment_id );

	// Abort now if no API key
	$options = get_option( 'fh_seo_settings' );
	if ( empty( $options['api_key'] ) ) {
		return false;
	}

	// Name and caption attachment
	$output['set_metadata'] = fh_seo_attachment_set_name_and_caption( $attachment_id );

	// Set focal point
	$output['set_focal_point'] = fh_seo_attachment_set_focal_point( $attachment_id );

	// Save a timestamp so we know image was processed already
	update_post_meta( $attachment_id, 'fh_seo_timestamp', date( 'Y-m-d H:i:s' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

	return $output;
}

/**
 * Enqueue any CSS & JS scripts
 *
 * @param string $hook_suffix The current admin page.
 */
function fh_seo_admin_scripts( $hook_suffix ) {
	// Only load scripts on the settings page
	if ( $hook_suffix == 'settings_page_auto-seo' ) {
		wp_enqueue_script(
			'fh_seo_main',
			plugins_url( 'js/main.js', __FILE__ ),
			null,
			time(),
			true
		);
		wp_enqueue_style(
			'fh_seo_main',
			plugins_url( 'css/main.css', __FILE__ ),
			null,
			time()
		);

		// Import some JS vars from PHP
		$js_vars = array(
			'api_url' => site_url( '/wp-json/auto-seo' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		);

		wp_add_inline_script(
			'fh_seo_main',
			'var fh_seo_vars = ' . wp_json_encode( $js_vars ),
			'before'
		);
	}

	// Load focushaus scripts
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'focushaus', plugins_url( 'js/focushaus.js', __FILE__ ), array( 'jquery' ), '2.01', true );
	wp_enqueue_style( 'focushaus', plugins_url( 'css/focushaus.css', __FILE__ ), null, '2.01' );
}
add_action( 'admin_enqueue_scripts', 'fh_seo_admin_scripts' );


/**
 * Register custom API endpoints
 */
function fh_seo_add_api_routes() {
	// Use this to trigger a deploy at Netlify
	register_rest_route(
		'auto-seo',
		'/generate',
		array(
			array(
				'methods'             => 'POST',
				'callback'            => 'fh_seo_generate',
				'args'                => array(
					'id'     => array(),
					'offset' => array(),
				),
				'permission_callback' => 'fh_seo_permissions',
			),
		)
	);
}
add_action( 'rest_api_init', 'fh_seo_add_api_routes' );

/**
 * Check if PHP GD module enabled.
 *
 * @return bool Returns true if enabled.
 */
function fh_seo_is_gd_enabled() {
	return function_exists( 'imagecreatefrompng' );
}

/**
 * Adds admin notice.
 */
function fh_seo_admin_notices() {
	printf(
		'<div class="notice notice-warning is-dismissible"><p>Warning: %s</p></div>',
		__( '<b>Auto SEO</b> plugin requires GD php extension. Please install/enable it. For more information, please check <a href="https://www.php.net/manual/en/image.installation.php" target="_blank">documentation.</a>' )
	);
}
