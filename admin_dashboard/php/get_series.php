<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['grade'] !== 1) {
  http_response_code(403);
  exit;
}
require '../../php/db.php';
$stmt = $pdo->query("SELECT * FROM Series ORDER BY ID DESC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));