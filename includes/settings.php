<?php
/**
 * Register the new top level settings page
 *
 * @package funkhaus-auto-seo
 */

/**
 * Register a new settings page under Settings sub-menu
 */
function fh_seo_register_options_page() {
	add_options_page(
		'Auto SEO',
		'Auto SEO',
		'manage_options',
		'auto-seo',
		'fh_seo_options_page'
	);
}
add_action( 'admin_menu', 'fh_seo_register_options_page' );

/**
 * Register the new settings fields on the new Admin page
 */
function fh_seo_settings_init() {
	register_setting( 'fh_seo_plugin', 'fh_seo_settings' );

	add_settings_section(
		'fh_seo_plugin_section',
		'Settings',
		'fh_seo_settings_section_render',
		'fh_seo_plugin'
	);

	add_settings_field(
		'api_key',
		'Plugin API Key',
		'fh_seo_api_key_render',
		'fh_seo_plugin',
		'fh_seo_plugin_section'
	);
}
add_action( 'admin_init', 'fh_seo_settings_init' );

/**
 * Render function for what comes after the new settings group title.
 */
function fh_seo_settings_section_render() {
}

/**
 * Render function
 */
function fh_seo_api_key_render() {
	$options = get_option( 'fh_seo_settings' );

	?>
		<input type='password' class="input input-api-key regular-text code" name='fh_seo_settings[api_key]' value='<?php echo esc_attr( $options['api_key'] ?? '' ); ?>'>
		<p class="description">The API key provided by Funkhaus.</p>
	<?php
}

/**
 * Render function for the new Admin page. This controls the output of everything on the page.
 */
function fh_seo_options_page() {
	?>

	<div id="page-fh-seo-options" class="wrap page-fh-seo-options">
		<h1>Auto SEO</h1>

		<p>Automatically implement SEO best practices using the power of AI.</p>

		<form action='options.php' method='post'>

			<?php
			// Required WP functions so form submits correctly.
			settings_fields( 'fh_seo_plugin' );
			do_settings_sections( 'fh_seo_plugin' );
			submit_button();
			?>

		</form>

		<h2>Regenerate Images</h2>

		<p>This will go through all existing images and convert PNGs to JPEG, and set all the image meta data. It will only affect images that haven't been auto-SEO'd before.</p>

		<div class="fh-seo-progress-status">
			<div class="fh-seo-bar">
				<div class="fh-seo-progress"></div>
				<span class="fh-seo-percentage">0%</span>
			</div>

			<input type="button" data-total="<?php echo esc_attr( fh_seo_get_total_new_attachments() ); ?>" id="start-regenerate" class="button button-primary" value="Start regeneration">
		</div>

		<p class="fh-seo-log-text">Regeneration log. See your browsers console for more details.</p>

		<p class="fh-seo-stats">
			Total succeeded: <span class="total-success">0</span></br>
			Total failed: <span class="total-fail">0</span>
		</p>

		<div class="fh-seo-log"></div>

	</div>

	<?php
}
