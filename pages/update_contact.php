<?php

include "../config.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing contact data"
    ]);
    exit;
}


$id = (int)$data["id"];
$name = $data["name"] ?? "";
$title = $data["title"] ?? "";
$company = $data["company"] ?? "";
$email = $data["email"] ?? "";
$phone = $data["phone"] ?? "";
$type = $data["type"] ?? "";
$status = $data["status"] ?? "";

try {

    $sql = 'UPDATE "Contacts"
            SET name = :name,
                title = :title,
                company = :company,
                email = :email,
                phone = :phone,
                type = :type,
                status = :status
            WHERE id = :id';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":id" => $id,
        ":name" => $name,
        ":title" => $title,
        ":company" => $company,
        ":email" => $email,
        ":phone" => $phone,
        ":type" => $type,
        ":status" => $status
    ]);

    echo json_encode([
        "success" => true
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}
