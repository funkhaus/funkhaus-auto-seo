<?php
/**
 * Color detect functions
 *
 * @package funkhaus-auto-seo
 */

use ColorThief\ColorThief;
use ColorThief\Image\Adapter\GdAdapter;

/**
 * Detect the primary color in the image.
 *
 * @param int $attachment_id The WP post_id for the attachment.
 * @return int|bool Returns the ID of the attachment on success. False if nothing updated.
 * @throws Exception ColorThief::getColor error handling.
 */
function fh_seo_attachment_set_colors( $attachment_id ) {
	fh_load_colorthief();

	$path = get_attached_file( $attachment_id );
	try {
		$dominant_color = ColorThief::getColor( $path );
	} catch ( Exception $e ) {
		$msg = $e->getMessage();
		if ( str_contains( $msg, 'Unable to compute the color palette' ) ) {
			return false;
		} else {
			throw $e;
		}
	}

	// Abort if no colors detected
	if ( empty( $dominant_color ) ) {
		return false;
	}

	$loader = new GdAdapter();
	$loader->loadFromPath( $path );
	$image = $loader->getResource();

	$is_transparent = fh_seo_image_has_transparency( $image );

	// Take RGB value, turn to hex
	if ( $is_transparent ) {
		$hex = sprintf( '#%02x%02x%02x%02x', $dominant_color[0], $dominant_color[1], $dominant_color[2], '00' );
	} else {
		$hex = sprintf( '#%02x%02x%02x', $dominant_color[0], $dominant_color[1], $dominant_color[2] );
	}

	// Save to ACF or regular meta field
	if ( class_exists( 'ACF' ) ) {
		update_field( 'primary_color', $hex, $attachment_id );
	} else {
		update_post_meta( $attachment_id, 'primary_color', $hex );
	}

	return $attachment_id;
}
