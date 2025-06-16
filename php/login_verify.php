<?php
session_set_cookie_params([
    'domain' => '.get-media.fr',
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Affiche toutes les erreurs PHP (utile pour déboguer le 500)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Si l’utilisateur est déjà connecté, on le redirige
if (!empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// Inclut la connexion PDO depuis db.php
require __DIR__ . '/db.php'; // Assure-toi que db.php est dans le même dossier

// Vérification en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $_SESSION['login_error'] = 'Veuillez remplir tous les champs.';
        header('Location: ../login_form.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT ID, Passwd, Grade FROM Users WHERE Username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Passwd'])) {
        $_SESSION['user'] = [
            'id'    => $user['ID'],
            'name'  => $username,
            'grade' => (int)$user['Grade']
        ];
        // variables legacy pour compatibilité avec l’ancien code
        $_SESSION['user_id']  = $user['ID'];
        $_SESSION['username'] = $username;
        header('Location: ../index.php');
        exit;
    } else {
        // Échec d’authentification
        $_SESSION['login_error'] = 'Identifiant ou mot de passe incorrect.';
        header('Location: ../login_form.php');
        exit;
    }
} else {
    // Si on accède en GET, on redirige vers le formulaire
    header('Location: ../login_form.php');
    exit;
}