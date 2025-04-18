<?php
/*
Plugin Name: Secretaire Interface
Description: Interface pour les secrétaires pour gérer les rendez-vous et les patients.
Version: 1.0
Author: yessine berrich
*/

// Empêcher l'accès direct
defined('ABSPATH') or die('Accès direct interdit.');
// Ajouter le rôle "Secretaire"
function force_add_secretaire_role() {
    if (!get_role('secretaire')) {
        add_role(
            'secretaire',
            'Secretaire',
            array(
                'read' => true,
                'manage_appointments' => true,
                'edit_posts' => false,
            )
        );
    }
}
add_action('init', 'force_add_secretaire_role');

// Vérifier si l'utilisateur a le rôle "Secretaire"
function secretaire_check_role() {
    $current_user = wp_get_current_user();
    return in_array('secretaire', (array) $current_user->roles);
}

// Page de l'interface secrétaire
add_shortcode('secretaire_interface', 'secretaire_interface_page');
function secretaire_interface_page() {
    if (!secretaire_check_role()) {
        wp_die('Vous n’avez pas la permission d’accéder à cette page.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $msg = secretaire_handle_post_actions();
    }
    echo '<style>' . file_get_contents(plugin_dir_path(__FILE__) . 'secretaire.css') . '</style>';

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

    <div class="wrap">
        <h1>Interface Secretaire</h1>
        <div id="secretaire-interface">
            <?php 
            if ($msg) {
                echo $msg;
            }
            ?>
            <h2>Rendez-vous</h2>
            <table class="widefat fixed" style="text-align: center;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Statut</th>
                        <th>Paiement statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php secretaire_display_appointments(); ?>
                </tbody>
            </table>

            <h2>Patients</h2>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Adresse</th>
                        <th>Date de naissance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php secretaire_display_patients(); ?>
                </tbody>
            </table>

        </div>
    </div>
    <?php
}

// Afficher les rendez-vous
function secretaire_display_appointments() {
    global $wpdb;
    $rendezvouss = $wpdb->prefix . 'rendezvouss';
    $secretaires = $wpdb->prefix . 'secretaires';
    // $results = $wpdb->get_results("SELECT * FROM $table_name");
    $results = $wpdb->get_results("SELECT R.* FROM $rendezvouss R , $secretaires S WHERE R.psychologue_id = S.psy_id AND S.name = '" . wp_get_current_user()->display_name . "'");

    $types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}types_consultation");

    foreach ($results as $appointment) {
        echo '<tr>';
        echo '<td>' . $appointment->id . '</td>';
        echo '<td>' . get_userdata($appointment->user_id)->display_name . '</td>';
        echo '<td>' . $appointment->email . '</td>';
        echo '<td>' . $appointment->phone . '</td>';
        if ($types) {
            foreach ($types as $type) {
                if ($type->id == $appointment->id_type) {
                    echo '<td>' . $type->type_consultation . '</td>';
                    break;
                }
            }
        }
        echo '<td>' . $appointment->consultation_date . '</td>';
        echo '<td>' . $appointment->consultation_temps . '</td>';
        echo '<td>' . $appointment->status . '</td>';
        echo '<td>' . $appointment->paiement_status . '</td></tr>';
        echo '<tr colspan="9">
            <td colspan="1"><form method="post" style="display:inline;">
                <input type="hidden" name="appointment_id" value="' . $appointment->id . '">
                <input type="hidden" name="action_type" value="confirm">
                <button type="submit" class="button-confirm">Confirmer</button>
            </form></td>
            <td colspan="1"><form method="post" style="display:inline;">
                <input type="hidden" name="appointment_id" value="' . $appointment->id . '">
                <input type="hidden" name="action_type" value="annuler">
                <button type="submit" class="button-cancel">Annuler</button>
            </form></td>
            <td colspan="1"><form method="post" style="display:inline;">
                <input type="hidden" name="appointment_id" value="' . $appointment->id . '">
                <input type="hidden" name="action_type" value="delete">
                <button type="submit" class="button-delete">Supprimer</button>
            </form></td>
            <td colspan="3"><form method="post" style="display:inline;">
                <input type="hidden" name="appointment_id" value="' . $appointment->id . '">
                <input type="hidden" name="action_type" value="change_date">
                <input type="date" name="new_date" required>
                <button type="submit" class="button-modify">Modifier Date</button>
            </form></td>
            <td colspan="3"><form method="post" style="display:inline;">
                <input type="hidden" name="appointment_id" value="' . $appointment->id . '">
                <input type="hidden" name="action_type" value="change_time">
                <input type="time" name="new_time" required>
                <button type="submit" class="button-modify">Modifier Heure</button>
            </form></td>
        </tr>';
        echo '</tr>';
    }
}

// Afficher les patients
function secretaire_display_patients() {
    global $wpdb;
    $rendezvouss = $wpdb->prefix . 'rendezvouss';
    $secretaires = $wpdb->prefix . 'secretaires';
    $patients = $wpdb->prefix . 'patients';
    $results = $wpdb->get_results("SELECT * FROM $patients U WHERE U.user_id IN (SELECT user_id FROM $rendezvouss R , $secretaires S WHERE R.psychologue_id = S.psy_id AND S.name = '" . wp_get_current_user()->display_name . "')");
    
    foreach ($results as $user) {
        echo '<tr>';
        echo '<td>' . $user->id . '</td>';
        echo '<td>' . $user->name . '</td>';
        echo '<td>' . $user->email . '</td>';
        echo '<td>' . $user->telephone . '</td>';
        echo '<td>' . $user->adresse . '</td>';
        echo '<td>' . $user->date_naissance . '</td>';
        echo '</tr>';
    }
}

function secretaire_handle_post_actions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rendezvouss';
    $msg = "";
    if (isset($_POST['action_type']) && isset($_POST['appointment_id'])) {
        $action = sanitize_text_field($_POST['action_type']);
        $appointment_id = intval($_POST['appointment_id']);
        if ($action === 'confirm') {
            $wpdb->update($table_name, array('status' => 'Confirmée'), array('id' => $appointment_id));
            $msg = '<div style="color: green;"><p>Le rendez-vous a été confirmé.</p></div>';
        } elseif ($action === 'annuler') {
            $wpdb->update($table_name, array('status' => 'Annulée'), array('id' => $appointment_id));
            $msg = '<div style="color: green;"><p>Le rendez-vous a été annulée.</p></div>';
        } elseif ($action === 'change_date' && isset($_POST['new_date'])) {
            $new_date = sanitize_text_field($_POST['new_date']);
            $wpdb->update($table_name, array('consultation_date' => $new_date), array('id' => $appointment_id));
            $msg = '<div style="color: green;"><p>La date du rendez-vous a été modifiée.</p></div>';
        } elseif ($action === 'change_time' && isset($_POST['new_time'])) {
            $new_time = sanitize_text_field($_POST['new_time']);
            $wpdb->update($table_name, array('consultation_temps' => $new_time), array('id' => $appointment_id));
            $msg = '<div style="color: green;"><p>L’heure du rendez-vous a été modifiée.</p></div>';
        }elseif ($action === 'delete') {
            $wpdb->delete($table_name, array('id' => $appointment_id));
            $msg = '<div style="color: green;"><p>Le rendez-vous a été supprimée.</p></div>';
        }
    }
    return $msg;
}
?>
