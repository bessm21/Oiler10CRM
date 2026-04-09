<?php
/**
 * pages/check_status.php
 * Lightweight AJAX endpoint used by pending.php to check if the user has been approved.
 * Re-reads the current status from the DB and refreshes the session.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

try {
    $stmt = $pdo->prepare('SELECT status, role FROM users WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Refresh session so auth_guard picks up the new status next navigation
        $_SESSION['status'] = $row['status'];
        $_SESSION['role']   = $row['role'];
        echo json_encode(['status' => $row['status'], 'role' => $row['role']]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error']);
}
