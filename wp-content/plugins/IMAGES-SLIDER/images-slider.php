<?php
/*
Plugin Name: Image Slider
Description: Un slider d'images avec redirection vers une page de description.
Version: 1.2
Author: Yessine Berrich
*/

if (!defined('ABSPATH')) {
    exit;
}

function image_assets() {
    wp_enqueue_style('image-slider-styles' , plugin_dir_url(__FILE__) . 'assets/style.css');
}
add_action('wp_enqueue_scripts', 'image_assets');

function image_data() {
    $json_file = plugin_dir_path(__FILE__) . 'data/images.json';

    if ( file_exists( $json_file ) ) {
        return json_decode( file_get_contents( $json_file ), true );
    }
    return [];
}

function image_slider() {
    $images = image_data();

    if (empty($images)) {
        return '<p>Aucune image disponible.</p>';
    }

    ob_start();
    echo '<div class="image-slider" style="scrollbar-width: none;-ms-overflow-style: none;">';
    foreach ($images as $image) {
        $image_url = plugin_dir_url(__FILE__) . $image['image'];
        $details_url = add_query_arg([
            'title' => urlencode($image['title']),
        ], get_permalink(get_page_by_path('details')));

        echo '<div class="image-card">';
        echo '<a style="text-decoration: none;" href="' . esc_url($details_url) . '">';
        echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image['title']) . '" />';
        echo '<div class="title" id="modalTitle">' . esc_html($image['title']) . '</div></a>';
        echo '</div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('image_slider', 'image_slider');

function image_details() {

    if (!isset($_GET['title'])) {
        return '<p>Aucune information disponible.</p>';
    }

    $images = image_data();
    $title = urldecode($_GET['title']);

    foreach ($images as $image) {
        if ($image['title'] === $title) {
            ob_start();
            echo '<div class="image-details">';
            echo '<h1>' . esc_html($image['title_desc']) . '</h1>';
            echo '<img style="height:400px; object-fit: cover" src="' . plugin_dir_url(__FILE__) . $image['image'] . '" alt="' . esc_attr($image['title']) . '" />';
            if (isset($image['details'])) {
                $details = $image['details'];
                
                if (isset($details['headings']) && isset($details['paragraphs'])) {
                    $count = max(count($details['headings']), count($details['paragraphs']));
                    
                    for ($i = 0; $i < $count; $i++) {
                        if (isset($details['headings'][$i])) {
                            echo '<h2>' . esc_html($details['headings'][$i]) . '</h2>';
                        }
                        if (isset($details['paragraphs'][$i])) {
                            echo $details['paragraphs'][$i];
                        }
                    }
                }
            }
            echo '</div>';
            return ob_get_clean();
        }
    }

    return '<p>Aucune image trouvée pour ce titre.</p>';
}
add_shortcode('image_details', 'image_details');