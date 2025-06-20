<?php
session_set_cookie_params([
  'domain'   => '.get-media.fr',
  'path'     => '/',
  'secure'   => true,
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: ../login_form.php");
exit;