<?php
/*
Plugin Name: Patient Manager
Description: Plugin to manage patients
Version: 1.0
Author: yessine berrich
*/

defined('ABSPATH') or die('No script kiddies please!');

function display_patients_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'patients';
    
    // Vérifier si un patient doit être supprimé
    if (isset($_GET['delete_patient'])) {
        $patient_id = intval($_GET['delete_patient']);
        $wpdb->delete($table_name, array('id' => $patient_id));
        echo '<div class="updated"><p>Patient supprimé avec succès.</p></div>';
    }

    $patients = $wpdb->get_results("SELECT * FROM $table_name");

    

    echo '<div class="wrap">';
    echo '<h1>Liste des Patients</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Date de Naissance</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>Adresse</th>
                <th>Action</th>
            </tr>
          </thead>';
    echo '<tbody>';
    
    if ($patients) {
        foreach ($patients as $patient) {
            echo '<tr>';
            echo '<td>' . esc_html($patient->id) . '</td>';
            echo '<td>' . esc_html($patient->name) . '</td>';
            echo '<td>' . esc_html($patient->date_naissance) . '</td>';
            echo '<td>' . esc_html($patient->telephone) . '</td>';
            echo '<td>' . esc_html($patient->email) . '</td>';
            echo '<td>' . esc_html($patient->adresse) . '</td>';
            echo '<td><a href="?page=patients-manager&delete_patient=' . esc_attr($patient->id) . '" class="button button-danger" onclick="return confirm(\'Voulez-vous vraiment supprimer ce patient ?\');">Supprimer</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7" style="text-align: center;">Aucun patient enregistré.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function add_patients_menu() {
    add_menu_page(
        'Gérer les Patients',
        'Patients',
        'manage_options',
        'patients-manager',
        'display_patients_page',
    );
}
add_action('admin_menu', 'add_patients_menu');