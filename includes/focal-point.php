<?php

/**
 * Get focal point from Azure and save to image
 *
 * @param int $attachment_id Attachment ID.
 */
function fh_seo_attachment_set_focal_point( $attachment_id ) {

	$azure_data = fh_seo_azure_post_area_of_interest( $attachment_id );

	// Abort early if network error
	if ( is_wp_error( $azure_data ) ) {
		return $azure_data;
	}

	// Abort if no data from Azure.
	if ( empty( $azure_data->areaOfInterest ) ) {
		return false;
	}

	// Get middle of focal point as pixels.
	$x_pixel = $azure_data->areaOfInterest->x + ( $azure_data->areaOfInterest->w / 2 );
	$y_pixel = $azure_data->areaOfInterest->y + ( $azure_data->areaOfInterest->h / 2 );

	// Convert to percentage positions.
	$x_percentage = round( $x_pixel / $azure_data->metadata->width * 100, 2 );
	$y_percentage = round( $y_pixel / $azure_data->metadata->height * 100, 2 );

	// Save to ACF or regular meta field.
	if ( class_exists( 'ACF' ) ) {
		update_field( 'focal_point_x', $x_percentage, $attachment_id );
		update_field( 'focal_point_y', $y_percentage, $attachment_id );
	} else {
		update_post_meta( $attachment_id, 'focal_point_x', $x_percentage );
		update_post_meta( $attachment_id, 'focal_point_y', $y_percentage );
	}

	return $attachment_id;
}
