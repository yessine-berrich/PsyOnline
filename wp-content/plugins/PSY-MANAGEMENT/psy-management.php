<?php
/*
Plugin Name: Psychologues Manager
Description: Plugin to manage psychologists
Version: 1.1
Author: yessine berrich

*/

defined('ABSPATH') or die('No script kiddies please!');

function display_psychologues_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'psychologues';

    if (isset($_POST['delete_psychologue']) && isset($_POST['psychologue_id'])) {
        $psychologue_id = intval($_POST['psychologue_id']);
        if (wp_verify_nonce($_POST['_wpnonce'], 'delete_psychologue_' . $psychologue_id)) {
            $wpdb->delete('wp_secretaires', array('psy_id' => $psychologue_id), array('%d'));
            $wpdb->delete($table_name, array('id' => $psychologue_id), array('%d'));
            echo '<div class="updated"><p>Psychologue supprimé avec succès!</p></div>';
        } else {
            echo '<div class="error"><p>Erreur de sécurité.</p></div>';
        }
    }

    $psychologues = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");

    ?>
    <div class="wrap">
        <h1>Gérer les psychologues</h1>

        <h2>Ajouter un nouveau psychologue</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('add_psychologue', 'psychologue_nonce'); ?>
            
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
                    <th><label for="type_consultation">Type de consultation:</label></th>
                    <td><input type="text" name="type_consultation" id="type_consultation" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="description">Description:</label></th>
                    <td><textarea name="description" id="description" rows="5" cols="50"></textarea></td>
                </tr>
                <tr>
                    <th><label for="profile_picture">Photo de profil:</label></th>
                    <td><input type="file" name="profile_picture" id="profile_picture" accept="image/*"></td>
                </tr>
            </table>
            
            <input type="submit" name="submit_psychologue" class="button button-primary" value="Ajouter">
        </form>

        <h2>Liste des psychologues</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Type de consultation</th>
                    <th>Description</th>
                    <th>Photo de profil</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($psychologues as $psychologue): ?>
                    <tr>
                        <td><?php echo esc_html($psychologue->name); ?></td>
                        <td><?php echo esc_html($psychologue->email); ?></td>
                        <td><?php echo esc_html($psychologue->type_consultation); ?></td>
                        <td><?php echo esc_html($psychologue->description); ?></td>
                        <td>
                            <?php if (!empty($psychologue->profile_picture)): ?>
                                <img src="<?php echo esc_url($psychologue->profile_picture); ?>" alt="Profile Picture" style="max-width: 100px; max-height: 100px;">
                            <?php else: ?>
                                Aucune photo
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post">
                                <?php wp_nonce_field('delete_psychologue_' . $psychologue->id); ?>
                                <input type="hidden" name="psychologue_id" value="<?php echo $psychologue->id; ?>">
                                <input type="submit" name="delete_psychologue" value="Supprimer" class="button button-secondary" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce psychologue ?');">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php

    if (isset($_POST['submit_psychologue'])) {
        handle_form_submission();
    }
}

function handle_form_submission() {
    if (!wp_verify_nonce($_POST['psychologue_nonce'], 'add_psychologue')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    
    $profile_picture_url = '';
    if (!empty($_FILES['profile_picture']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('profile_picture', 0);
        if (!is_wp_error($attachment_id)) {
            $profile_picture_url = wp_get_attachment_url($attachment_id);
        }
    }

    $user = get_user_by('email',sanitize_text_field($_POST['email']));
    if ($user) {
        $user->set_role('psychologue');
    }

    $wpdb->delete($wpdb->prefix . 'patients', array('email' => sanitize_email($_POST['email'])));

    $data = array(
        'name' => sanitize_text_field($_POST['name']),
        'type_consultation' => sanitize_text_field($_POST['type_consultation']),
        'description' => sanitize_textarea_field($_POST['description']),
        'profile_picture' => $profile_picture_url,
        'email' => sanitize_email($_POST['email']),
    );

    $result = $wpdb->insert(
        $wpdb->prefix . 'psychologues',
        $data,
        array('%s', '%s', '%s', '%s')
    );

    if ($result) {
        echo '<div class="updated"><p>Psychologue ajouté avec succès!</p></div>';
    } else {
        echo '<div class="error"><p>Erreur lors de lajout du psychologue.</p></div>';
    }
}

function add_psychologues_menu() {
    add_menu_page(
        'Gérer les Psychologues',
        'Psychologues',
        'manage_options',
        'psychologues-manager',
        'display_psychologues_page'
    );
}
add_action('admin_menu', 'add_psychologues_menu');