<?php

include "../config.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$name = $data["name"] ?? "";
$title = $data["title"] ?? "";
$company = $data["company"] ?? "";
$email = $data["email"] ?? "";
$phone = $data["phone"] ?? "";
$type = $data["type"] ?? "";
$status = $data["status"] ?? "";

try {

    $sql = 'INSERT INTO "Contacts"
            (name, title, company, email, phone, type, status)
            VALUES 
            (:name, :title, :company, :email, :phone, :type, :status)';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":name" => $name,
        ":title" => $title,
        ":company" => $company,
        ":email" => $email,
        ":phone" => $phone,
        ":type" => $type,
        ":status" => $status
    ]);

    $id = $pdo->lastInsertId();   // ✅ correct PDO method

    echo json_encode([
        "success" => true,
        "id" => $id,
        "name" => $name,
        "title" => $title,
        "company" => $company,
        "email" => $email,
        "phone" => $phone,
        "type" => $type,
        "status" => $status
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}

