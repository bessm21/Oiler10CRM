<?php
ob_start();
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config.php';

$isAdmin = ($_SESSION['role'] ?? 'user') === 'admin';

$page    = isset($_GET['page']) ? $_GET['page'] : 'overview';
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
            <?php include __DIR__ . '/pages/calendar.php'; ?>
        </div>

    </main>
</div>

<script>
    /**
     * Swaps the visible view and updates the URL state.
     * @param {string} pageId - The ID of the div to show (e.g., 'calendar-view')
     */
    function switchPage(pageId) {
        const allViews = document.querySelectorAll('.main-content');
        allViews.forEach(view => { view.style.display = 'none'; });

        const targetView = document.getElementById(pageId);
        if (targetView) {
            targetView.style.display = 'block';
        } else {
            console.error('Navigation Error: Could not find ' + pageId);
            return;
        }

        const pageName     = pageId.replace('-view', '');
        const cleanURLName = (pageName === 'dashboard') ? 'overview' : pageName;
        window.history.pushState({}, '', '?page=' + cleanURLName);

        document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
        if (window.event && window.event.currentTarget) {
            window.event.currentTarget.classList.add('active');
        }

        // Refresh dashboard data whenever the user navigates back to it
        if (pageId === 'dashboard-view') {
            refreshDashboard();
        }
    }

    // Handle the browser "Back" button
    window.onpopstate = function() { location.reload(); };

    /**
     * Fetches live stats from the server and updates dashboard DOM elements.
     * Called after any mutation in Contacts or Calendar, and when navigating to Overview.
     */
    function refreshDashboard() {
        fetch('pages/dashboard_data.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) return;

                // Stat cards
                var el;
                el = document.getElementById('dash-total-projects');
                if (el) el.textContent = data.totalProjects;

                el = document.getElementById('dash-active-projects');
                if (el) el.textContent = data.activeProjects;

                el = document.getElementById('dash-total-contacts');
                if (el) el.textContent = data.totalContacts;

                el = document.getElementById('dash-active-leads');
                if (el) el.textContent = data.activeLeads;

                // Bottom stat cards
                el = document.getElementById('dash-total-hours');
                if (el) el.textContent = data.totalHours;

                el = document.getElementById('dash-planning-projects');
                if (el) el.textContent = data.planningProjects;

                el = document.getElementById('dash-completion-rate');
                if (el) el.textContent = data.completionRate + '%';

                el = document.getElementById('dash-completion-desc');
                if (el) el.textContent = data.completedProjects + ' of ' + data.totalProjects + ' projects completed';

                // List panels
                el = document.getElementById('dash-recent-contacts');
                if (el && data.recentContactsHtml !== undefined) el.innerHTML = data.recentContactsHtml;

                el = document.getElementById('dash-upcoming-events');
                if (el && data.upcomingEventsHtml !== undefined) el.innerHTML = data.upcomingEventsHtml;
            })
            .catch(function() { /* silent — dashboard shows last PHP-rendered data */ });
    }

    /**
     * Reload the admin pending-users panel after an approve/deny action.
     */
    function reloadAdminPanel() {
        fetch('pages/admin_actions.php?action=list')
            .then(function(r) { return r.json(); })
            .then(function(users) {
                var panel = document.getElementById('admin-pending-list');
                if (!panel) return;
                if (!Array.isArray(users) || users.length === 0) {
                    panel.innerHTML = '<p style="color:var(--text-muted);font-size:0.9rem;">No pending users.</p>';
                    return;
                }
                var html = '';
                users.forEach(function(u) {
                    html += '<div class="admin-user-row">'
                          + '<div class="admin-user-info">'
                          + '<strong>' + escHtml(u.username) + '</strong>'
                          + '<span>' + escHtml(u.email) + '</span>'
                          + '</div>'
                          + '<div class="admin-user-actions">'
                          + '<button class="admin-approve-btn" onclick="adminAction(\'approve\',\'' + u.user_id + '\')">Approve</button>'
                          + '<button class="admin-deny-btn"    onclick="adminAction(\'deny\',\''    + u.user_id + '\')">Deny</button>'
                          + '</div>'
                          + '</div>';
                });
                panel.innerHTML = html;
            })
            .catch(function() {});
    }

    function adminAction(action, userId) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('user_id', userId);
        fetch('pages/admin_actions.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    reloadAdminPanel();
                } else {
                    alert('Action failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function() { alert('Network error'); });
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }
</script>

</body>
</html>
