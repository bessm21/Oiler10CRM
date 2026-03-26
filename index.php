<?php
// 1. Database connection is required once at the very top
require_once 'config.php';
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

    <main class="content-area" style="flex-grow: 1; display: block;">

        <div id="dashboard-view" class="main-content">
            <?php include 'pages/dashboard.php'; ?>
        </div>

        <div id="contacts-view" class="main-content" style="display: none;">
            <?php include 'pages/contacts.php'; ?>
        </div>

        <div id="calendar-view" class="main-content" style="display: none;">
            <?php include 'pages/calendar.php'; ?>
        </div>

    </main>
</div>

<script>
    /**
     * Handles switching between different sections of the CRM
     * @param {string} pageId - The ID of the div to show (e.g., 'contacts-view')
     */
    function switchPage(pageId) {
        // A. Find all sections with the class 'main-content' and hide them
        const allViews = document.querySelectorAll('.main-content');
        allViews.forEach(view => {
            view.style.display = 'none';
        });

        // B. Show the specific section requested
        const targetView = document.getElementById(pageId);
        if (targetView) {
            targetView.style.display = 'block';
        } else {
            console.error("Navigation Error: Could not find " + pageId);
        }

        // C. Update the Sidebar "Active" Styling (The Orange Highlight)
        // First, remove the 'active' class from every link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Second, add 'active' to the link that was just clicked
        // Note: This relies on the browser's global 'event' object
        if (window.event && window.event.currentTarget) {
            window.event.currentTarget.classList.add('active');
        }
    }
</script>

</body>
</html>