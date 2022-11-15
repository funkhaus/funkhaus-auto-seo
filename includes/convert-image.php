<?php
/**
 * Convert PNG to JPG functions.
 *
 * @package funkhaus-auto-seo
 */

/**
 * Converts an image to a JPG and saves it to the file system. Deletes orginal PNG is successful.
 *
 * @param string $file_path In the format of /www/wp-content/uploads/2020/11/example.png .
 * @param string $file_url  Fiel Url.
 * @param string $mime_type Fiel type.
 *
 * @return array | wp-error An array of the [$file_path, $file_url, $mime_type] or WP Error object
 */
function fh_seo_convert_to_jpg( $file_path, $file_url, $mime_type ) {
	// Abort if not a PNG
	if ( $mime_type !== 'image/png' ) {
		return new WP_Error(
			'convert_error',
			'The supplied image is not a PNG ' . $file_url
		);
	}

	$img = imagecreatefrompng( $file_path );

	// Abort if PNG has some transparency
	if ( fh_seo_image_has_transparency( $img ) ) {
		return new WP_Error(
			'convert_error',
			"The supplied PNG has transparency, so we can't convert it to a JPG"
		);
	}

	// Setup PHP image
	$bg = imagecreatetruecolor( imagesx( $img ), imagesy( $img ) );
	imagefill( $bg, 0, 0, imagecolorallocate( $bg, 255, 255, 255 ) );
	imagealphablending( $bg, 1 );
	imagecopy( $bg, $img, 0, 0, 0, 0, imagesx( $img ), imagesy( $img ) );

	// Get file size
	$size_before = filesize( $file_path );
	$size_after  = 0;

	// Figure out new paths
	$i        = 1;
	$new_path = fh_seo_replace_extension( $file_path, '.jpg' );
	$new_url  = fh_seo_replace_extension( $file_url, '.jpg' );

	// Handle duplicate filenames
	// TODO Use wp_unique_filename instead?
	while ( file_exists( $new_path ) ) {
		$new_path = fh_seo_replace_extension( $file_path, '-' . $i . '.jpg' );
		$new_url  = fh_seo_replace_extension( $file_url, '-' . $i . '.jpg' );
		++$i;
	}

	// Make new compressed JPG file
	if ( imagejpeg( $bg, $new_path, 85 ) ) {
		// Is new file actually smaller?
		$size_after = filesize( $new_path );

		if ( $size_after < $size_before ) {
			// Delete old file
			unlink( $file_path );

			// Return new data
			return array(
				'file' => $new_path,
				'url'  => $new_url,
				'type' => 'image/jpeg',
			);
		} else {
			// New file not smaller, so delete new file
			unlink( $new_path );

			return new WP_Error(
				'convert_error',
				'New JPG not smaller than the original PNG ' . $file_url
			);
		}
	}

	// Didn't convert
	return new WP_Error(
		'convert_error',
		'Unable to make a JPG from ' . $file_url
	);
}
