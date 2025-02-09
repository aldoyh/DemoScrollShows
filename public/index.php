<?php

define('ROOT', dirname(__DIR__));
define('IMGS', ROOT . '/public/images');
define('PIECE_COUNT', 8);

/**
 * Downloads a random image from picsum
 *
 * @param int|null $id
 * @return string
 */
function getRandomImage($id = null)
{
    $url = $id ? "https://picsum.photos/id/$id/600/900" : 'https://picsum.photos/600/900';
    $image = @file_get_contents($url);
    if ($image === false) {
        return '';
    }

    $filename = uniqid() . '.jpg';
    if (!is_dir(IMGS)) {
        mkdir(IMGS, 0755, true);
    }
    file_put_contents(IMGS . '/' . $filename, $image);
    return $filename;
}

// check images folder exists and contains at least 2 images, otherwise get some images
if (!is_dir(IMGS) || count(array_diff(scandir(IMGS), ['.', '..'])) < 2) {
    for ($i = 0; $i < 10; $i++) {
        getRandomImage(random_int(0, 1000));
    }
}

function getImages()
{
    $images = [];
    $files = scandir(IMGS);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $images[] = $file;
        }
    }
    if (count($images) == 0) {
        die('No images found in the images directory.');
    }
    return $images;
}

$images = getImages();

if (file_exists('../templates/index.blade.php')) {
    $template = file_get_contents('../templates/index.blade.php');
    
    // Create array of image URLs
    $image_urls = array_map(function($image) {
        return '/images/' . $image;
    }, array_slice($images, 0, PIECE_COUNT));
    
    // Convert to JavaScript variable declaration
    $image_array = "const IMAGES_DATA = " . json_encode($image_urls) . ";";

    $content = str_replace(
        '{{IMAGES}}',
        $image_array,
        $template
    );

    echo $content;
} else {
    echo "Sorry, the file index.blade.php does not exist in the templates directory.";
}
