<?php
// Démarrage de la session pour afficher d'éventuels messages d'erreur
session_start();
$errorMsg = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 380px;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="text-center mb-4">Se connecter</h3>
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <form method="POST" action="./php/login_verify.php">
            <div class="mb-3">
                <label for="username" class="form-label">Identifiant</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Connexion</button>
        </form>
    </div>
</body>
</html>