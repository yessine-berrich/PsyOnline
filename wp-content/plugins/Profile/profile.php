<?php
/*
Plugin Name: profil et mot de passe
Description: Un plugin WordPress permettant aux utilisateurs de gérer leur profil et changer leur mot de passe.
Version: 1.2
Author: yessine berrich
*/

if (!defined('ABSPATH')) {
    exit;
}

function user_profile_password_form() {
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour gérer votre profil.</p>';
    }
    if (isset($_POST['update_profile'])) {
        $msg = update_user_profile();
    }
    
    if (isset($_POST['change_password'])) {
        $msg = change_user_password();
    }
    $user = wp_get_current_user();
    ob_start();
    echo '<style>' . file_get_contents(plugin_dir_path(__FILE__) . 'profile.css') . '</style>';

    ?>
    <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 500px; width: 100%;">
    <form method="post">
        <h2 style="text-align: center; margin-bottom: 20px; color: #0073aa;">Mettre à jour le profil</h2>
        <?php
        if ($msg && isset($_POST['update_profile'])) {
            echo $msg;
            ?><?php

        }
        ?>
        <label for="user_email">Email :</label>
        <input type="email" name="user_email" value="<?php echo esc_attr($user->user_email); ?>" required><br>
        
        <label for="display_name">Nom affiché :</label>
        <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" required><br>
        
        <input type="submit" name="update_profile" value="Mettre à jour le profil" style=" width: 100%; padding: 10px; background-color: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; ">
    </form>
    <form method="post" style="margin-top: 20px;">
        <h2 style="text-align: center; margin-bottom: 20px; color: #0073aa;">Changer le mot de passe</h2>
        <?php
        if ($msg && isset($_POST['change_password'])) {
            echo $msg;
            ?><?php

        }
        ?>
        <label for="current_password">Mot de passe actuel :</label>
        <input type="password" name="current_password" required><br>
        
        <label for="new_password">Nouveau mot de passe :</label>
        <input type="password" name="new_password" required><br>
        
        <label for="confirm_password">Confirmer le mot de passe :</label>
        <input type="password" name="confirm_password" required><br>
        
        <input type="submit" name="change_password" value="Changer le mot de passe" style=" width: 100%; padding: 10px; background-color: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; ">
    </form>
    </div>
    </div>
    <?php
    
    
    
    return ob_get_clean();
}
add_shortcode('user_profile', 'user_profile_password_form');

function update_user_profile() {
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $email = sanitize_email($_POST['user_email']);
    $display_name = sanitize_text_field($_POST['display_name']);
    $msg = '<p style="color: green; padding: 10px; margin-bottom: 20px; text-align: center;">Profil mis à jour avec succès.</p>';
    if (!is_email($email)) {
        $msg = '<p style="color: red; padding: 10px; margin-bottom: 20px; text-align: center;">Adresse email invalide.</p>';
        return $msg;
    }

    wp_update_user([
        'ID' => $user_id,
        'user_email' => $email,
        'display_name' => $display_name
    ]);

    return $msg;
}

function change_user_password() {
    if (!is_user_logged_in()) {
        return;
    }

    $user = wp_get_current_user();
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $msg = '<p style="color: green; padding: 10px; margin-bottom: 20px; text-align: center;">Votre mot de passe a été changé avec succès. Veuillez vous reconnecter.</p>';
    if (!wp_check_password($current_password, $user->user_pass, $user->ID)) {
        $msg = '<p style="color: red; padding: 10px; margin-bottom: 20px; text-align: center;">Mot de passe actuel incorrect.</p>';
        return $msg;
    }

    if ($new_password !== $confirm_password) {
        $msg = '<p style="color: red; padding: 10px; margin-bottom: 20px; text-align: center;">Les nouveaux mots de passe ne correspondent pas.</p>';
        return $msg;
    }

    wp_set_password($new_password, $user->ID);
    // wp_logout();
    return $msg;
}
?>
