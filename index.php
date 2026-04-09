<?php
ob_start();
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 1. Database connection and path handling
require_once __DIR__ . '/config.php';

// 2. Detect the initial page view from the URL (?page=...)
$page = isset($_GET['page']) ? $_GET['page'] : 'overview';
$allowed = ['overview', 'projects', 'calendar', 'contacts'];

if (!in_array($page, $allowed, true)) {
    $page = 'overview';
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
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <main class="content-area" style="flex-grow: 1; display: block;">
        <div class="main-content">
            <?php
            if ($page === 'overview') {
                include __DIR__ . '/pages/dashboard.php';
            } elseif ($page === 'projects') {
                echo '<div id="projects-view">';
                include __DIR__ . '/pages/projects.php';
                echo '</div>';
            } elseif ($page === 'contacts') {
                echo '<div id="contacts-view">';
                include __DIR__ . '/pages/contacts.php';
                echo '</div>';
            } elseif ($page === 'calendar') {
                echo '<div id="calendar-view">';
                include __DIR__ . '/pages/calendar.php';
                echo '</div>';
            }
            ?>
        </div>
    </main>
</div>

<script>
    /**
     * Swaps the visible view and updates the URL state
     * @param {string} pageId - The ID of the div to show (e.g., 'calendar-view')
     */
    function switchPage(pageId) {
        // A. Hide all sections
        const allViews = document.querySelectorAll('.main-content');
        allViews.forEach(view => {
            view.style.display = 'none';
        });

        // B. Show the requested section
        const targetView = document.getElementById(pageId);
        if (targetView) {
            targetView.style.display = 'block';
        } else {
            console.error("Navigation Error: Could not find " + pageId);
            return;
        }

        // C. Update the URL in the address bar without a page reload
        const pageName = pageId.replace('-view', '');
        const cleanURLName = (pageName === 'dashboard') ? 'overview' : pageName;
        window.history.pushState({}, '', '?page=' + cleanURLName);

        // D. Update Sidebar "Active" styling
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Use the event target to highlight the clicked link
        if (window.event && window.event.currentTarget) {
            window.event.currentTarget.classList.add('active');
        }
    }

    // Handle the browser "Back" button
    window.onpopstate = function() {
        location.reload();
    };
</script>

</body>
</html>