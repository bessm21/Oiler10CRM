<?php
// Pointing directly to the config file in the same folder
require_once 'config.php';

try {


    // --- 1. PROJECTS DATA ---
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM projects");
    $totalProjects = $stmtTotal->fetchColumn();

    $stmtActive = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = :status");
    $stmtActive->execute(['status' => 'active']);
    $activeProjects = $stmtActive->fetchColumn();

    $stmtPlanning = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = :status");
    $stmtPlanning->execute(['status' => 'planning']);
    $planningProjects = $stmtPlanning->fetchColumn();

    // --- 2. CONTACTS DATA ---
    // Double quotes around "Contacts" because of the capital C!
    $stmtTotalContacts = $pdo->query('SELECT COUNT(*) FROM "Contacts"');
    $totalContacts = $stmtTotalContacts->fetchColumn();

    $stmtActiveLeads = $pdo->prepare('SELECT COUNT(*) FROM "Contacts" WHERE status = :status');
    $stmtActiveLeads->execute(['status' => 'active']);
    $activeLeads = $stmtActiveLeads->fetchColumn();

} catch (PDOException $e) {
    // Fallbacks so the page doesn't crash if a query fails
    $totalProjects = 0; $activeProjects = 0; $planningProjects = 0;
    $totalContacts = 0; $activeLeads = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oiler 10 CRM - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <?php include 'pages/dashboard.php'; ?>
</div>

</body>
</html>