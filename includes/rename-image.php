<?php
/*
 * Required for file renaming to work WordPress
 */
require_once( ABSPATH . 'wp-admin/includes/image.php' );


/**
* Handles doing a renames and caption of a file.
*
* @param int $attachment_id The WP post_id for the attachment
* @returns WP_error | int | bool Returns a WP-Error on network fail, or the ID of the attachment on success. False if nothing updated.
*/    
function fh_seo_attachment_set_name_and_caption($attachment_id) {

    // Send image to Azure for AI analyzing 
    $azure_data = fh_seo_azure_post_analyze($attachment_id);

    // Abort early if network error
    if( is_wp_error($azure_data) ) {
        return $azure_data;
    }
    
    // Setup post args to save
    $args = [
        'ID' => $attachment_id,        
    ];
    
    // Some defaults
    $celebrities = "";
    $brands = "";
    
    // Collect celebrity names in image
    if( !empty($azure_data->categories[0]->detail->celebrities) ) {
        $celebrities = $azure_data->categories[0]->detail->celebrities;
        
        // Turn array of objects into names
        $names = array_map(function ($obj) {
            return $obj->name;
        }, $celebrities);        
        
        // Set as string of celebrity names        
        $celebrities = implode(', ', $names) . " - ";
    }
    
    // Collect brand names in image
    if( !empty($azure_data->brands) ) {
        $brands = $azure_data->brands;
        
        // Turn array of objects into names
        $names = array_map(function ($obj) {
            return $obj->name;
        }, $brands);        
        
        // Set as string of brand names        
        $brands = implode(', ', $names) . " - ";
    }           
    
    // Rename file to discription tags
    if( !empty($azure_data->description->tags) ) {

        // Get tags
        $tags = $azure_data->description->tags;
        $tags = implode(' ', $tags);
        
        // Prepend with celebrities names if we have them
        $name = ucwords($celebrities) . ucwords($brands) . ucfirst($tags);
        $name = trim($name);
        
        // Rename file (this will santize the filename too)
        fh_seo_attachment_rename($attachment_id, strtolower($name));

        // Set title to filename
    	$args['post_title'] = ucfirst($name);
    }

    // Add caption
    if( !empty($azure_data->description->captions[0]) ) {
        $caption = $azure_data->description->captions[0]->text;
        $caption = $caption . ".";
        $args['post_excerpt'] = ucfirst( $caption );
    }
    
    // Add alt text based on image tags
    if( !empty($azure_data->tags) ) {        
        // Turn array of objects into names
        $names = array_map(function ($obj) {
            return $obj->name;
        }, $azure_data->tags);   

        // Save as string of tag names
        $image_tags = implode(' ', $names);
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image_tags );  
    }

    // Save post data and return
    return wp_update_post($args);
}    

/**
* Renames a file. Will also regenerate all the thumbnails that go with the file.
* @SEE https://stackoverflow.com/questions/64990515/wordpress-rename-attachment-file-with-working-thumbnails
*
* @param string $post_id The WordPress post_id for the attachment
* @param string $new_file_name The filename (without extension) that you want to rename to
*/  
function fh_seo_attachment_rename($post_id, $filename) {

    // Get path info of orginal file
    $og_url = wp_get_attachment_url($post_id);
    $og_path = get_attached_file($post_id);
    $path_info = pathinfo($og_path);
    $og_meta = get_post_meta($post_id, '_wp_attachment_metadata', true);

    // Santize filename
    $safe_filename = wp_unique_filename($path_info['dirname'], $filename . "." . $path_info['extension']);

    // Build out path to new file
    $new_path = $path_info['dirname']. "/" . $safe_filename;

    // Rename the file in the file system
    rename($og_path, $new_path); 

    // Delete old image sizes if we have them
    if( !empty($og_meta) ) {
        fh_seo_delete_attachment_files($post_id);
    }

    // Now save new path to file in WP
    update_attached_file( $post_id, $new_path );

    // Register filter to update metadata
    $new_data = wp_generate_attachment_metadata($post_id, $new_path);    
    return add_filter('wp_update_attachment_metadata', function($data, $post_id) use ($new_data) {        
        return $new_data;
    }, 10, 2);

}

/**
 * Delete all old image files, if they aren't used post_content anywhere. 
 * The dont-delete check isn't perfect, it will give a lot of false postives (keeping more files than it should), but it's best I could come up with.
 * @SEE https://github.com/WordPress/WordPress/blob/f4cda1b62ffca52115e4b04d9d75047060d69e68/wp-includes/post.php#L5983
 *
 * @param string $post_id The WordPress post_id for the attachment
 */ 
function fh_seo_delete_attachment_files($post_id) {
    
    $meta = wp_get_attachment_metadata( $post_id );
    $backup_sizes = get_post_meta( $post_id, '_wp_attachment_backup_sizes', true );
    $file = get_attached_file( $post_id );   
    $url = wp_get_attachment_url($post_id);

    // Check if image is used in a post somehwere
    $url_without_extension = fh_seo_replace_extension($url, '');
    $args = [
        "s" => $url_without_extension,
        "posts_per_page" => 1,
        "post_type" => "any",
        'fields' => 'ids'
    ];
    $found = get_posts($args);

    if( empty($found) ) {
        return wp_delete_attachment_files( $post_id, $meta, $backup_sizes, $file );
    }

    return false;
}