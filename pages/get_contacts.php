<?php
include "../config.php";

header("Content-Type: application/json");

try {
    $stmt = $pdo->query('SELECT * FROM "Contacts"');
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($contacts);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
