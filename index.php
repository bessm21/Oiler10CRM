<?php require_once 'config.php'; ?>

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
    <?php include 'pages/contacts.php'; ?>
    <?php include 'pages/dashboard.php'; ?>
</div>
<script>
    function switchPage(pageId) {
        // 1. Hide both views first
        document.getElementById('dashboard-view').style.display = 'none';
        document.getElementById('contacts-view').style.display = 'none';

        // 2. Show the one that was clicked
        const selectedPage = document.getElementById(pageId);
        if (selectedPage) {
            selectedPage.style.display = 'block';
        }
    }
</script>
<script>
    function switchPage(pageId) {
        // Hide both sections
        document.getElementById('dashboard-view').style.display = 'none';
        document.getElementById('contacts-view').style.display = 'none';

        // Show the clicked section
        document.getElementById(pageId).style.display = 'block';
    }
</script>
</body>
</html>