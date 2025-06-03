<?php
file_put_contents("debug.log", file_get_contents("php://input") . PHP_EOL, FILE_APPEND);

header('Content-Type: application/json');
require_once 'db.php'; // Inclusion de la connexion PDO

$action = $_GET['action'] ?? '';

switch ($action) {
case 'get':
    $userId = $_GET['user_id'] ?? null;
    if (!$userId) {
      echo json_encode(['error' => 'ID utilisateur manquant']);
      exit;
    }
    $stmt = $pdo->prepare("SELECT ID, Name FROM Playsets WHERE UserID = ?");
    $stmt->execute([$userId]);
    $playsets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['playsets' => $playsets]);
    break;

case 'create':
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data['name'] || !$data['user_id']) {
      echo json_encode(['error' => 'Paramètres manquants']);
      exit;
    }
    $stmt = $pdo->prepare("INSERT INTO Playsets (Name, UserID, Date) VALUES (?, ?, NOW())");
    $stmt->execute([$data['name'], $data['user_id']]);
    echo json_encode(['playset_id' => $pdo->lastInsertId()]);
    break;

case 'add':
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO FilmsPlayset (PlaysetID, Type, TMDB_ID) VALUES (?, ?, ?)");
    $stmt->execute([$data['playset_id'], $data['type'], $data['tmdb_id']]);
    echo json_encode(['success' => true]);
    break;

case 'list':
    $userId = $_GET['user_id'] ?? null;
    if (!$userId) {
        echo json_encode(['error' => 'ID utilisateur manquant']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            p.ID,
            p.Name,
            p.Banner,
            COUNT(f.TMDB_ID) AS entry_count
        FROM Playsets p
        LEFT JOIN FilmsPlayset f ON f.PlaysetID = p.ID
        WHERE p.UserID = ?
        GROUP BY p.ID
        ORDER BY p.Date DESC
    ");
    $stmt->execute([$userId]);
    $playsets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['playsets' => $playsets]);
    break;

case 'view':
    $playsetId = $_GET['id'] ?? null;
    if (!$playsetId) {
        echo json_encode(['error' => 'ID du playset manquant']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT Name, Banner FROM Playsets WHERE ID = ?");
    $stmt->execute([$playsetId]);
    $playset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$playset) {
        echo json_encode(['error' => 'Playset introuvable']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT TMDB_ID, Type FROM FilmsPlayset WHERE PlaysetID = ?");
    $stmt->execute([$playsetId]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'name' => $playset['Name'],
        'banner' => $playset['Banner'],
        'entries' => $entries
    ]);
    break;

case 'setbanner':
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data) || !isset($data['id']) || !isset($data['banner'])) {
        echo json_encode(['error' => 'Paramètres manquants']);
        exit;
    }

    file_put_contents("debug.log", "ID = {$data['id']}, Banner = {$data['banner']}" . PHP_EOL, FILE_APPEND);

    $stmt = $pdo->prepare("UPDATE Playsets SET Banner = ? WHERE ID = ?");
    $stmt->execute([$data['banner'], $data['id']]);
    echo json_encode(['success' => true]);
    break;

default:
    echo json_encode(['error' => 'Action inconnue']);
    break;
}
