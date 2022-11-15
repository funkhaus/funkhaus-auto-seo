<?php
/**
 * Library load function
 *
 * @package funkhaus-auto-seo
 */

/**
 * Load Blurhash dependencies.
 */
function fh_load_blurhash() {
	$blurhas_root = __DIR__ . '/Blurhash/';
	include_once $blurhas_root . 'AC.php';
	include_once $blurhas_root . 'Base83.php';
	include_once $blurhas_root . 'Blurhash.php';
	include_once $blurhas_root . 'Color.php';
	include_once $blurhas_root . 'DC.php';
}

/**
 * Load ColorThief dependencies
 */
function fh_load_colorthief() {
	$colorthief_root = __DIR__ . '/ColorThief/';

	// Include exception files.
	require_once $colorthief_root . 'Exception/Exception.php';
	require_once $colorthief_root . 'Exception/InvalidArgumentException.php';
	require_once $colorthief_root . 'Exception/NotReadableException.php';
	require_once $colorthief_root . 'Exception/NotSupportedException.php';
	require_once $colorthief_root . 'Exception/RuntimeException.php';

	// Include Image files.
	require_once $colorthief_root . 'Image/ImageLoader.php';
	require_once $colorthief_root . 'Image/Adapter/AdapterInterface.php';
	require_once $colorthief_root . 'Image/Adapter/AbstractAdapter.php';
	require_once $colorthief_root . 'Image/Adapter/GdAdapter.php';
	require_once $colorthief_root . 'Image/Adapter/GmagickAdapter.php';
	require_once $colorthief_root . 'Image/Adapter/ImagickAdapter.php';

	require_once $colorthief_root . 'Color.php';
	require_once $colorthief_root . 'ColorThief.php';
	require_once $colorthief_root . 'PQueue.php';
	require_once $colorthief_root . 'VBox.php';
}
