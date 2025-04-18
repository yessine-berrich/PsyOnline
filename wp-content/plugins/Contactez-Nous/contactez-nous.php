<?php
/**
 * Plugin Name: Contact
 * Description: Un plugin pour créer un formulaire de contact et enregistrer les messages dans la base de données.
 * Version: 1.0
 * Author: Yessine Berrich
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function contact_form() {
    ob_start();

    if (isset($_POST['submit_btn'])) {
        ajouter_message();
    }

    ?>
    <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
        <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 500px; width: 100%;">
            <h2 style="text-align: center; margin-bottom: 20px; color: #0073aa;">Contactez-nous</h2>
            <?php if (isset($_POST['submit_btn'])) : ?>
                <p style="color: green; text-align: center;">Votre message a été envoyé avec succès!</p>
            <?php endif; ?>
            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="POST" name="contact_form">
                <label for="name" style="display: block; margin-bottom: 5px;">Nom :</label>
                <input type="text" id="name" name="cf_name" placeholder="Votre nom" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">

                <label for="email" style="display: block; margin-bottom: 5px;">Email :</label>
                <input type="email" id="email" name="cf_email" placeholder="Votre e-mail" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">

                <label for="message" style="display: block; margin-bottom: 5px;">Message :</label>
                <textarea id="message" name="cf_message" rows="4" placeholder="Votre message" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>

                <button type="submit" name="submit_btn" style="width: 100%; padding: 10px; background-color: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s;">Envoyer</button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('contact_form', 'contact_form');

function ajouter_message() {
    global $wpdb;

    $name = sanitize_text_field($_POST['cf_name']);
    $email = sanitize_email($_POST['cf_email']);
    $message = sanitize_textarea_field($_POST['cf_message']);
    $date_envoi = current_time('mysql');

    $table_name = $wpdb->prefix . 'messages_contact';
    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'date_envoi' => $date_envoi,
        )
    );
}
