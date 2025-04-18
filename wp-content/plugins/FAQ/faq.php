<?php 
/**
 * Plugin Name: FAQ
 * Description: Un plugin pour afficher des FAQ à partir d'un fichier JSON.
 * Version: 1.0
 * Author: yessine berrich
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function import_faq() {
    $faqs = plugin_dir_path( __FILE__ ) . 'faq.json';
    if ( file_exists( $faqs ) ) {
        return json_decode( file_get_contents( $faqs ), true );
    }
    return [];
}


function afficher_faq() {
    $faqs = import_faq();
    if ( empty( $faqs ) ) {
        return '<p>Aucune FAQ disponible.</p>';
    }

    $ch = '<div class="faq-dynamique">';
    foreach ( $faqs as $faq ) {
        $ch .= '<details class="faq-item">';
        $ch .= '<summary style="cursor:pointer;">' . esc_html( $faq['question'] ) . '</summary>';
        $ch .= '<p>' . esc_html( $faq['réponse'] ) . '</p>';
        $ch .= '</details>';
    }
    $ch .= '</div>';

    return $ch;
}
add_shortcode( 'faq_dynamique', 'afficher_faq' );

function faq_style() {
    wp_enqueue_style( 'faq-dynamique-style', plugin_dir_url( __FILE__ ) . 'css/style.css' );
}
add_action( 'wp_enqueue_scripts', 'faq_style' );