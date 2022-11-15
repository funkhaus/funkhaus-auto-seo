<?php
/**
 * Azure functions.
 *
 * @package funkhaus-auto-seo
 */

/**
 * Get azure endpoint for auto seo.
 *
 * @return string
 */
function fh_seo_azure_endpoint() {
	return 'https://auto-seo.cognitiveservices.azure.com';
}

/**
 * Get image discription and tags from Azure
 *
 * @param int $attachment_id Attachment ID.
 * @return mixed
 */
function fh_seo_azure_post_analyze( $attachment_id ) {
	$options = get_option( 'fh_seo_settings' );

	$body = array(
		'url' => wp_get_attachment_url( $attachment_id ),
	);

	$response = wp_remote_post(
		fh_seo_azure_endpoint() . '/vision/v3.1/analyze?visualFeatures=Brands,Description,Tags&details=Celebrities,Landmarks',
		array(
			'body'    => wp_json_encode( $body ),
			'headers' => array(
				'Content-Type'              => 'application/json',
				'Ocp-Apim-Subscription-Key' => $options['api_key'],
			),
		)
	);

	$body = wp_remote_retrieve_body( $response );

	return json_decode( $body );
}


/**
 * Get focal point from Azure
 *
 * @param int $attachment_id Attachment ID.
 * @return mixed
 */
function fh_seo_azure_post_area_of_interest( $attachment_id ) {
	$options = get_option( 'fh_seo_settings' );

	$body = array(
		'url' => wp_get_attachment_url( $attachment_id ),
	);

	$response = wp_remote_post(
		fh_seo_azure_endpoint() . '/vision/v3.1/areaOfInterest',
		array(
			'body'    => wp_json_encode( $body ),
			'headers' => array(
				'Content-Type'              => 'application/json',
				'Ocp-Apim-Subscription-Key' => $options['api_key'],
			),
		)
	);

	$body = wp_remote_retrieve_body( $response );

	return json_decode( $body );
}
