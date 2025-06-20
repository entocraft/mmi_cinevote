<?php
header('Content-Type: application/json');

require_once './db.php'; // fichier avec ta connexion PDO $pdo

$type = $_GET['type'] ?? 'movie';
$page = max((int)($_GET['page'] ?? 1), 1);
$search = $_GET['search'] ?? '';
$limit = 20;
$offset = ($page - 1) * $limit;

$table = $type === 'tv' ? 'Series' : 'Films';

$sql = "SELECT * FROM $table";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE Name LIKE :search";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY ID DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as &$item) {
        $tmdb_id = $item['TMDB_ID'];
        $needsUpdate = false;

        if (empty($item['Name']) || empty($item['Description']) || empty($item['Rating']) || empty($item['Date']) || empty($item['Poster_Img']) || empty($item['Banner_Img'])) {
            $url = "https://api.themoviedb.org/3/" . ($type === 'tv' ? 'tv' : 'movie') . "/$tmdb_id?language=fr-FR";
            $opts = ["http" => [
                "method" => "GET",
                "header" => "Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI\r\nAccept: application/json\r\n"
            ]];
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data) {
                    if (empty($item['Name']) && isset($data['title'])) {
                        $item['Name'] = $data['title'];
                        $needsUpdate = true;
                    }
                    if (empty($item['Description']) && isset($data['overview'])) {
                        $item['Description'] = $data['overview'];
                        $needsUpdate = true;
                    }
                    if (empty($item['Rating']) && isset($data['vote_average'])) {
                        $item['Rating'] = $data['vote_average'];
                        $needsUpdate = true;
                    }
                    if (empty($item['Date']) && isset($data['release_date'])) {
                        $item['Date'] = $data['release_date'];
                        $needsUpdate = true;
                    }
                    if (empty($item['Poster_Img']) && isset($data['poster_path'])) {
                        $item['Poster_Img'] = $data['poster_path'];
                        $needsUpdate = true;
                    }
                    if (empty($item['Banner_Img']) && isset($data['backdrop_path'])) {
                        $item['Banner_Img'] = $data['backdrop_path'];
                        $needsUpdate = true;
                    }

                    // Met à jour la BDD
                    if ($needsUpdate) {
                        $updateSql = "UPDATE $table SET Name = :name, Description = :description, Rating = :rating, Date = :date, Poster_Img = :poster, Banner_Img = :banner WHERE TMDB_ID = :tmdb_id";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->execute([
                            ':name' => $item['Name'],
                            ':description' => $item['Description'],
                            ':rating' => $item['Rating'],
                            ':date' => $item['Date'],
                            ':poster' => $item['Poster_Img'],
                            ':banner' => $item['Banner_Img'],
                            ':tmdb_id' => $tmdb_id
                        ]);
                    }
                }
            }
        }
    }

    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
