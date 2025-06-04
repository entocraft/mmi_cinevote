<?php
file_put_contents("debug.log", file_get_contents("php://input") . PHP_EOL, FILE_APPEND);

header('Content-Type: application/json');
require_once 'db.php'; // Inclusion de la connexion PDO

$action = $_GET['action'] ?? '';

switch ($action) {
case 'get':
    $userId = $_GET['user_id'] ?? null;
    $tmdbId = $_GET['tmdb_id'] ?? null; // Ajout
    $type = $_GET['type'] ?? null;      // Optionnel : film/serie

    if (!$userId) {
        echo json_encode(['error' => 'ID utilisateur manquant']);
        exit;
    }

    // On récupère les playsets ET pour chacun on vérifie si le TMDB_ID est déjà dedans
    $stmt = $pdo->prepare("SELECT ID, Name FROM Playsets WHERE UserID = ?");
    $stmt->execute([$userId]);
    $playsets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque playset, vérifier la présence du film/série
    if ($tmdbId) {
        foreach ($playsets as &$ps) {
            $sql = "SELECT 1 FROM FilmsPlayset WHERE PlaysetID = ? AND TMDB_ID = ?";
            $params = [$ps['ID'], $tmdbId];
            if ($type) {
                $sql .= " AND Type = ?";
                $params[] = $type;
            }
            $check = $pdo->prepare($sql);
            $check->execute($params);
            $ps['contains'] = $check->fetchColumn() ? true : false;
        }
        unset($ps); // break ref
    }

    echo json_encode(['playsets' => $playsets]);
    break;

case 'create':
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $name = $input['name'] ?? null;
    $description = $input['description'] ?? '';
    if (!$userId || !$name) {
        echo json_encode(['success' => false, 'error' => 'Champs obligatoires manquants.']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO Playsets (Name, Description, Date, UserID) VALUES (?, ?, NOW(), ?)");
    $success = $stmt->execute([$name, $description, $userId]);
    echo json_encode(['success' => $success]);
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

    $stmt = $pdo->prepare("SELECT Name, Description, Banner FROM Playsets WHERE ID = ?");
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
        'description' => $playset['Description'],
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

case 'remove_entry':
    $input = json_decode(file_get_contents('php://input'), true);
    $playset_id = $input['playset_id'] ?? null;
    $tmdb_id = $input['tmdb_id'] ?? null;
    $type = $input['type'] ?? null;
    if (!$playset_id || !$tmdb_id) {
        echo json_encode(['error' => 'missing data']);
        exit;
    }
    // Adapter le nom de la table et des colonnes selon ta structure
    $stmt = $pdo->prepare("DELETE FROM FilmsPlayset WHERE PlaysetID = ? AND TMDB_ID = ? AND Type = ?");
    $stmt->execute([$playset_id, $tmdb_id, $type]);
    echo json_encode(['success' => true]);
    break;

case 'edit':
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $name = $input['name'] ?? null;
    $description = $input['description'] ?? '';

    if (!$id || !$name) {
        echo json_encode(['error' => 'missing id or name']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE Playsets SET Name = ?, Description = ? WHERE ID = ?");
    $stmt->execute([$name, $description, $id]);
    echo json_encode(['success' => true]);
    break;

case 'create_vote_session':
    $input = json_decode(file_get_contents('php://input'), true);
    $playset_id = $input['playset_id'] ?? null;
    $name = $input['name'] ?? null;
    $end = $input['end'] ?? null;
    $description = $input['description'] ?? '';
    if (!$playset_id || !$name || !$end) {
        echo json_encode(['success' => false, 'error' => 'Données manquantes']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO PlaysetVote (PlaysetID, Name, End, Description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$playset_id, $name, $end, $description]);
    $vote_id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'vote_id' => $vote_id]);
    break;

default:
    echo json_encode(['error' => 'Action inconnue']);
    break;
}
