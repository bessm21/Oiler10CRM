<?php
/**
 * pages/dashboard_data.php
 *
 * AJAX endpoint: returns live dashboard stats as JSON.
 * Called by refreshDashboard() in index.php after any mutation.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Auth + approval check (returns JSON error instead of redirecting)
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sessionRole   = $_SESSION['role']   ?? 'user';
$sessionStatus = $_SESSION['status'] ?? 'pending';

if ($sessionRole !== 'admin' && $sessionStatus !== 'approved') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once '../config.php';

$totalProjects     = 0;
$activeProjects    = 0;
$planningProjects  = 0;
$completedProjects = 0;
$totalContacts     = 0;
$activeLeads       = 0;
$totalHours        = 0;
$completionRate    = 0;
$recentContacts    = [];
$upcomingEvents    = [];

try {
    $totalProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = ?");

    $stmt->execute(['Active']);
    $activeProjects = (int)$stmt->fetchColumn();

    $stmt->execute(['Planning']);
    $planningProjects = (int)$stmt->fetchColumn();

    $stmt->execute(['Completed']);
    $completedProjects = (int)$stmt->fetchColumn();

    if ($totalProjects > 0) {
        $completionRate = round(($completedProjects / $totalProjects) * 100);
    }

    $totalHours = (int)$pdo->query("SELECT COALESCE(SUM(estimated_hours), 0) FROM projects")->fetchColumn();

    $totalContacts = (int)$pdo->query('SELECT COUNT(*) FROM "Contacts"')->fetchColumn();

    $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM "Contacts" WHERE type = ?');
    $stmt2->execute(['Lead']);
    $activeLeads = (int)$stmt2->fetchColumn();

    $recentContacts = $pdo->query(
        'SELECT name, type, status FROM "Contacts" ORDER BY id DESC LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC);

    $upcomingEvents = $pdo->query(
        "SELECT title, event_date, color FROM calendar
         WHERE event_date >= CURRENT_DATE
         ORDER BY event_date ASC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Zeros remain
}

// Build HTML snippets for the list panels

$colorMap = ['blue'=>'blue-dot','green'=>'green-dot','red'=>'red-dot','purple'=>'purple-dot','yellow'=>'yellow-dot'];

$recentContactsHtml = '';
if (empty($recentContacts)) {
    $recentContactsHtml = '<p style="color:var(--text-muted);font-size:0.9rem;">No contacts yet.</p>';
} else {
    foreach ($recentContacts as $c) {
        $dot    = $c['type'] === 'Client' ? 'blue-dot' : 'green-dot';
        $name   = htmlspecialchars($c['name']);
        $type   = htmlspecialchars($c['type']);
        $status = $c['status'] ? '&mdash; <span class="tag ' . strtolower(htmlspecialchars($c['status'])) . '">' . htmlspecialchars($c['status']) . '</span>' : '';
        $recentContactsHtml .= '<div class="list-item">'
            . '<span class="dot ' . $dot . '"></span>'
            . '<div class="item-details"><h4>' . $name . '</h4><p>' . $type . ' ' . $status . '</p></div>'
            . '</div>';
    }
}

$upcomingEventsHtml = '';
if (empty($upcomingEvents)) {
    $upcomingEventsHtml = '<p style="color:var(--text-muted);font-size:0.9rem;">No upcoming events.</p>';
} else {
    foreach ($upcomingEvents as $ev) {
        $dot   = $colorMap[$ev['color']] ?? 'blue-dot';
        $title = htmlspecialchars($ev['title']);
        $date  = date('M j', strtotime($ev['event_date']));
        $upcomingEventsHtml .= '<div class="list-item">'
            . '<span class="dot ' . $dot . '"></span>'
            . '<div class="item-details"><h4>' . $title . '</h4></div>'
            . '<span class="date">' . $date . '</span>'
            . '</div>';
    }
}

echo json_encode([
    'totalProjects'       => $totalProjects,
    'activeProjects'      => $activeProjects,
    'planningProjects'    => $planningProjects,
    'completedProjects'   => $completedProjects,
    'totalContacts'       => $totalContacts,
    'activeLeads'         => $activeLeads,
    'totalHours'          => $totalHours,
    'completionRate'      => $completionRate,
    'recentContactsHtml'  => $recentContactsHtml,
    'upcomingEventsHtml'  => $upcomingEventsHtml,
]);
