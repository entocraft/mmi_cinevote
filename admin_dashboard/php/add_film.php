<?php
/* --------------------------------------------------------------
   INSERT_FILM.PHP – API d'ajout / mise à jour de films
   Conditions : l'utilisateur doit être connecté et avoir grade = 1
----------------------------------------------------------------*/

/* ---------- Session & sécurité ---------- */
session_set_cookie_params([
  'domain'   => '.get-media.fr',
  'path'     => '/',
  'secure'   => true,   // false si HTTP uniquement
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

/* ---------- Connexion BDD ---------- */
require '../../php/db.php';   // => doit définir $pdo
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ---------- Helpers ---------- */
function json_error(string $message, int $status = 400): void {
  http_response_code($status);
  echo json_encode(['error' => $message]);
  exit;
}

function sanitizeLatin1(?string $text): ?string {
  return $text !== null
    ? iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text)
    : null;
}

/* ---------- Lecture JSON d'entrée ---------- */
$data = json_decode(file_get_contents('php://input'), true) ?? [];

/* =====================================================================
   1) IMPORT VIA TMDB_ID
   =====================================================================*/
if (isset($data['tmdb_id'])) {
  $tmdbId = (int) $data['tmdb_id'];
  if ($tmdbId <= 0) json_error('ID TMDb invalide');

  /* ------ Appel TMDB API ------ */
  $bearer = 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI';  // <-- Remplace ici !
  $ch = curl_init("https://api.themoviedb.org/3/movie/{$tmdbId}?language=fr-FR");
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
      "Authorization: {$bearer}",
      'Accept: application/json'
    ]
  ]);
  $tmdbJson = curl_exec($ch);
  curl_close($ch);

  $tmdb = json_decode($tmdbJson, true);
  if (!isset($tmdb['title'])) json_error('Film TMDb introuvable');

  /* ------ Préparation des données ------ */
  $film = [
    ':name'   => $tmdb['title'],
    ':desc'   => $tmdb['overview'] ?? null,
    ':date'   => $tmdb['release_date']  ?? null,
    ':tmdb'   => $tmdbId,
    ':poster' => $tmdb['poster_path']   ?? null,
    ':banner' => $tmdb['backdrop_path'] ?? null
  ];

  /* ------ INSERT + UPDATE si doublon ------ */
  try {
    $stmt = $pdo->prepare(
      'INSERT INTO Films (Name, Description, Date, TMDB_ID, Poster_Img, Banner_Img)
       VALUES (:name, :desc, :date, :tmdb, :poster, :banner)
       ON DUPLICATE KEY UPDATE
         Name        = VALUES(Name),
         Description = VALUES(Description),
         Date        = VALUES(Date),
         Poster_Img  = VALUES(Poster_Img),
         Banner_Img  = VALUES(Banner_Img)'
    );
    $stmt->execute($film);

    // lastInsertId() == 0 si doublon mis à jour
    $id = $pdo->lastInsertId();
    if (!$id) {
      $id = $pdo->prepare('SELECT ID FROM Films WHERE TMDB_ID = ?');
      $id->execute([$tmdbId]);
      $id = $id->fetchColumn();
      $msg = 'Film déjà existant – infos mises à jour';
    } else {
      $msg = 'Film inséré';
    }

    echo json_encode(['success' => true, 'id' => (int)$id, 'message' => $msg]);
    exit;
  } catch (PDOException $e) {
    error_log('[insert_film.php] TMDB insert: '.$e->getMessage());
    json_error('Échec enregistrement : '.$e->errorInfo[2]);
  }
}

/* =====================================================================
   2) INSERT MANUEL (nom / description / date)
   =====================================================================*/
if (!empty($data['name'])) {
  $film = [
    ':name' => trim($data['name']),
    ':desc' => trim($data['description'] ?? '') ?: null,
    ':date' => $data['date'] ?? null
  ];

  try {
    $stmt = $pdo->prepare(
      'INSERT INTO Films (Name, Description, Date)
       VALUES (:name, :desc, :date)'
    );
    $stmt->execute($film);

    echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => 'Film inséré (manuel)']);
    exit;
  } catch (PDOException $e) {
    error_log('[insert_film.php] Manual insert: '.$e->getMessage());
    json_error('Échec enregistrement : '.$e->errorInfo[2]);
  }
}

/* =====================================================================
   3) CAS PAR DÉFAUT : données invalides
   =====================================================================*/
json_error('Données invalides');
