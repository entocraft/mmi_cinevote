<?php
try {
    $pdo = new PDO(
        "mysql:host=...;dbname=...;charset=utf8mb4",
        "...", // Nom d'utilisateur
        "...", // Mot de passe
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données', 'details' => $e->getMessage()]);
    exit;
}
?>
