<?php
/**
 * Plugin Name: Rendez-vous et Paiement
 * Description: Prendre un rendez-vous avec un psycholoque et effectuer le paiement.
 * Version: 1.0
 * Author: Yessine Berrich
 */

if (!defined('ABSPATH')) {
    exit;
}

require plugin_dir_path(__FILE__) . 'paiement.php';

add_shortcode('rdv_form', 'psy_rendezvous_booking_form');
function psy_rendezvous_booking_form() {
    if (!is_user_logged_in()) {
        return '<h1 style="text-align: center; display: flex; justify-content: center; align-items: center; min-height: 300px; padding: 20px; margin: 50px 0;">
        Vous devez <a href="' . esc_url("http://localhost/psyonline01/se-connecter/") . '" style="text-decoration: none; margin: 0 10px;">vous connecter</a> pour prendre un rendez-vous.
    </h1>';
        }
    $message = "";
    $choix = false;
    global $wpdb;
    $psychologues = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}psychologues");
    $types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}types_consultation");
    if (isset($_POST['submit_rendezvous'])) {
        $psychologue_id = intval($_POST['psychologue']);
        $id_type = sanitize_text_field($_POST['id_type']);
        $email = sanitize_text_field($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $consultation_date = sanitize_text_field($_POST['consultation_date']);
        $consultation_temps = sanitize_text_field($_POST['consultation_temps']);
        $user_id = get_current_user_id();

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        if (strtotime($consultation_date) < strtotime($tomorrow)) {
            $message = '<p style="color: red; text-align: center;">La date de consultation doit être égale ou supérieure à la date d\'aujourd\'hui.</p>';
        } else {
            $existing_rendezvous = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rendezvouss WHERE psychologue_id = %d AND consultation_date = %s AND consultation_temps = %s",
                $psychologue_id, $consultation_date, $consultation_temps
            ));

            if ($existing_rendezvous > 0) {
                $message = '<p style="color: red; text-align: center;">Ce créneau est déjà réservé. Veuillez choisir un autre créneau.</p>';
            } else {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'rendezvouss',
                    [
                        'user_id' => $user_id,
                        'psychologue_id' => $psychologue_id,
                        'id_type' => $id_type,
                        'consultation_date' => $consultation_date,
                        'consultation_temps' => $consultation_temps,
                        'email' => $email,
                        'phone' => $phone,
                        'paiement_status' => 'Non payée'
                    ]
                );
                
                if ($result === false) {
                    $message = '<p style="color: red; text-align: center;">Une erreur est survenue lors de la prise de rendez-vous. Veuillez réessayer plus tard.</p>';
                } else {
                    $message = '<p style="color: green; text-align: center;">Rendez-vous pris avec succès. Choisissez une option ci-dessous :</p>';
                    $choix = true;
                }
            }
        }
    }
    ob_start();
    echo '<style>' . file_get_contents(plugin_dir_path(__FILE__) . 'css/rendezvous.css') . '</style>';

    ?>
    <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 500px; width: 100%;">
    <h2 style="text-align: center; margin-bottom: 20px; color: #0073aa;">Rendez-vous</h2>
    <?php if ($message) : 
        echo $message;
             endif; 
            if ($choix) : ?>
                <div style="text-align: center;">
                    <a style="text-decoration: none; color: blue; text-align: center;" href="<?php echo esc_url(add_query_arg('rendezvous_id', $wpdb->insert_id, site_url('/paiement'))) ?>">Procéder au paiement</a><br>
                    <a style="text-decoration: none; color: blue; text-align: center;" href="<?php echo  esc_url("http://localhost/psyonline01/") ?> ">Payer plus tard</a>
                </div>
                    <?php endif; ?>
    <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
            <label for="psychologue">Selectionner le psychologue:</label>
            <select name="psychologue" id="psychologue" required>
                <?php foreach ($psychologues as $psy) : ?>
                    <option value="<?php echo $psy->id; ?>"><?php echo esc_html($psy->name); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="consultation_type">Type de la consultation:</label>
            <select name="id_type" id="id_type" required>
                <?php foreach ($types as $type) : ?>
                    <option value="<?php echo $type->id; ?>"><?php echo esc_html($type->type_consultation); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="email">Email :</label>
            <input type="email" id="email" name="email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
            <?php
            $current_user_id = get_current_user_id();
            $phone_number = $wpdb->get_var($wpdb->prepare("SELECT telephone FROM {$wpdb->prefix}patients WHERE user_id = %d", $current_user_id));
            if ($phone_number === null) {
                $phone_number = ''; 
            }
            ?>
            <label for="phone">Téléphone</label>
            <input type="text" id="phone" name="phone" value="<?php echo esc_html($phone_number) ?>" placeholder="Votre numéro de téléohone" required>
            <label for="consultation_date">Date:</label>
            <input type="date" id="consultation_date" name="consultation_date" value="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>">
            
            <label for="consultation_temps">Heure :</label>
            <select id="consultation_temps" name="consultation_temps">
                <option value="09:00:00">09:00</option>
                <option value="10:00:00">10:00</option>
                <option value="11:00:00">11:00</option>
                <option value="13:00:00">13:00</option>
                <option value="14:00:00">14:00</option>
                <option value="15:00:00">15:00</option>
                <option value="16:00:00">16:00</option>
                <option value="17:00:00">17:00</option>
            </select>
            <button type="submit" name="submit_rendezvous">Confirmer</button>
        </form>
    </div>
    </div>
    <?php
    

    

    return ob_get_clean();
}
