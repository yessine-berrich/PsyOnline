<?php
/**
 * Plugin Name: Signup
 * Description: Un plugin pour créer un formulaire d'inscription avancé.
 * Version: 1.0
 * Author: yessine berrich
 */

if (!defined('ABSPATH')) {
    exit;
}

function add_patient_role() {
    add_role(
        'patient',
        'Patient',
        array(
            'read' => true,
        )
    );
}
add_action('init', 'add_patient_role');

function signup_form() {
    ob_start();
    $message = "";
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $dob = sanitize_text_field($_POST['dob']);
        $country_code = sanitize_text_field($_POST['country_code']);
        $phone_number = sanitize_text_field($_POST['phone_number']);
        $city = sanitize_text_field($_POST['city']);
        $street = sanitize_text_field($_POST['street']);

        if (!validate_phone_number($country_code, $phone_number)) {
            $message = '<p style="color: red; text-align: center;">Numéro de téléphone invalide pour le pays sélectionné.</p>';
        } else {
            $phone = $country_code . $phone_number;

            if (!email_exists($email)) {
                $user_id = wp_insert_user(array(
                    'user_login' => $email,
                    'user_email' => $email,
                    'user_pass'  => $password,
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                ));

                if (!is_wp_error($user_id)) {
                    $user = new WP_User($user_id);
                    $user->set_role('patient');

                    global $wpdb;
                    $wpdb->update(
                        $wpdb->users,
                        array('role' => 'patient'),
                        array('ID' => $user_id)
                    );
                    
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'patients';
                    $wpdb->insert(
                        $table_name,
                        array(
                            'user_id'       => $user_id,
                            'name'          => $first_name . ' ' . $last_name,
                            'date_naissance'=> $dob,
                            'telephone'     => $phone,
                            'email'         => $email,
                            'adresse'       => $street . ', ' . $city
                        ),
                        
                        array('%s', '%s', '%s', '%s', '%s')
                    );

                    $message = '<p style="color: green; text-align: center;" >Inscription réussie ! Vous pouvez maintenant <a style="color: green" href="' . esc_url('http://localhost/psyonline01/se-connecter/') . '">vous connecter</a>.</p>';
                } else {
                    $message = '<p style="color: red; text-align: center;">Erreur lors de linscription. Veuillez réessayer.</p>';
                }
            } else {
                $message = '<p style="color: red; text-align: center;">Ladresse email est déjà utilisée !</p>';
            }
        }
    }

    ?>
    <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
        <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 500px; width: 100%;">
            <h2 style="text-align: center; margin-bottom: 20px; color: #0073aa;">Inscription</h2>
            <?php if ($message) : ?>
                <?php echo $message; ?>
            <?php endif; ?>
            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="POST">
                <input type="text" placeholder="Nom" name="first_name" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <input type="text" placeholder="Prénom" name="last_name" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <input type="email" placeholder="Adresse Email" name="email" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <input type="password" placeholder="Mot de passe" name="password" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <label for="dob" style="margin: 10px 0; display: block;">Date de naissance :</label>
                <input type="date" name="dob" id="dob" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <label style="margin: 10px 0; display: block;">Téléphone :</label>
                <select name="country_code" required style="width: 30%; padding: 10px; margin-right: 5%; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="+33">France (+33)</option>
                    <option value="+32">Belgique (+32)</option>
                    <option value="+41">Suisse (+41)</option>
                    <option value="+1">Canada (+1)</option>
                    <option value="+352">Luxembourg (+352)</option>
                    <option value="+377">Monaco (+377)</option>
                </select>
                <input type="text" name="phone_number" placeholder="Numéro" required title="Entrez un numéro de téléphone valide." style="width: 65%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <input type="text" placeholder="Ville" name="city" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <input type="text" placeholder="Cité" name="street" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <button type="submit" name="signup" style="width: 100%; padding: 10px; background-color: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s;">S'inscrire</button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('signup_form', 'signup_form');


function validate_phone_number($country_code, $phone_number) {
    $patterns = array(
        '+33'  => '/^\d{9}$/', // France (9 chiffres )
        '+32'  => '/^\d{9}$/', // Belgique (9 chiffres )
        '+41'  => '/^\d{9}$/', // Suisse (9 chiffres )
        '+1'   => '/^\d{10}$/',// Canada (10 chiffres )
        '+352' => '/^\d{8}$/', // Luxembourg (8 chiffres )
        '+377' => '/^\d{8}$/', // Monaco (8 chiffres )
    );

    if (isset($patterns[$country_code])) {
        if (preg_match($patterns[$country_code], $phone_number)) {
            return true;
        } else {
            return false;
        }
    }
    
    return false;
}
