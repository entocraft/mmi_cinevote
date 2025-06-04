<?php
session_start();

// Affiche toutes les erreurs PHP (utile pour déboguer le 500)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Si l’utilisateur est déjà connecté, on le redirige
if (isset($_SESSION['user_id'])) {
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

    // Récupérer le hash bcrypt depuis la BDD
    $stmt = $pdo->prepare('SELECT ID, Passwd FROM Users WHERE Username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Passwd'])) {
        // Authentification réussie : on stocke en session et on redirige
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $username;
        header('Location: ../index.html');
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