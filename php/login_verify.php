<?php
session_set_cookie_params([
    'domain' => '.get-media.fr',
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

require __DIR__ . '/db.php';

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
        $_SESSION['user_id']  = $user['ID'];
        $_SESSION['username'] = $username;
        header('Location: ../index.php');
        exit;
    } else {
        $_SESSION['login_error'] = 'Identifiant ou mot de passe incorrect.';
        header('Location: ../login_form.php');
        exit;
    }
} else {
    header('Location: ../login_form.php');
    exit;
}