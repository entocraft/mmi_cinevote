<?php
/* API d'ajout de séries via TMDb – accès grade = 1 */
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Sécurité session
if (empty($_SESSION['user']) || ($_SESSION['user']['grade'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès interdit']);
    exit;
}

require_once '../../php/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupération du JSON
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$tmdbId = intval($data['tmdb_id'] ?? 0);
if ($tmdbId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID TMDb invalide']);
    exit;
}

// Authentification TMDb – token codé en dur
$bearer = 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI';

$ch = curl_init("https://api.themoviedb.org/3/tv/{$tmdbId}?language=fr-FR");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: {$bearer}",
        'Accept: application/json'
    ]
]);
$tmdbJson = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => "TMDb a renvoyé HTTP {$httpCode}"]);
    exit;
}

$tmdb = json_decode($tmdbJson, true);
if (empty($tmdb['name'])) {
    http_response_code(404);
    echo json_encode(['error' => 'Série TMDb introuvable']);
    exit;
}

// Préparation des données
$name       = $tmdb['name'];
$overview   = $tmdb['overview']         ?? null;
$firstAir   = $tmdb['first_air_date']   ?? null;
$poster     = $tmdb['poster_path']      ?? null;
$backdrop   = $tmdb['backdrop_path']    ?? null;

// Insert + update on duplicate
try {
    $stmt = $pdo->prepare("
      INSERT INTO Series
        (Name, Description, Date, TMDB_ID, Poster_Img, Banner_Img)
      VALUES
        (?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        Name        = VALUES(Name),
        Description = VALUES(Description),
        Date        = VALUES(Date),
        Poster_Img  = VALUES(Poster_Img),
        Banner_Img  = VALUES(Banner_Img)
    ");
    $stmt->execute([$name, $overview, $firstAir, $tmdbId, $poster, $backdrop]);
    $newId = $pdo->lastInsertId() ?: 
             $pdo->query("SELECT ID FROM Series WHERE TMDB_ID = {$tmdbId}")
                 ->fetchColumn();
    echo json_encode(['success' => true, 'id' => (int)$newId]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur BDD : '.$e->getMessage()]);
}