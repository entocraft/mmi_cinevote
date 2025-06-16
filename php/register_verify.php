<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require __DIR__ . '/db.php'; // Connexion PDO

// Activer l'affichage des erreurs pour diagnostiquer d’éventuelles erreurs 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register_form.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$password_confirm = trim($_POST['password_confirm'] ?? '');

if ($username === '' || $email === '' || $password === '' || $password_confirm === '') {
    $_SESSION['register_error'] = 'Veuillez remplir tous les champs.';
    header('Location: register_form.php');
    exit;
}

if ($password !== $password_confirm) {
    $_SESSION['register_error'] = 'Les mots de passe ne correspondent pas.';
    header('Location: register_form.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = 'Adresse email invalide.';
    header('Location: register_form.php');
    exit;
}

// Vérifier si l’email existe déjà
$stmt = $pdo->prepare('SELECT ID FROM Users WHERE Mail = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['register_error'] = 'Cette adresse email est déjà utilisée.';
    header('Location: register_form.php');
    exit;
}

// Vérifier si l’email existe déjà
$stmt = $pdo->prepare('SELECT ID FROM Users WHERE Username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $_SESSION['register_error'] = 'Ce pseudo est déjà utilisée.';
    header('Location: register_form.php');
    exit;
}

// Générer un hash bcrypt cost=10
$options = ['cost' => 10];
$password_hash = password_hash($password, PASSWORD_BCRYPT, $options);

// Insérer le nouvel utilisateur
$stmt = $pdo->prepare('INSERT INTO Users (Username, Mail, Passwd, Date) VALUES (?, ?, ?, NOW())');
$success = $stmt->execute([$username, $email, $password_hash]);

if (!$success) {
    $_SESSION['register_error'] = 'Une erreur est survenue. Réessayez plus tard.';
    header('Location: register_form.php');
    exit;
}

// Récupérer l’ID de l’utilisateur fraîchement créé
$user_id = $pdo->lastInsertId();
$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $username;

// Rediriger vers la page d’accueil
header('Location: ../index.php');
exit;