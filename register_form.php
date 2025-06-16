<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$errorMsg = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un compte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .register-card {
            width: 100%;
            max-width: 400px;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <h3 class="text-center mb-4">Créer un compte</h3>
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <form method="POST" action="./php/register_verify.php">
            <div class="mb-3">
                <label for="username" class="form-label">Identifiant</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Adresse email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">S’inscrire</button>
            <div class="text-center mt-3">
                <a href="login_form.php">Déjà un compte ? Connectez-vous</a>
            </div>
        </form>
    </div>
</body>
</html>