<?php
require_once __DIR__ . '/config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'overview';
$allowed = ['overview', 'calendar', 'contacts'];

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

        <div id="dashboard-view" class="main-content" style="<?php echo $page === 'overview' ? 'display:block;' : 'display:none;'; ?>">
            <?php include __DIR__ . '/pages/dashboard.php'; ?>
        </div>

        <div id="contacts-view" class="main-content" style="<?php echo $page === 'contacts' ? 'display:block;' : 'display:none;'; ?>">
            <?php include __DIR__ . '/pages/contacts.php'; ?>
        </div>

        <div id="calendar-view" class="main-content" style="<?php echo $page === 'calendar' ? 'display:block;' : 'display:none;'; ?>">
            <?php include __DIR__ . '/pages/Calendar.php'; ?>
        </div>

    </main>
</div>

<script>
    function switchPage(pageId) {
        const allViews = document.querySelectorAll('.main-content');
        allViews.forEach(view => {
            view.style.display = 'none';
        });

        const targetView = document.getElementById(pageId);
        if (targetView) {
            targetView.style.display = 'block';
        } else {
            console.error("Navigation Error: Could not find " + pageId);
        }

        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        if (window.event && window.event.currentTarget) {
            window.event.currentTarget.classList.add('active');
        }
    }
</script>

</body>
</html>