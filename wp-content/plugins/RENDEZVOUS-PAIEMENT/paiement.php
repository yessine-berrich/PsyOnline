<?php

add_shortcode('consultation_list', 'psy_paiement_consultations_page');
function psy_paiement_consultations_page() {
    if (!is_user_logged_in()) {
        return '<p>Vous devez <a href="' . wp_login_url(get_permalink("/se-connecter")) . '">vous connecter</a> pour voir cette page.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();

    if (isset($_GET['delete_rendezvous_id'])) {
        $delete_id = intval($_GET['delete_rendezvous_id']);
        $wpdb->delete("{$wpdb->prefix}rendezvouss", ['id' => $delete_id, 'user_id' => $user_id]);
        echo '<p>Le rendez-vous a été supprimé avec succès.</p>';
    }

    $rendezvouss = $wpdb->get_results($wpdb->prepare(
        "SELECT R.*, P.name AS psychologue_name 
         FROM {$wpdb->prefix}rendezvouss R
         JOIN {$wpdb->prefix}psychologues P 
         ON R.psychologue_id = P.id
         WHERE R.user_id = %d",
        $user_id
    )); 
    if (empty($rendezvouss)) { 
        return '<h1 style="display: flex; justify-content: center; align-items: center; min-height: 300px; text-align: center;">
        <a style="text-decoration: none; color: green; margin-right: 10px;" href="' . esc_url('http://localhost/psyonline01/rendez-vous/') . '">Cliquez ici</a> 
        pour prendre un rendez-vous.
    </h1>';
    }
    $consultations = $wpdb->get_results($wpdb->prepare(
        "SELECT C.*, P.name AS psychologue_name 
         FROM {$wpdb->prefix}consultations C
         JOIN {$wpdb->prefix}psychologues P 
         ON C.id_psy = P.id
         WHERE C.id_patient = %d ORDER BY C.id DESC LIMIT 1",
        $user_id
    ));


    foreach ($rendezvouss as $rendezvous) {
        if ($rendezvous->status === 'En cours...') {
            foreach ($consultations as $consultation) {
                echo '<p class="consult">Dr. ' . esc_html($consultation->psychologue_name) . ' t’attend, <a style="text-decoration: none;color:green;" class="lien_consult" href="'. esc_html($consultation->zoom_link) .'" target="_blank">cliquez ici</a> pour rejoindre la consultation</p>';
            }        
        }
    }
    ob_start();
    echo '<style>' . file_get_contents(plugin_dir_path(__FILE__) . 'css/consultations.css') . '</style>';
    ?>
    <style>
        .consult {
            font-size: 18px; 
            font-weight: bold; 
            color: #333; 
            margin: 20px 0; 
            padding: 10px; 
            border-left: 5px solid #4CAF50; 
            background-color: #f9f9f9; 
            border-radius: 5px; 
        }

        .lien_consult{
            color: #4CAF50; 
            text-decoration: none; 
            font-weight: bold; 
            transition: background-color 0.3s, color 0.3s; 
        }

        .lien_consult:hover {
            color: red;
        }

    </style>
    <div class="paiement">
    <h2>Vos rendez-vous</h2>
    <table>
        <tr>
            <th>Psychologue</th>
            <th>Type</th>
            <th>Date</th>
            <th>Heure</th>
            <th>Prix</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($rendezvouss as $rendezvous) : ?>
            <tr>
                <td><?php echo esc_html($rendezvous->psychologue_name); ?></td>
                <td><?php 
                    $type = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}types_consultation WHERE id = %d",
                        $rendezvous->id_type
                    ));
                    echo esc_html($type->type_consultation); ?></td>
                <td><?php echo esc_html($rendezvous->consultation_date); ?></td>
                <td><?php echo esc_html($rendezvous->consultation_temps); ?></td>
                <td><?php 
                    $type = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}types_consultation WHERE id = %d",
                        $rendezvous->id_type
                    ));
                    echo esc_html($type->prix); ?>TND</td>
                <td><?php echo esc_html($rendezvous->status); ?></td>
                <td>
                    <?php if ($rendezvous->paiement_status === 'Non payée') : ?>
                        <a style="text-decoration: none;" href="<?php echo esc_url(add_query_arg('rendezvous_id', $rendezvous->id, site_url('/paiement'))); ?>">Payer maintenant</a>
                        <a style="text-decoration: none;" href="<?php echo esc_url(add_query_arg('delete_rendezvous_id', $rendezvous->id)); ?>" 
                        onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');">Annuler</a>
                        <?php else : ?>
                            ✔️payée
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('psy_paiement_interface', 'psy_paiement_interface');
function psy_paiement_interface() {
    if (!is_user_logged_in()) {
        return '<p>You must <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access the paiement interface.</p>';
    }

    global $wpdb;
    $rendezvous_id = intval($_GET['rendezvous_id']);

    $rendezvous = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rendezvouss WHERE id = %d",
        $rendezvous_id
    ));

    $psy = $wpdb->get_row($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}psychologues WHERE id = %d",
        $rendezvous->psychologue_id
    ));

    $type = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}types_consultation WHERE id = %d",
        $rendezvous->id_type
    ));

    if (!$rendezvous) {
        return '<p>Rendez-vous invalid.</p>';
    }

    ob_start();
    echo '<style>' . file_get_contents(plugin_dir_path(__FILE__) . 'css/paiement.css') . '</style>';
    $msg = false;
    if (isset($_POST['submit_paiement'])) {
        $card_number = sanitize_text_field($_POST['card_number']);
        $expiry_date = sanitize_text_field($_POST['expiry_date']);
        $cvv = sanitize_text_field($_POST['cvv']);

        $wpdb->update(
            $wpdb->prefix . 'rendezvouss',
            [
                'paiement_status' => 'payée',
                'paiement_details' => json_encode([
                    'numcarte' => $card_number,
                    'date_expiration' => $expiry_date,
                    'cvv' => $cvv
                ])
            ],
            ['id' => $rendezvous_id]
        );
        $msg = true;
    }
    ?>
    <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    <div class="paiement-page">
    <h2 style="text-align: center; margin-bottom: 20px; color: #0073aa;">Paiement</h2>
    <?php
        if ($msg) {
            echo '<div style="color: green; padding: 10px; margin-bottom: 20px; text-align: center;"><p>Paiement effectué avec succès.</p><a style="text-decoration: none; color: blue; text-align: center;" href="'.  esc_url("http://localhost/psyonline01/vos-rendezvous/") .' ">Vos rendezvous</a></div>';
            ?><?php

        }
        ?>
    <p><span>Psychologue: </span><?php echo esc_html($psy->name); ?></p>
    <p><span>Consultation Type: </span><?php echo esc_html($type->type_consultation); ?></p>
    <p><span>Date: </span><?php echo esc_html($rendezvous->consultation_date); ?></p>
    <p><span>Temps: </span><?php echo esc_html($rendezvous->consultation_temps); ?></p>
    <p><span>Prix: </span><?php echo esc_html($type->prix); ?></p>
    <form method="post" onsubmit="return validerPaiement()">
    <input type="text" name="card_number" id="card_number" placeholder="Numéro de la carte" maxlength="16" required>

    <input type="text" name="expiry_date" id="expiry_date" placeholder="Date d'expiration (MM/YY)" maxlength="5" required>

    <input type="text" name="cvv" id="cvv" placeholder="CVV" maxlength="3" required>

    <button type="submit" name="submit_paiement">Payer</button>
</form>

<script>
function validerPaiement() {
    let cardNumber = document.getElementById("card_number").value.trim();
    let expiryDate = document.getElementById("expiry_date").value.trim();
    let cvv = document.getElementById("cvv").value.trim();

    if (cardNumber.length !== 16 || isNaN(cardNumber)) {
        alert("Le numéro de carte doit contenir exactement 16 chiffres.");
        return false;
    }

    if (!expiryDate.includes("/") || expiryDate.length !== 5) {
        alert("La date d'expiration doit être au format MM/YY.");
        return false;
    }

    let [mois, annee] = expiryDate.split("/").map(Number);
    let now = new Date(), anneeCourante = now.getFullYear() % 100, moisCourant = now.getMonth() + 1;

    if (mois < 1 || mois > 12 || annee < anneeCourante || (annee === anneeCourante && mois < moisCourant)) {
        alert("La carte est expirée ou la date est invalide.");
        return false;
    }

    if (cvv.length !== 3 || isNaN(cvv)) {
        alert("Le CVV doit contenir exactement 3 chiffres.");
        return false;
    }

    return true;
}
</script>

    </div>
    </div>
    <?php
    

    return ob_get_clean();
}