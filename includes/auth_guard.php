<?php
/**
 * includes/auth_guard.php
 *
 * Reusable auth + approval guard.
 * Include at the top of any CRM page that requires login and approval.
 *
 * - Redirects to login.php if not authenticated.
 * - Redirects to pending.php if logged in but not yet approved (non-admin).
 * - Admins always pass through regardless of status.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$_guard_role   = $_SESSION['role']   ?? 'user';
$_guard_status = $_SESSION['status'] ?? 'pending';

if ($_guard_role !== 'admin' && $_guard_status !== 'approved') {
    header('Location: pending.php');
    exit;
}
