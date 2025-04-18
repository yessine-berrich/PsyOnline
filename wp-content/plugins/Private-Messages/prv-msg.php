<?php
/**
 * Plugin Name: Private Messages
 * Description: Un plugin pour gérer des messages privés entre utilisateurs dans le tableau de bord WordPress.
 * Version: 1.0
 * Author: yessine berrich
 */

add_shortcode('display_messages', 'display_messages');
function display_messages() {
    if ( !is_user_logged_in() ) {
        echo 'Vous devez être connecté pour voir vos messages.';
        return;
    }

    $current_user = wp_get_current_user();
    global $wpdb;
    $table_name = $wpdb->prefix . 'private_messages';
    $message_sent = false;

    if (isset($_POST['send_message'])) {
        $receiver_id = intval($_POST['receiver_id']);
        $message = sanitize_textarea_field($_POST['message']);

        $wpdb->insert(
            $table_name,
            array(
                'sender_id' => $current_user->ID,
                'receiver_id' => $receiver_id,
                'message' => $message,
                'date_sent' => current_time('mysql'),
            )
        );
        $message_sent = true;
    }

    $received_messages = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE receiver_id = %d ORDER BY date_sent DESC LIMIT 5", $current_user->ID)
    );

    $sent_messages = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE sender_id = %d ORDER BY date_sent DESC LIMIT 5", $current_user->ID)
    );
    echo '<style>' . file_get_contents(plugin_dir_path(__FILE__) . 'style.css') . '</style>';

    ?>
    <div class="wrap">
        <h1>Boîte de Réception des Messages Privés</h1>

        <?php
        if ($message_sent) {
            echo '<div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center;"><p>Votre message a été envoyé avec succès.</p></div>';
        }
        ?>

        <h2>Messages Reçus</h2>
        <?php
        if (empty($received_messages)) {
            echo 'Aucun message reçu.';
        } else {
            echo '<table class="messages">
                <thead>
                    <tr>
                        <th>De</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($received_messages as $message) {
                $sender = get_userdata($message->sender_id);
                echo '<tr>';
                echo '<td>' . esc_html($sender->display_name) . '</td>';
                echo '<td>' . esc_html($message->message) . '</td>';
                echo '<td>' . esc_html($message->date_sent) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        ?>

        <h2>Messages Envoyés</h2>
        <?php
        if (empty($sent_messages)) {
            echo 'Aucun message envoyé.';
        } else {
            echo '<table class="wp-list-table widefat fixed striped messages">
                <thead>
                    <tr>
                        <th>À</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($sent_messages as $message) {
                $receiver = get_userdata($message->receiver_id);
                echo '<tr>';
                echo '<td>' . esc_html($receiver->display_name) . '</td>';
                echo '<td>' . esc_html($message->message) . '</td>';
                echo '<td>' . esc_html($message->date_sent) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        ?>

        <h2>Envoyer un Message Privé</h2>
        <form method="post">
            <label for="receiver_id">ID du destinataire :</label>
            <select name="receiver_id" id="receiver_id" required>
                <option value="0">Choisir un destinataire</option>
                <?php
                contacts();
                ?>
            </select>

            <label for="message">Message :</label>
            <textarea name="message" id="message" required></textarea>
            <button type="submit" name="send_message">Envoyer</button>
        </form>
    </div>
    <?php    
}

function contacts () {
    global $wpdb;
    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    $role = !empty($roles) ? $roles[0] : '';
    if ($role === 'psychologue') {
        $psy = $wpdb->get_row(
            $wpdb->prepare("SELECT id, name FROM wp_psychologues WHERE name = %s", $user->display_name)
        );
        if ($psy) {
            $sec = $wpdb->get_row(
                $wpdb->prepare("SELECT id, name FROM wp_secretaires WHERE psy_id = %d", $psy->id)
            );
            if ($sec) {
                $user_ID = $wpdb->get_row(
                    $wpdb->prepare("SELECT ID FROM wp_users WHERE display_name = %s", $sec->name)
                );
                if ($user_ID) {
                    echo '<option value="' . esc_attr($user_ID->ID) . '">' . esc_html($sec->name) . '</option>';
                }
            }

            $patients = $wpdb->get_results(
                $wpdb->prepare("SELECT ID, display_name FROM wp_users WHERE ID IN (SELECT user_id FROM wp_rendezvouss WHERE psychologue_id = %d)", $psy->id)
            );
            if (!empty($patients)) {
                foreach ($patients as $patient) {
                    echo '<option value="' . esc_attr($patient->ID) . '">' . esc_html($patient->display_name) . '</option>';
                }
            }
        }
    } elseif ($role === 'secretaire') {
        $sec = $wpdb->get_row(
            $wpdb->prepare("SELECT id, psy_id FROM wp_secretaires WHERE name = %s", $user->display_name)
        );
        if ($sec) {
            $psy = $wpdb->get_row(
                $wpdb->prepare("SELECT id, name FROM wp_psychologues WHERE id = %d", $sec->psy_id)
            );
            if ($psy) {
                $user_ID = $wpdb->get_row(
                    $wpdb->prepare("SELECT ID FROM wp_users WHERE display_name = %s", $psy->name)
                );
                if ($user_ID) {
                    echo '<option value="' . esc_attr($user_ID->ID) . '">' . esc_html($psy->name) . '</option>';
                }
            }

            $patients = $wpdb->get_results(
                $wpdb->prepare("SELECT ID, display_name FROM wp_users WHERE ID IN (SELECT user_id FROM wp_rendezvouss WHERE psychologue_id = %d)", $sec->psy_id)
            );
            if (!empty($patients)) {
                foreach ($patients as $patient) {
                    echo '<option value="' . esc_attr($patient->ID) . '">' . esc_html($patient->display_name) . '</option>';
                }
            }
        }
    } elseif ($role === 'patient') {
        // $secretaires = $wpdb->get_results(
        //     $wpdb->prepare("SELECT DISTINCT U.ID, U.display_name
        //         FROM wp_users U
        //         INNER JOIN wp_secretaires S ON U.display_name = S.name
        //         INNER JOIN wp_psychologues P ON P.id = S.psy_id
        //         INNER JOIN wp_rendezvouss R ON R.psychologue_id = P.id
        //         WHERE R.user_id = %d
        //     ", $user->ID)
        // );


        $secretaires = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT U.ID, U.display_name
                FROM wp_users U
                WHERE EXISTS (
                    SELECT 1
                    FROM wp_secretaires S
                    WHERE U.display_name = S.name
                    AND EXISTS (
                        SELECT 1
                        FROM wp_psychologues P
                        WHERE P.id = S.psy_id
                        AND EXISTS (
                            SELECT 1
                            FROM wp_rendezvouss R
                            WHERE R.psychologue_id = P.id
                            AND R.user_id = %d
                        )
                    )
                )",
                $user->ID
            )
        );
        


        if (!empty($secretaires)) {
            foreach ($secretaires as $secretaire) {
                echo '<option value="' . esc_attr($secretaire->ID) . '">' . esc_html($secretaire->display_name) . '</option>';
            }
        }
        // $psychologues = $wpdb->get_results(
        //     $wpdb->prepare("
        //         SELECT DISTINCT U.ID, U.display_name
        //         FROM wp_users U
        //         INNER JOIN wp_psychologues P ON U.display_name = P.name
        //         INNER JOIN wp_rendezvouss R ON P.id = R.psychologue_id
        //         WHERE R.user_id = %d
        //     ", $user->ID)
        // );

        $psychologues = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT U.ID, U.display_name
                FROM wp_users U
                WHERE EXISTS (
                    SELECT 1
                    FROM wp_psychologues P
                    WHERE U.display_name = P.name
                    AND EXISTS (
                        SELECT 1
                        FROM wp_rendezvouss R
                        WHERE R.psychologue_id = P.id
                        AND R.user_id = %d
                    )
                )",
                $user->ID
            )
        );
        

        if (!empty($psychologues)) {
            foreach ($psychologues as $psychologue) {
                echo '<option value="' . esc_attr($psychologue->ID) . '">' . esc_html($psychologue->display_name) . '</option>';
            }
        }
    }
}