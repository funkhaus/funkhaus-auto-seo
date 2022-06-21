<?php
/*
Plugin Name: Auto SEO
Description: Automatically implement SEO best practices using the power of AI.
Version: 2.1
Author: Funkhaus
Plugin URI: https://github.com/funkhaus/auto-seo
Author URI: http://funkhaus.us
Text Domain: funkhaus-auto-seo
*/

/*
 * Import required files
 */
include_once plugin_dir_path(__FILE__) . "includes/utilities.php";
include_once plugin_dir_path(__FILE__) . "includes/settings.php";
include_once plugin_dir_path(__FILE__) . "includes/azure.php";
include_once plugin_dir_path(__FILE__) . "includes/convert-image.php";
include_once plugin_dir_path(__FILE__) . "includes/rename-image.php";
include_once plugin_dir_path(__FILE__) . "includes/focal-point.php";
include_once plugin_dir_path(__FILE__) . "includes/color-detect.php";
include_once plugin_dir_path(__FILE__) . "includes/blurhash.php";
include_once plugin_dir_path(__FILE__) . "includes/api.php";

/*
 * Main WP hooks to fire our logic on.
 */
add_filter("wp_handle_upload", "fh_seo_convert", 10, 2);
add_action("add_attachment", "fh_seo_rename_and_discribe", 10, 1);


/*
 * Plugin activated, setup default options
 */
function fh_seo_plugin_activated()
{
    $defaults = [
        "api_key" => "",
    ];
    add_option("fh_seo_settings", $defaults);
}
register_activation_hook(__FILE__, "fh_seo_plugin_activated");

/**
 * Attempt to convert an image on upload. Currently only works for PNGs
 * @see https://developer.wordpress.org/reference/hooks/wp_handle_upload/
 */
function fh_seo_convert($upload, $context)
{
    // Attempt to convert image
    $converted = fh_seo_convert_to_jpg(
        $upload["file"],
        $upload["url"],
        $upload["type"]
    );
    if ( !is_wp_error($converted) ) {
        $upload = $converted;
    }

    return $upload;
}

/**
 * Rename file and add meta data via Azure.
 */
function fh_seo_rename_and_discribe($attachment_id)
{
    $output = [
        "id" => $attachment_id
    ];

    // Only try this on formats Azure and ColorThief support
    $type = get_post_mime_type($attachment_id);
    switch ($type) {
        case "image/jpeg":
        case "image/png":
        case "image/gif":
            break;

        default:
            return false;
    }

    // Set color first as we don't need Azure for this
    $output['set_color'] = fh_seo_attachment_set_colors($attachment_id);

    // Set blurhash
    $output['set_blurhash'] = fh_seo_attachment_set_bluehash($attachment_id);

    // Abort now if no API key
    $options = get_option("fh_seo_settings");
    if (empty($options["api_key"])) {
        return false;
    }

    // Name and caption attachment
    $output['set_metadata'] = fh_seo_attachment_set_name_and_caption($attachment_id);

    // Set focal point
    $output['set_focal_point'] = fh_seo_attachment_set_focal_point($attachment_id);

    // Save a timestamp so we know image was processed already
    update_post_meta( $attachment_id, 'fh_seo_timestamp', date('Y-m-d H:i:s') );

    return $output;
}

/*
 * Enqueue any CSS & JS scripts
 */
function fh_seo_admin_scripts($hook_suffix)
{
    // Only load scripts on the settings page
    if ($hook_suffix == 'settings_page_auto-seo') {
        wp_enqueue_script(
            'fh_seo_main',
            plugins_url("js/main.js", __FILE__),
            null,
            time()
        );
        wp_enqueue_style(
            'fh_seo_main',
            plugins_url("css/main.css", __FILE__),
            null,
            time()
        );

        // Import some JS vars from PHP
        $js_vars = [
            "api_url" => site_url('/wp-json/auto-seo'),
            "nonce" => wp_create_nonce('wp_rest'),
        ];

        wp_add_inline_script(
            'fh_seo_main',
            'var fh_seo_vars = ' . wp_json_encode($js_vars),
            'before'
        );
    }

    // Load focushaus scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('focushaus', plugins_url('js/focushaus.js', __FILE__), 'jquery', '2.01');
    wp_enqueue_style('focushaus', plugins_url('css/focushaus.css', __FILE__), null, '2.01');
}
add_action('admin_enqueue_scripts', 'fh_seo_admin_scripts');


/*
 * Register custom API endpoints
 */
function fh_seo_add_api_routes()
{
    // Use this to trigger a deploy at Netlify
    register_rest_route("auto-seo", "/generate", [
        [
            "methods"   => "POST",
            "callback"  => "fh_seo_generate",
            'args' => [
                'id'        => [],
                'offset'    => []
            ],
            "permission_callback" => "fh_seo_permissions",
        ],
    ]);
}
add_action("rest_api_init", "fh_seo_add_api_routes");
