

<?php
// edit_film.php – API pour récupérer et modifier un film existant
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification de l'accès : seul un admin (grade = 1) peut récupérer ou modifier
if (empty($_SESSION['user']) || ($_SESSION['user']['grade'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès interdit']);
    exit;
}

require_once __DIR__ . '/../../php/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Si GET : retourne les infos du film pour pré-remplir le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID invalide']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT ID, Name, Description, Date FROM Films WHERE ID = ?");
        $stmt->execute([$id]);
        $film = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$film) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Film non trouvé']);
            exit;
        }
        echo json_encode(['success' => true, 'film' => $film]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur BDD: ' . $e->getMessage()]);
    }
    exit;
}

// Sinon POST : met à jour le film
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$id = isset($input['id']) ? (int)$input['id'] : 0;
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$date = $input['date'] ?? null;

if ($id <= 0 || $name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE Films
        SET Name = ?, Description = ?, Date = ?
        WHERE ID = ?
    ");
    $success = $stmt->execute([
        $name,
        $description !== '' ? $description : null,
        $date ?: null,
        $id
    ]);
    echo json_encode(['success' => (bool)$success]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur BDD: ' . $e->getMessage()]);
}