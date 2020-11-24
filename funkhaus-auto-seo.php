<?php
/*
Plugin Name: Auto SEO
Description: Automatically implement SEO best practices using the power of AI.
Version: 1.0
Author: Funkhaus
Plugin URI:  https://github.com/funkhaus/auto-seo
Author URI:  http://funkhaus.us
*/

/*
 * Import required files
 */
include_once plugin_dir_path(__FILE__) . "includes/utilities.php";
include_once plugin_dir_path(__FILE__) . "includes/azure.php";
include_once plugin_dir_path(__FILE__) . "includes/convert-image.php";
include_once plugin_dir_path(__FILE__) . "includes/rename-image.php";
include_once plugin_dir_path(__FILE__) . "includes/focal-point.php";
include_once plugin_dir_path(__FILE__) . "includes/color-detect.php";

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
        "api_key" => "deb2fcfccc044b6ba1375877268f2d78",
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
 * When an attachment is added and we have a Post ID, rename and add meta data via Azure.
 */
function fh_seo_rename_and_discribe($attachment_id)
{
    $output = false;

    // Only try this on formats Azure supports
    $type = get_post_mime_type($attachment_id);
    switch ($type) {
        case "image/jpeg":
        case "image/png":
        case "image/gif":
            break;

        default:
            return false;
    }

    // Set color first as doesn't need Azure
    $output = fh_seo_attachment_set_colors($attachment_id);

    // Abort if no API key
    $options = get_option("fh_seo_settings");
    if (empty($options["api_key"])) {
        return false;
    }

    // Name and caption attachment
    $output = fh_seo_attachment_set_name_and_caption($attachment_id);

    // Set focal point
    $output = fh_seo_attachment_set_focal_point($attachment_id);
    
    return $output;
}


/*

add_filter( 'wp_handle_upload_prefilter', 'custom_upload_filter' );
function custom_upload_filter( $file ) {
 
        
    $response = wp_remote_post( fh_seo_azure_endpoint() . '/vision/v3.1/describe?maxCandidates=2', [
        'body'    => file_get_contents( $file['tmp_name'] ),
        'headers' => [
            'Content-Type' => 'application/octet-stream',
            'Ocp-Apim-Subscription-Key' => $options['api_key'],
        ],
    ]);
    
    $body = wp_remote_retrieve_body($response);
    $body = json_decode($body);    
    
    var_dump($body); die;
 
 
    $file['name'] = $post_name . '-' . $file['name'];
    return $file;
}
*/

