<?php
/* ---------- Session + sécurité ---------- */
session_set_cookie_params([
  'domain'   => '.get-media.fr',
  'path'     => '/',
  'secure'   => true,  // à false si HTTP
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user']) || ($_SESSION['user']['grade'] ?? 0) !== 1) {
  http_response_code(403);
  echo json_encode(['error' => 'Accès interdit']);
  exit;
}

require '../../php/db.php';

$data = json_decode(file_get_contents('php://input'), true) ?: [];

if (isset($data['tmdb_id'])) {
  /* ====== Cas TMDb ====== */
  $tmdbId = intval($data['tmdb_id']);
  if (!$tmdbId) {
    echo json_encode(['error' => 'ID TMDb invalide']); exit;
  }

  /* --- Requête TMDb --- */
  $bearer = 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI';            // ➜ remplace
  $curl = curl_init("https://api.themoviedb.org/3/movie/$tmdbId?language=fr-FR");
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
      "Authorization: $bearer",
      'Accept: application/json'
    ]
  ]);
  $tmdbJson = curl_exec($curl);
  curl_close($curl);

  $tmdb = json_decode($tmdbJson, true);
  if (!isset($tmdb['title'])) {
    echo json_encode(['error' => 'Film TMDb introuvable']); exit;
  }

  /* --- Préparation des données --- */
  $name        = $tmdb['title'];
  $description = $tmdb['overview'] ?? null;
  $date        = $tmdb['release_date'] ?? null;
  $poster      = $tmdb['poster_path']  ?? null;
  $banner      = $tmdb['backdrop_path'] ?? null;

  /* --- Insertion BDD --- */
  $stmt = $pdo->prepare("INSERT INTO Films (Name, Description, Date, TMDB_ID, Poster_Img, Banner_Img)
                         VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->execute([$name, $description, $date, $tmdbId, $poster, $banner]);

  echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
  exit;

} elseif (!empty($data['name'])) {
  /* ====== Cas manuel ====== */
  $name        = trim($data['name']);
  $description = trim($data['description'] ?? '');
  $date        = $data['date'] ?? null;

  $stmt = $pdo->prepare("INSERT INTO Films (Name, Description, Date)
                         VALUES (?, ?, ?)");
  $stmt->execute([$name, $description ?: null, $date ?: null]);

  echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
  exit;
}

/* ---------- Données invalides ---------- */
echo json_encode(['error' => 'Données invalides']);