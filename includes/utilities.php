<?php    

/**
 * Convert a file extension on any string (path or URL)
 *
 * @returns string New stirng with the extension replace
 */
function fh_seo_replace_extension($string, $new_extension = ".jpg")
{
    // Do nothing if no extension found
    $contains_period = strpos($string, ".");
    if ($contains_period === false) {
        return $string;
    }

    // Replace everything after last period
    return preg_replace('/\.[^.]+$/', $new_extension, $string);
}

/**
 * Conditional to check if a PNG has a transparent pixel in it
 * @see https://stackoverflow.com/a/54827140/503546
 *
 * @returns bool True of has a transparent PNG in it
 */
function fh_seo_image_has_transparency($image)
{
    if (!is_resource($image)) {
        throw new \InvalidArgumentException(
            "Image resource expected. Got: " . gettype($image)
        );
    }

    $shrinkFactor = 64.0;
    $minSquareToShrink = 64.0 * 64.0;

    $width = imagesx($image);
    $height = imagesy($image);
    $square = $width * $height;

    if ($square <= $minSquareToShrink) {
        [$thumb, $thumbWidth, $thumbHeight] = [$image, $width, $height];
    } else {
        $thumbSquare = $square / $shrinkFactor;
        $thumbWidth = (int) round($width / sqrt($shrinkFactor));
        $thumbWidth < 1 and ($thumbWidth = 1);
        $thumbHeight = (int) round($thumbSquare / $thumbWidth);
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagealphablending($thumb, false);
        imagecopyresized(
            $thumb,
            $image,
            0,
            0,
            0,
            0,
            $thumbWidth,
            $thumbHeight,
            $width,
            $height
        );
    }

    for ($i = 0; $i < $thumbWidth; $i++) {
        for ($j = 0; $j < $thumbHeight; $j++) {
            if (imagecolorat($thumb, $i, $j) & 0x7f000000) {
                return true;
            }
        }
    }

    return false;
}
