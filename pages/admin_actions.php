<?php
/**
 * pages/admin_actions.php
 *
 * AJAX endpoint for admin-only user management.
 *
 * GET  ?action=list           → returns JSON array of pending users
 * POST action=approve&user_id → approves a user
 * POST action=deny&user_id    → denies a user
 *
 * Returns JSON. Only accessible by users with role = 'admin'.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Must be logged in AND admin
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once '../config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- LIST pending users ---
if ($action === 'list') {
    try {
        $stmt = $pdo->query(
            "SELECT user_id, username, email, status FROM users WHERE status = 'pending' ORDER BY user_id ASC"
        );
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error']);
    }
    exit;
}

// --- APPROVE or DENY a user ---
if ($action === 'approve' || $action === 'deny') {
    $userId = trim($_POST['user_id'] ?? '');

    if ($userId === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user_id']);
        exit;
    }

    $newStatus = ($action === 'approve') ? 'approved' : 'denied';

    try {
        // Never change another admin's status
        $stmt = $pdo->prepare(
            "UPDATE users SET status = :status WHERE user_id = :uid AND role != 'admin'"
        );
        $stmt->execute([':status' => $newStatus, ':uid' => $userId]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['error' => 'User not found or is an admin']);
        } else {
            echo json_encode(['success' => true, 'status' => $newStatus]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
