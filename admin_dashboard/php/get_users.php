<?php
// get_users.php – API pour récupérer la liste des utilisateurs (grade ≥ 1)
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification de l'accès : seul un admin (grade = 1) peut lister les utilisateurs
if (empty($_SESSION['user']) || ($_SESSION['user']['grade'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès interdit']);
    exit;
}
require_once '../../php/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $stmt = $pdo->prepare(
        "SELECT ID, Username, Mail, Grade, Date
         FROM Users
         ORDER BY Date DESC"
    );
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['users' => $users]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur BDD: ' . $e->getMessage()]);
}
