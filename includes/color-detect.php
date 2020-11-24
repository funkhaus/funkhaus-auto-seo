<?php

/*
 * Load all the ColorThief required files manually (to not use COmposer)
 * SEE https://github.com/ksubileau/color-thief-php
 */
include_once plugin_dir_path(__FILE__) . "../lib/ColorThief/ColorThief.php";
include_once plugin_dir_path(__FILE__) . "../lib/ColorThief/Image/ImageLoader.php";
include_once plugin_dir_path(__FILE__) . "../lib/ColorThief/Image/Adapter/IImageAdapter.php";    
include_once plugin_dir_path(__FILE__) . "../lib/ColorThief/Image/Adapter/ImageAdapter.php";    
include_once plugin_dir_path(__FILE__) . "../lib/ColorThief/Image/Adapter/ImagickImageAdapter.php";        
include_once plugin_dir_path(__FILE__) . "../lib/ColorThief/VBox.php";
include_once plugin_dir_path(__FILE__) . "../lib/ColorThief/PQueue.php";    
include_once plugin_dir_path(__FILE__) . "../lib/ColorThief/CMap.php";        
use ColorThief\ColorThief;


/**
* Detect the primary color in the image.
*
* @param int $attachment_id The WP post_id for the attachment
* @returns int|bool Returns the ID of the attachment on success. False if nothing updated.
*/ 
function fh_seo_attachment_set_colors($attachment_id){
    
    $path = get_attached_file($attachment_id);
    $dominant_color = ColorThief::getColor($path);
    
    // Abort if no colors detected
    if( empty($dominant_color) ) {
        return false;
    }
    
    // Take RGB value, turn to hex
    $hex = sprintf("#%02x%02x%02x", $dominant_color[0], $dominant_color[1], $dominant_color[2]);
    
    // Save to ACF or regular meta field
    if ( class_exists('ACF') ) {
        update_field('primary_color', $hex, $attachment_id);
    } else {
        update_post_meta($attachment_id, 'primary_color', $hex);
    }

    return $attachment_id;
}


    