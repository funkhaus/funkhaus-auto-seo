<?php
/**
 * Utility functions.
 *
 * @package funkhaus-auto-seo
 */

/**
 * Convert a file extension on any string (path or URL)
 *
 * @param string $string        File name string.
 * @param string $new_extension New file extension to replace with.
 *
 * @return string New stirng with the extension replace
 */
function fh_seo_replace_extension( $string, $new_extension = '.jpg' ) {
	// Do nothing if no extension found
	$contains_period = strpos( $string, '.' );
	if ( $contains_period === false ) {
		return $string;
	}

	// Replace everything after last period
	return preg_replace( '/\.[^.]+$/', $new_extension, $string );
}

/**
 * Conditional to check if a PNG has a transparent pixel in it.
 *
 * @see https://stackoverflow.com/a/54827140/503546
 *
 * @param GdImage|resource $image Image resource.
 *
 * @return bool True of has a transparent PNG in it.
 * @throws \InvalidArgumentException If $image is not gd image throws the error.
 */
function fh_seo_image_has_transparency( $image ) {
	if ( ! is_gd_image( $image ) ) {
		throw new \InvalidArgumentException(
			'Image resource expected. Got: ' . gettype( $image )
		);
	}

	$shrink_factor        = 64.0;
	$min_square_to_shrink = 64.0 * 64.0;

	$width  = imagesx( $image );
	$height = imagesy( $image );
	$square = $width * $height;

	if ( $square <= $min_square_to_shrink ) {
		$thumb        = $image;
		$thumb_width  = $width;
		$thumb_height = $height;
	} else {
		$thumb_square = $square / $shrink_factor;
		$thumb_width  = (int) round( $width / sqrt( $shrink_factor ) );
		if ( $thumb_width < 1 ) {
			$thumb_width = 1;
		}

		$thumb_height = (int) round( $thumb_square / $thumb_width );
		$thumb        = imagecreatetruecolor( $thumb_width, $thumb_height );
		imagealphablending( $thumb, false );
		imagecopyresized(
			$thumb,
			$image,
			0,
			0,
			0,
			0,
			$thumb_width,
			$thumb_height,
			$width,
			$height
		);
	}

	for ( $i = 0; $i < $thumb_width; $i++ ) {
		for ( $j = 0; $j < $thumb_height; $j++ ) {
			if ( imagecolorat( $thumb, $i, $j ) & 0x7f000000 ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Returns the total attachments that haven't been auto-seo'd before
 */
function fh_seo_get_total_new_attachments() {
	$args  = array(
		'post_status'    => 'any',
		'post_type'      => 'attachment',
		'post_mime_type' => array( 'image/jpeg', 'image/gif', 'image/png' ),
		'posts_per_page' => 1,
		'meta_query'     => array(
			array(
				'key'     => 'fh_seo_timestamp',
				'compare' => 'NOT EXISTS',
			),
		),
	);
	$query = new WP_Query( $args );

	return $query->found_posts;
}
