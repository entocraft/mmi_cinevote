<?php
try {
    $pdo = new PDO(
        "mysql:host=web07.ouiheberg.com;dbname=mlzdphgn_MMI_CTVS;charset=utf8",
        "mlzdphgn_CineVote",
        "!CVSmmi2025",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données', 'details' => $e->getMessage()]);
    exit;
}
?>
