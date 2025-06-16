<?php
// Session sécurisée sur tout le domaine (au cas où elle avait été initialisée ainsi)
session_set_cookie_params([
  'domain'   => '.get-media.fr',  // adapte selon ton domaine réel
  'path'     => '/',
  'secure'   => true,             // à activer si HTTPS
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

// Supprimer toutes les variables de session
$_SESSION = [];

// Supprimer le cookie de session (sur le navigateur)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session côté serveur
session_destroy();

// Redirection vers la page de login
header("Location: ../login_form.php"); // adapte selon ton chemin
exit;