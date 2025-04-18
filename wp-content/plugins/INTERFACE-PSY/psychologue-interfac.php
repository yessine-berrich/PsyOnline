<?php
/*
Plugin Name: Psychologue Interface
Description: Interface pour les psychologues pour consulter et gérer leurs rendez-vous.
Version: 1.0
Author: yessine berrich
*/

if (!defined('ABSPATH')) {
    exit;
}

function add_psychologue_role() {
    if (!get_role('psychologue')) {
        add_role(
            'psychologue',
            'Psychologue',
            array(
                'read' => true,
                'manage_appointments' => true,
            )
        );
    }
}
add_action('init', 'add_psychologue_role');

function psychologue_check_role() {
    $current_user = wp_get_current_user();
    return in_array('psychologue', (array) $current_user->roles);
}

add_shortcode('psychologue_interface', 'psychologue_interface_page');
function psychologue_interface_page() {
    if (!psychologue_check_role()) {
        wp_die('Vous n’avez pas la permission d’accéder à cette page.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $msg =terminee_actions();
    }
    echo '<style>' . file_get_contents(plugin_dir_path(__FILE__) . 'css/style.css') . '</style>';

    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(document).ready(function($) {
    $("td").each(function() {
        var text = $(this).text().trim();
        if (text === "Terminée") {
            $(this).css({"color": "#27ae60", "font-weight": "bold"});
        } else if (text === "Confirmée") {
            $(this).css({"color": "#2980b9", "font-weight": "bold"});
        } else if (text === "Non payée") {
            $(this).css({"color": "#e74c3c", "font-weight": "bold"});
        } else if (text.includes("payée")) { 
            $(this).css({"color": "#27ae60", "font-weight": "bold"});
        }
    });
});
</script>

    <div>
        <h1>Interface Psychologue</h1>
        <?php
        if ($msg) {
            echo $msg;
        }
        ?>
        <div id="psychologue-interface">
            <h2>Rendez-vous</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php psychologue_display_rdv(); ?>
                </tbody>
            </table>
            <h2>Patients</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Adresse</th>
                        <th>Date de naissance</th>
                        <th>Dossier médical</th>
                    </tr>
                </thead>
                <tbody>
                    <?php display_patients(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function psychologue_display_rdv() {
    global $wpdb;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rdv_id']) && !empty($_POST['zoom_url'])) {
        $rdv_id = intval($_POST['rdv_id']);
        $zoom_url = esc_url_raw($_POST['zoom_url']);

        $rendezvous = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rendezvouss WHERE id = %d",
            $rdv_id
        ));
        if ($rendezvous->status == 'Confirmée') {
            $link = $wpdb->insert(
                $wpdb->prefix . 'consultations',
                array(
                    'id_psy'    => $rendezvous->psychologue_id,
                    'id_patient'    => $rendezvous->user_id,
                    'zoom_link' => $zoom_url,
                ),
                array('%d','%d','%s')
                );
            $wpdb->update('wp_rendezvouss', array('status' => 'En cours...'), array('id' => $rendezvous->id));
            if ($link === false) {
                echo '<div class="error notice"><p>Erreur lors de l\'enregistrement du lien Zoom.</p></div>';
            }else {
                echo '<div class="updated notice"><p>Le lien Zoom a été enregistré avec succès !</p></div>';
            }
        }
        
    }
    $rdvs = $wpdb->prefix . 'rendezvouss';
    $psychologues = $wpdb->prefix . 'psychologues';

    $results = $wpdb->get_results(
        "SELECT R.* FROM $rdvs R, $psychologues P 
         WHERE R.psychologue_id = P.id
           AND P.name = '" . wp_get_current_user()->display_name . "'
           AND R.status IN ('Confirmée', 'Annulée', 'Terminée', 'En cours...')"
    );
    $types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}types_consultation");

    if ($results) {
        foreach ($results as $rdv) {
            echo '<tr>';
            echo '<td>' . $rdv->id . '</td>';
            echo '<td>' . get_userdata($rdv->user_id)->display_name . '</td>';
            echo '<td>' . $rdv->email. '</td>';
            echo '<td>' . $rdv->phone. '</td>';
            // $type = $wpdb->get_row("SELECT * FROM wp_types_consultation WHERE id = %d", $rdv->id_type);
            if ($types) {
                foreach ($types as $type) {
                    if ($type->id == $rdv->id_type) {
                        echo '<td>' . $type->type_consultation . '</td>';
                        break;
                    }
                }
            }
            echo '<td>' . $rdv->consultation_date . '</td>';
            echo '<td>' . $rdv->consultation_temps . '</td>';
            echo '<td>' . $rdv->status . '</td>';
            echo '<td><form method="post" style="display:inline;">
                        <input type="hidden" name="rdv_id" value="' . $rdv->id . '">
                        <input type="hidden" name="action_type" value="terminer">
                        <button type="submit" class="button">Terminer</button>
                    </form>';
                    echo '<form method="post" style="display:inline;">
                    <input type="hidden" name="rdv_id" value="' . $rdv->id . '">
                    <input type="url" name="zoom_url" value="" placeholder="Entrez le lien Zoom" required>
                    <button type="submit" class="button">Envoyer le lien</button>
                  </form>';
            
            echo '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="7">Aucun rendez-vous trouvé.</td></tr>';
    }
}

function display_patients() {
    global $wpdb;
    $rendezvouss = $wpdb->prefix . 'rendezvouss';
    $psychologues = $wpdb->prefix . 'psychologues';
    $users = $wpdb->prefix . 'patients';
    $results = $wpdb->get_results("SELECT * FROM $users U WHERE U.user_id IN (SELECT user_id FROM $rendezvouss R , $psychologues P WHERE R.psychologue_id = P.id AND P.name = '" . wp_get_current_user()->display_name . "')");
    
    foreach ($results as $user) {
        echo '<tr>';
        echo '<td>' . $user->id . '</td>';
        echo '<td>' . $user->name . '</td>';
        echo '<td>' . $user->email . '</td>';
        echo '<td>' . $user->telephone . '</td>';
        echo '<td>' . $user->adresse . '</td>';
        echo '<td>' . $user->date_naissance . '</td>';
        echo '<td><button class="button view-details" data-id="' . $user->user_id . '">Dossier médical</button></td>';
        echo '</tr>';
    }
}

function terminee_actions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rendezvouss';

    if (isset($_POST['action_type']) && isset($_POST['rdv_id'])) {
        $action = sanitize_text_field($_POST['action_type']);
        $rdv_id = intval($_POST['rdv_id']);
        $msg = "";
        if ($action === 'terminer') {
            $wpdb->update($table_name, array('status' => 'Terminée'), array('id' => $rdv_id));
            $msg = '<div style="color: green;"><p>Le rendez-vous a été terminée.</p></div>';
        }
    }
    return $msg;
}

function scripts_psy() {
    wp_enqueue_script(
        'psychologue-script',
        get_stylesheet_directory_uri() . '/js/interface-psy.js',
        array(),
        null,
        true
    );

    wp_localize_script('psychologue-script', 'psychologue_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('psychologue_notes_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'scripts_psy');

add_action('wp_ajax_get_patient_dossier', 'get_patient_dossier_handler');
add_action('wp_ajax_save_patient_dossier', 'save_patient_dossier_handler');
function get_patient_dossier_handler() {
    check_ajax_referer('psychologue_notes_nonce', 'nonce');
    
    global $wpdb;
    $user_id = intval($_POST['user_id']);

    $psy = $wpdb->get_row($wpdb->prepare(
        "SELECT psychologue_id FROM {$wpdb->prefix}rendezvouss WHERE user_id = %d ORDER BY id DESC LIMIT 1",
        $user_id
    ));

    if (!$psy) {
        wp_send_json_error(array('message' => 'Psychologue introuvable pour ce patient.'));
    }

    $note = $wpdb->get_row($wpdb->prepare(
        "SELECT * 
         FROM {$wpdb->prefix}dossiers 
         WHERE id_patient = %d AND id_psy = %d 
         ORDER BY created_at DESC 
         LIMIT 1",
        $user_id, $psy->psychologue_id
    ));

    if (!$note) {
        $note = (object) array(
            'note' => '',
            'antecedents_Medicaux' => '',
            'consultations_et_Diagnostiques' => '',
            'traitements_en_Cours' => '',
            'examen_et_Resultats' => '',
            'created_at' => current_time('mysql')
        );
    }
    
    wp_send_json_success($note);
}

function save_patient_dossier_handler() {
    check_ajax_referer('psychologue_notes_nonce', 'nonce');
    
    global $wpdb;
    $user_id = intval($_POST['user_id']);
    $note_content = sanitize_textarea_field($_POST['note']);
    $antecedents = sanitize_textarea_field($_POST['antecedents_Medicaux']);
    $consultations = sanitize_textarea_field($_POST['consultations_et_Diagnostiques']);
    $traitements = sanitize_textarea_field($_POST['traitements_en_Cours']);
    $examens = sanitize_textarea_field($_POST['examen_et_Resultats']);
    
    $rdv = $wpdb->get_row($wpdb->prepare(
        "SELECT psychologue_id 
         FROM {$wpdb->prefix}rendezvouss 
         WHERE user_id = %d 
         ORDER BY id DESC LIMIT 1",
        $user_id
    ));

    if (!$rdv) {
        wp_send_json_error(array('message' => 'Rendez-vous introuvable pour ce patient.'));
    }

    $existing_note = $wpdb->get_row($wpdb->prepare(
        "SELECT * 
         FROM {$wpdb->prefix}dossiers 
         WHERE id_patient = %d AND id_psy = %d",
        $user_id, $rdv->psychologue_id
    ));

    if ($existing_note) {
        $result = $wpdb->update(
            $wpdb->prefix . 'dossiers',
            array(
                'note' => $note_content,
                'antecedents_Medicaux' => $antecedents,
                'consultations_et_Diagnostiques' => $consultations,
                'traitements_en_Cours' => $traitements,
                'examen_et_Resultats' => $examens,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $existing_note->id),
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    } else {
        $result = $wpdb->insert(
            $wpdb->prefix . 'dossiers',
            array(
                'id_patient' => (int) $user_id,
                'id_psy' => (int) $rdv->psychologue_id,
                'note' => $note_content,
                'antecedents_Medicaux' => $antecedents,
                'consultations_et_Diagnostiques' => $consultations,
                'traitements_en_Cours' => $traitements,
                'examen_et_Resultats' => $examens,
                'created_at' => current_time('mysql', 1) // UTC
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );        
    }

    if ($result === false) {
        wp_send_json_error(array('message' => 'Erreur lors de l\'enregistrement de la note.'));
    }
    wp_send_json_success(array('message' => 'Notes enregistrées avec succès.'));
}