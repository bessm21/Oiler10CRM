<?php
require_once "../config.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

try {
    $sql = 'INSERT INTO "Contacts" (name, title, company, email, phone, type, status) 
            VALUES (:name, :title, :company, :email, :phone, :type, :status)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":name"    => $data["name"],
        ":title"   => $data["title"],
        ":company" => $data["company"],
        ":email"   => $data["email"],
        ":phone"   => $data["phone"],
        ":type"    => $data["type"],
        ":status"  => $data["status"]
    ]);

    echo json_encode(["success" => true, "id" => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}