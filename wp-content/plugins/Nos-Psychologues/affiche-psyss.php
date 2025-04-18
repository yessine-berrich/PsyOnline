<?php
/*
Plugin Name: Nos psychologues
Description: Plugin pour afficher tous les psychologues.
Version: 1.0
Author: yessine berrich
*/

if (!defined('ABSPATH')) {
    exit;
}

function psyonline_display_psychologues() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'psychologues';

    $psychologues = $wpdb->get_results("SELECT * FROM $table_name");

    $output = '<div class="psychologues-list">';
    $output .= '<h2>Liste des Psychologues</h2>';
    $output .= '<table style="width: 100%; border-collapse: collapse;">';
    $output .= '<thead><tr>
                    <th style="border: 1px solid #ddd; padding: 8px;">Nom</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Type Consultation</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Description</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Photo</th>
                </tr></thead>';
    $output .= '<tbody>';

    foreach ($psychologues as $psy) {
        $output .= '<tr>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">Dr. ' . esc_html($psy->name) . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($psy->type_consultation) . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($psy->description) . '</td>';
        if (!empty($psy->profile_picture)) {
            $output .= '<td style="border: 1px solid #ddd; padding: 8px;">
                            <img src="' . esc_url($psy->profile_picture) . '" alt="' . esc_html($psy->name) . '" style="width: 100px; height: auto;">
                        </td>';
        } else {
            $output .= '<td style="border: 1px solid #ddd; padding: 8px;">Pas de photo</td>';
        }
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '</div>';

    return $output;
}

add_shortcode('display_psychologues', 'psyonline_display_psychologues');

function affiche_psyss_style() {
    wp_enqueue_style( 'aff-psyss-style', plugin_dir_url( __FILE__ ) . 'css/style.css' );
}
add_action( 'wp_enqueue_scripts', 'affiche_psyss_style' );
