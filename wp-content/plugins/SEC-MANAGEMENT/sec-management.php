<?php
/*
Plugin Name: Secretaries Manager
Description: Plugin to manage secretaries and link them to psychologists
Version: 1.0
Author: yessine berrich
*/

defined('ABSPATH') or die('No script kiddies please!');

function display_secretaries_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'secretaires';
    $psychologues_table = $wpdb->prefix . 'psychologues';

    if (isset($_POST['delete_secretary']) && isset($_POST['secretary_id'])) {
        $secretary_id = intval($_POST['secretary_id']);
        if (wp_verify_nonce($_POST['_wpnonce'], 'delete_secretary_' . $secretary_id)) {
            $wpdb->delete($table_name, array('id' => $secretary_id), array('%d'));
            echo '<div class="updated"><p>Secrétaire supprimé(e) avec succès!</p></div>';
        }
    }

    $secretaries = $wpdb->get_results("
        SELECT s.*, p.name as psychologist_name 
        FROM $table_name s 
        LEFT JOIN $psychologues_table p ON s.psy_id = p.id 
        ORDER BY s.name ASC
    ");

    $psychologists = $wpdb->get_results("SELECT id, name FROM $psychologues_table ORDER BY name ASC");

    ?>
    <div class="wrap">
        <h1>Gérer les secrétaires</h1>

        <h2>Ajouter un(e) nouveau/nouvelle secrétaire</h2>
        <form method="post">
            <?php wp_nonce_field('add_secretary', 'secretary_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="name">Nom:</label></th>
                    <td><input type="text" name="name" id="name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="email">Email:</label></th>
                    <td><input type="email" name="email" id="email" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="psy_id">Psychologue:</label></th>
                    <td>
                        <select name="psy_id" id="psy_id" class="regular-text" required>
                            <option value="">Sélectionner un psychologue</option>
                            <?php foreach ($psychologists as $psy): ?>
                                <option value="<?php echo esc_attr($psy->id); ?>">
                                    <?php echo esc_html($psy->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <input type="submit" name="submit_secretary" class="button button-primary" value="Ajouter">
        </form>

        <h2>Liste des secrétaires</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Psychologue assigné</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($secretaries as $secretary): ?>
                    <tr>
                        <td><?php echo esc_html($secretary->name); ?></td>
                        <td><?php echo esc_html($secretary->email); ?></td>
                        <td><?php echo esc_html($secretary->psychologist_name); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('delete_secretary_' . $secretary->id); ?>
                                <input type="hidden" name="secretary_id" value="<?php echo $secretary->id; ?>">
                                <input type="submit" name="delete_secretary" value="Supprimer" 
                                       class="button button-secondary" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette secrétaire ?');">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php

    if (isset($_POST['submit_secretary'])) {
        handle_secretary_submission();
    }
}

function handle_secretary_submission() {
    if (!wp_verify_nonce($_POST['secretary_nonce'], 'add_secretary')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    

    $user = get_user_by('email',sanitize_text_field($_POST['email']));
    if ($user) {
        $user->set_role('secretaire');
    }

    $wpdb->delete($wpdb->prefix . 'patients', array('email' => sanitize_email($_POST['email'])));


    $data = array(
        'name' => sanitize_text_field($_POST['name']),
        'psy_id' => intval($_POST['psy_id']),
        'email' => sanitize_email($_POST['email']),
    );
    
    $psy_exist = $wpdb->get_results("SELECT psy_id FROM wp_secretaires where psy_id = ".$data['psy_id']);
    if (count($psy_exist) > 0) {
        echo '<div class="error"><p>Ce psychologue a déjà un(e) secrétaire!</p></div>';
    } else {
        $result = $wpdb->insert(
            $wpdb->prefix . 'secretaires',
            $data,
            array('%s', '%d')
        );
    
        if ($result) {
            echo '<div class="updated"><p>Secrétaire ajouté(e) avec succès!</p></div>';
        } else {
            echo '<div class="error"><p>Erreur lors de l\'ajout de la secrétaire.</p></div>';
        }
    }
}

function add_secretaries_menu() {
    add_menu_page(
        'Gérer les Secrétaires',
        'Secrétaires',
        'manage_options',
        'secretaries-manager',
        'display_secretaries_page',
    );
}
add_action('admin_menu', 'add_secretaries_menu');