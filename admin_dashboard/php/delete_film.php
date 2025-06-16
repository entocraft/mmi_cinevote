<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['grade'] !== 1) {
  http_response_code(403);
  exit;
}
require '../../php/db.php';
$id = intval($_GET['id']);
$pdo->prepare("DELETE FROM Films WHERE ID = ?")->execute([$id]);
echo json_encode(["status" => "ok"]);