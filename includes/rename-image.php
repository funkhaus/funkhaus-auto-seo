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
    $output = false;
    
    // Send image to Azure for AI analyzing 
    $azure_data = fh_seo_azure_post_describe($attachment_id);
    
    // Abort early if network error
    if( is_wp_error($azure_data) ) {
        return $azure_data;
    }        
    
    // Rename file and alt text to tags
    if( isset($azure_data->description->tags) ) {

        // Rename
        $tags = $azure_data->description->tags;
        $filename = implode(' ', $tags);
        $output = fh_seo_attachment_rename($attachment_id, $filename);  
        
        // Set alt text to same as filename
    	update_post_meta( $attachment_id, '_wp_attachment_image_alt', ucfirst($filename) );                  
    }

    // Add caption
    if( !empty($azure_data->description->captions) ) {
        $captions = $azure_data->description->captions;

        // Save caption
    	$output = wp_update_post([
    		'ID' => $attachment_id,
    		'post_excerpt' => ucfirst($captions[0]->text),
    	]);
    }
    
    return $output;
}    

// See: https://wordpress.stackexchange.com/questions/166943/how-to-rename-an-image-attachment-filename-using-php
// See: https://wordpress.stackexchange.com/questions/30313/change-attachment-filename
// See: https://wordpress.stackexchange.com/a/221254/4562
function fh_seo_attachment_rename( $post_id, $new_filename = 'new filename' ){

    // Get path info of orginal file
    $og_path = get_attached_file($post_id);
    $path_info = pathinfo($og_path);

    // Santize filename
    $safe_filename = wp_unique_filename($path_info['dirname'], $new_filename);

    // Build out path to new file
    $new_path = $path_info['dirname']. "/" . $safe_filename . "." .$path_info['extension'];
    
    // Rename the file and update it's location in WP
    rename($og_path, $new_path);    
    update_attached_file( $post_id, $new_path );

    // URL to the new file
    $new_url = wp_get_attachment_url($post_id);
    
    // Update attachment data
	$id = wp_update_post([
		'ID' => $post_id,
		'post_title' => $new_filename,
	]);
	
    // Reset the GUID for the attachment?
/*
    global $wpdb;
    $result = $wpdb->update($wpdb->posts, ['guid' => $new_url], ['ID' => $post_id]);
*/
    
    // Update all links to old "sizes" files
    $metadata = get_post_meta($post_id, '_wp_attachment_metadata', true);
    if( empty($metadata) ) {

        // New upload
        $data = wp_generate_attachment_metadata($post_id, $new_path);
        update_post_meta($post_id, '_wp_attachment_metadata', $data);        
        
    } else {
        // Regenerating an image
        // TODO loop through $metadata and update the filename here? Maybe just delete it and regenerate it if it exsists?        
        var_dump("has metatdata", $metadata);
    }
    
    // Update use of the old filename?
    //UPDATE wp_posts SET post_content = REPLACE(post_content,'www.domain.com/wp-content/uploads','www.domain.com/images');    

    
//    var_dump($data); die;
}








    
/**
* Renames a file. Will also regenerate all the thumbnails that go with the file.
* @SEE https://wordpress.org/plugins/wp-media-files-name-rename/ for inspiration
*
* @param string $new_file_name The filename (without extension) that you want to rename to
*/    
/*
function fh_seo_attachment_rename( $post_id, $new_file_name ){

	// Attachment post id
	$id = $post_id;

	// get the media file dir path based on the media id (https://developer.wordpress.org/reference/functions/get_attached_file/)
	$original_file = get_attached_file( $id );

	// get media file name (https://www.php.net/manual/en/function.basename.php)
	$original_file_name = basename( $original_file );

	// get media file extension (https://php.net/manual/en/function.pathinfo.php)
	$original_file_ext = pathinfo( $original_file, PATHINFO_EXTENSION );
	
	// get media file full path excluded file name + ext (https://developer.wordpress.org/reference/functions/trailingslashit/)
	$original_file_path = trailingslashit( str_replace( "\\", "/" , pathinfo( $original_file, PATHINFO_DIRNAME ) ) );

    // Make a new filename that is sanitized and unique
	$new_filename = wp_unique_filename( $original_file_path, $new_file_name . "." . $original_file_ext );

	// combine file path + new file name
	$new_file = $original_file_path . $new_filename;

    // Delete any thumbnails that were made
	fh_seo_delete_image_thumbnails( $original_file_path, $original_file, $original_file_ext );
	
    // Rename the orginal media with new file name
	rename( $original_file_path . $original_file_name, $new_file );
	
    // Update file location in database
	$old_wp_attached_file = get_post_meta( $id, '_wp_attached_file', true );
	$new_wp_attached_file = str_replace( $original_file_name, $new_filename, $old_wp_attached_file );
	update_attached_file( $id, $new_wp_attached_file );

	// Get post object
	$post_for_guid = get_post( $id );

	// Update guid for attachment
	$guid = str_replace( $original_file_name, $new_filename, $post_for_guid->guid );

	// Update the media post
	$id = wp_update_post([
		'ID' => $id,
		'guid' => $guid,
		'post_title' => $new_file_name,
	]);

	// Update metadata for that attachment
    $delete = delete_post_meta($id, '_wp_attachment_metadata'); 
    $data = wp_generate_attachment_metadata($id, $new_file);       
    $result = update_post_meta($id, '_wp_attachment_metadata', $data);
        
    return $id;
}
*/

/**
* Delete all thumbnails. Used to delete everything before re-generating all the sizes.
* @SEE https://wordpress.org/plugins/wp-media-files-name-rename/ for inspiration
*/
function fh_seo_delete_image_thumbnails( $file_path, $file_name, $file_ext ) {

  	$all_thumbnailes_sizes = fh_seo_get_all_image_sizes();

  	$file_name_excluded_ext = basename( $file_name, '.' . $file_ext );

	foreach( $all_thumbnailes_sizes as $thumbnail ) {
	    
	    // Build thumbnail file name
		$thumbnail_file_name = $file_name_excluded_ext . '-' . $thumbnail['width'] . 'x' . $thumbnail['height'] . '.' . $file_ext;
		$full_file_path = $file_path . $thumbnail_file_name;

		// check if file exists
		if ( file_is_valid_image( $full_file_path ) ) {
			wp_delete_file($full_file_path);
		}
	}
}    

/**
* Get all images in format ready to build out paths to each file
* @SEE https://wordpress.org/plugins/wp-media-files-name-rename/ for inspiration
*/
function fh_seo_get_all_image_sizes() {
    $image_sizes = [];

    $default_image_sizes = get_intermediate_image_sizes();

    foreach ( $default_image_sizes as $size ) {
        $image_sizes[ $size ][ 'width' ] = intval( get_option( "{$size}_size_w" ) );
        $image_sizes[ $size ][ 'height' ] = intval( get_option( "{$size}_size_h" ) );
    }

    return $image_sizes;
}

