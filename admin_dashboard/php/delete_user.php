<?php

session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['user']) || ($_SESSION['user']['grade'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès interdit']);
    exit;
}

require_once '../../php/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM Users WHERE ID = ?");
    $success = $stmt->execute([$id]);
    echo json_encode(['success' => (bool) $success]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur BDD: ' . $e->getMessage()]);
}