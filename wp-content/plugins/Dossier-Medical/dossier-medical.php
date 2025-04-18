<?php
/**
 * Plugin Name: Dossier medical
 * Description: Crée une page pour afficher le dossier medical du patient
 * Version: 1.0
 * Author: yessine berrich
 */

if (!defined('ABSPATH')) {
    exit;
}

function dossier_medical($atts) {
    $current_user_id = get_current_user_id();

    global $wpdb;

    $table_name = $wpdb->prefix . 'dossiers';
    $dossiers = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT *
             FROM $table_name WHERE id_patient = %d ORDER BY id_psy ASC",
            $current_user_id
        )
    );

    if (empty($dossiers)) {
        return '<h1 style="text-align: center; display: flex; justify-content: center; align-items: center; min-height: 300px;">Votre dossier médical est en cours de préparation<br> Veuillez revenir plus tard</h1>';
    }
    
    $psys = $wpdb->get_results($wpdb->prepare("SELECT * FROM wp_psychologues"));
    $output = '<div class="patient-notes">';
    $output .= '<h2>Vos dossiers médicales</h2>';

    $previous_psy_id = null;
    foreach ($dossiers as $dossier) {
        if ($dossier->id_psy != $previous_psy_id) {
            if ($psys) {
                foreach ($psys as $psy) {
                    if ($psy->id == $dossier->id_psy) {
                        $output .= '<h3>Psychologue : ' . esc_html($psy->name) . '</h3>';
                        $output .= '<p><strong>Note generale :</strong> ' . esc_html($dossier->note) . '</p>';
                        $output .= '<p><strong>Antécédents médicaux :</strong> ' . esc_html($dossier->antecedents_Medicaux) . '</p>';
                        $output .= '<p><strong>Consultations et Diagnostiques :</strong> ' . esc_html($dossier->consultations_et_Diagnostiques) . '</p>';
                        $output .= '<p><strong>Traitements en Cours :</strong> ' . esc_html($dossier->traitements_en_Cours) . '</p>';
                        $output .= '<p><strong>Examen et Résultats :</strong> ' . esc_html($dossier->examen_et_Resultats) . '</p>';
                        $output .= '<hr>';
                        break;
                    }
                }
            }
            $previous_psy_id = $dossier->id_psy;
            
        }
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('afficher_notes_patient', 'dossier_medical');

function dossier_med_style() {
    wp_enqueue_style( 'dossier_med-style', plugin_dir_url( __FILE__ ) . 'css/style.css' );
}
add_action( 'wp_enqueue_scripts', 'dossier_med_style' );
