<?php
/**
 * Plugin Name: Login
 * Description: Un plugin pour créer un formulaire de connexion et gérer l'authentification des utilisateurs.
 * Version: 1.1
 * Author: Yessine Berrich
 */

if (!defined('ABSPATH')) {
    exit;
}

function login_form() {
    ob_start();
    ?>
    <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
        <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 400px; width: 100%;">
            <h2 style="text-align: center; margin-bottom: 20px; color: #0073aa;">Connexion</h2>
            <?php if (isset($_GET['erreur'])) : ?>
                <p style="color: red; text-align: center;"><?php echo esc_html($_GET['erreur']); ?></p>
            <?php endif; ?>
            <form action="<?php echo esc_url(add_query_arg('login_action', 'custom_login', $_SERVER['REQUEST_URI'])); ?>" method="POST">
                <input type="email" placeholder="Email" name="email" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <input type="password" placeholder="Mot de passe" name="password" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <button type="submit" style="width: 100%; padding: 10px; background-color: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s;">Se connecter</button>
            </form>
            <p style="text-align: center; margin-top: 15px;">Vous n'avez pas de compte ? <a href="<?php echo esc_url("http://localhost/psyonline01/sinscrire/"); ?>" style="color: #0073aa; text-decoration: none;">Inscrivez-vous ici</a></p>
        </div>
    </div>
    <?php
    return ob_get_clean();  
}
add_shortcode('login_form', 'login_form');

function user_login() {
    if (isset($_GET['login_action']) && $_GET['login_action'] === 'custom_login' && isset($_POST['email']) && isset($_POST['password'])) {
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        $user = get_user_by('email', $email);

        if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_set_auth_cookie($user->ID);
            $roles = (array) $user->roles;
            $role = !empty($roles) ? $roles[0] : '';

            switch ($role) {
                case 'patient':
                    wp_safe_redirect(home_url("/home"));
                    break;
                case 'secretaire':
                    wp_safe_redirect(home_url("/interface-secretaire"));
                    break;
                case 'psychologue':
                    wp_safe_redirect(home_url("/interface-psychologue"));
                    break;
                default:
                    wp_safe_redirect(home_url());
                    break;
            }
            exit;
        } else {
            wp_safe_redirect(add_query_arg('erreur', urlencode('Identifiants incorrects'), remove_query_arg('login_action', $_SERVER['REQUEST_URI'])));
            exit;
        }
    }
}
add_action('init', 'user_login');

function logout($items, $args) {
    if (is_user_logged_in() && $args->theme_location === 'primary') {
        $logout_url = wp_logout_url(home_url());
        $logout_item = '<li class="menu-item logout-item"><a style="color:rgb(33, 30, 30);text-decoration: none;transition: color 0.3s;margin-right:20px;" href="' . esc_url($logout_url) . '">Se déconnecter</a></li>';
        
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $role = !empty($roles) ? $roles[0] : '';

        if ($role === 'secretaire') {
            $menu_items_to_remove = ['Acceuil','Rendez-vous','Nos psychologues','Se connecter','S’inscrire'];
            $items .= '<li class="menu-item"><a style="color:rgb(33, 30, 30);text-decoration: none;transition: color 0.3s;margin-right:20px;" href="' . esc_url(home_url("/interface-secretaire")) . '">Espace secretaire</a></li>';
            $items .= '<li class="menu-item"><a style="color:rgb(33, 30, 30);text-decoration: none;transition: color 0.3s;margin-right:20px;" href="' . esc_url(home_url("/messages")) . '">Messages</a></li>';
        } elseif ($role === 'psychologue') {
            $menu_items_to_remove = ['Acceuil','Rendez-vous','Nos psychologues','Se connecter','S’inscrire'];
            $items .= '<li class="menu-item"><a style="color:rgb(33, 30, 30);text-decoration: none;transition: color 0.3s;margin-right:20px;" href="' . esc_url(home_url("/interface-psychologue")) . '">Espace psychologue</a></li>';
            $items .= '<li class="menu-item"><a style="color:rgb(33, 30, 30);text-decoration: none;transition: color 0.3s;margin-right:20px;" href="' . esc_url(home_url("/messages")) . '">Messages</a></li>';
        } elseif ($role === 'patient') {
            $menu_items_to_remove = ['Se connecter','S’inscrire'];
            $items .= '<li class="menu-item"><a style="color:rgb(33, 30, 30);text-decoration: none;transition: color 0.3s;margin-right:20px;" href="' . esc_url(home_url("/messages")) . '">Messagerie</a></li>';
            $items .= '<li class="menu-item"><a style="color:rgb(33, 30, 30);text-decoration: none;transition: color 0.3s;margin-right:20px;" href="' . esc_url(home_url("/vos-rendezvous")) . '">Vos rendezvous</a></li>';
            $items .= '<li class="menu-item"><a style="color:rgb(33, 30, 30);text-decoration: none;transition: color 0.3s;margin-right:20px;" href="' . esc_url(home_url("/dossier-medical")) . '">Dossier medical</a></li>';
        }
        foreach ($menu_items_to_remove as $item) {
            $items = preg_replace('/<li[^>]*>\s*<a[^>]*>' . preg_quote($item, '/') . '<\/a>\s*<\/li>/', '', $items);
        }
        $items .= $logout_item;
    }
    return $items;
}
add_filter('wp_nav_menu_items', 'logout', 10, 2);
