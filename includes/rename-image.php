<?php
/*
 * Required for file renaming to work WordPress
 */
// require_once( ABSPATH . 'wp-admin/includes/image.php' );


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
    
    // Collect celebrity names in image
    if( !empty($azure_data->categories[0]->detail->celebrities) ) {
        $celebrities = $azure_data->categories[0]->detail->celebrities;
        
        // Turn array of objects into names
        $names = array_map(function ($obj) {
            return $obj->name;
        }, $celebrities);        
        
        // Set as sring of celebrity names        
        $celebrities = implode(', ', $names) . " - ";
    }      
    
    // Rename file to discription tags
    if( !empty($azure_data->description->tags) ) {

        // Get tags
        $tags = $azure_data->description->tags;
        $tags = implode(' ', $tags);
        
        // Prepend with celebrities names if we have them
        $name = $celebrities . $tags;
        $name = trim($name);
        
        // Rename file (this will santize the filename too)
        fh_seo_attachment_rename($attachment_id, $name);

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

        // Save as sring of tag names
        $image_tags = implode(' ', $names);
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image_tags );  
    }
    
    // Save a timestamp of so we know image was run already
    update_post_meta( $attachment_id, 'fh_seo_timestamp', date('Y-m-d H:i:s') );  

    // Save post data and return
    return wp_update_post($args);;
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
    
    // Delete old image sizes if we have them
    /*
    $og_meta = get_post_meta($post_id, '_wp_attachment_metadata', true);
    if( !empty($og_meta) ) {
        // TODO Need to figure out how to replace old images in posts first 
        fh_seo_delete_attachment_files($post_id);
    }   
    */ 

    // Santize filename
    $safe_filename = wp_unique_filename($path_info['dirname'], $filename);

    // Build out path to new file
    $new_path = $path_info['dirname']. "/" . $safe_filename . "." .$path_info['extension'];
    
    // Rename the file and update it's location in WP
    rename($og_path, $new_path);    
    update_attached_file( $post_id, $new_path );
    
    // Replace any use of olds URLs in post content
    /*
    if( !empty($og_meta) ) {
        //fh_seo_replace_old_urls($og_url);
    }
    */

    // Register filter to update metadata
    return add_filter('wp_update_attachment_metadata', function($data, $post_id, $new_path) {
        return wp_generate_attachment_metadata($post_id, $new_path);            
    }, 10, 2);
}

/**
 * Delete all old image files 
 * @SEE https://github.com/WordPress/WordPress/blob/f4cda1b62ffca52115e4b04d9d75047060d69e68/wp-includes/post.php#L5983
 *
 * @param string $post_id The WordPress post_id for the attachment
 */ 
function fh_seo_delete_attachment_files($post_id) {
    $meta = wp_get_attachment_metadata( $post_id );
    $backup_sizes = get_post_meta( $post_id, '_wp_attachment_backup_sizes', true );
    $file = get_attached_file( $post_id );   

    wp_delete_attachment_files( $post_id, $meta, $backup_sizes, $file ); 
}

/**
 * Replace the use of old URLs in posts
 * @SEE https://github.com/WordPress/WordPress/blob/f4cda1b62ffca52115e4b04d9d75047060d69e68/wp-includes/post.php#L5983
 *
 * @param string $post_id The WordPress post_id for the attachment
 */ 
function fh_seo_replace_old_urls($old_url, $new_url) {
    // TODO figure out how to update a post content for something like this:
    /*
    <img class="alignnone size-medium wp-image-327" src="https://fuxt-backend.funkhaus.us/wp-content/uploads/2020/07/kevin-231x300.png" alt="" width="231" height="300" />
    
    Perhaps the right move is to just delete images NOT used in a post? And leave those images as is.
    */
}