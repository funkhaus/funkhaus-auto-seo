<?php

/*
 * Load all the Blurhash required files manually (to not use Composer)
 * SEE https://github.com/kornrunner/php-blurhash
 */

fh_load_blurhash();

use kornrunner\Blurhash\Blurhash;

/**
* Set the blurhash of attachment
*
* @param int $attachment_id The WP post_id for the attachment
* @param int $components_x The width of bluehash
* @param int $components_y The height of bluehash
* @returns int|bool Returns the ID of the attachment on success. False if nothing updated.
*/ 
function fh_seo_attachment_set_bluehash($attachment_id, $components_x = 4, $components_y = 4){
    
    $path = get_attached_file($attachment_id);
    $image = imagecreatefromstring(file_get_contents($path));
    $width = imagesx($image);

    $max_width = 20;
    if( $width > $max_width ) { 
        // resize for better performance
        $image = imagescale($image, $max_width);
        $width = imagesx($image);
    }

    $height = imagesy($image);


    $pixels = [];
    for ($y = 0; $y < $height; $y++) {
        $row = [];
        for ($x = 0; $x < $width; $x++) {
            $index = imagecolorat($image, $x, $y);
            $colors = imagecolorsforindex($image, $index);
            $row[] = [$colors['red'], $colors['green'], $colors['blue']];
        }
        $pixels[] = $row;
    }

    $blurhash = Blurhash::encode($pixels, $components_x, $components_y);
    
    // Save to ACF or regular meta field
    if ( class_exists('ACF') ) {
        update_field('blurhash', $blurhash, $attachment_id);
    } else {
        update_post_meta($attachment_id, 'blurhash', $blurhash);
    }

    return $attachment_id;
}

/**
* Decode blurhash value from 
*
* @param int $attachment_id The WP post_id for the attachment
* @param int $width The width of bluehash image
* @param int $height The height of bluehash image
* @returns array|bool Returns the array of the bluhash image color pixels on success.
*/
function fh_seo_attachment_decode_bluehash($attachment_id, $width = 300, $height = 300) {
    $blurhash = get_post_meta($attachment_id, 'blurhash', true);

    if ( empty( $blurhash ) ) {
        return false;
    }

    $pixels = Blurhash::decode($blurhash, $width, $height);
    $image  = imagecreatetruecolor($width, $height);
    for ($y = 0; $y < $height; ++$y) {
        for ($x = 0; $x < $width; ++$x) {
            [$r, $g, $b] = $pixels[$y][$x];
            imagesetpixel($image, $x, $y, imagecolorallocate($image, $r, $g, $b));
        }
    }
    // imagepng($image, 'blurhash.png');
    return $image;
}
    