<?php

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

$data = [
    'totalProjects'      => 0,
    'activeProjects'     => 0,
    'planningProjects'   => 0,
    'completedProjects'  => 0,
    'totalContacts'      => 0,
    'activeLeads'        => 0,
    'totalHours'         => 0,
    'completionRate'     => 0,
    'recentContactsHtml' => '',
    'upcomingEventsHtml' => '',
    'error'              => false
];

// --- 1. Project Stats (Isolated) ---
try {
    $data['totalProjects'] = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE LOWER(status) = ?");
    $stmt->execute(['active']);
    $data['activeProjects'] = (int)$stmt->fetchColumn();

    $stmt->execute(['planning']);
    $data['planningProjects'] = (int)$stmt->fetchColumn();

    $stmt->execute(['completed']);
    $data['completedProjects'] = (int)$stmt->fetchColumn();

    if ($data['totalProjects'] > 0) {
        $data['completionRate'] = round(($data['completedProjects'] / $data['totalProjects']) * 100);
    }

    // FIX: Cast text to NUMERIC before SUM()
    $data['totalHours'] = (int)$pdo->query("SELECT COALESCE(SUM(NULLIF(estimated_hours, '')::NUMERIC), 0) FROM projects")->fetchColumn();

} catch (Exception $e) {
    // Fail silently
}

// --- 2. Contact Stats (Isolated) ---
$recentContacts = [];
try {
    $data['totalContacts'] = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE LOWER(type) = ?");
    $stmt2->execute(['lead']);
    $data['activeLeads'] = (int)$stmt2->fetchColumn();
    $recentContacts = $pdo->query("SELECT name, type, status FROM contacts ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $data['totalContacts'] = (int)$pdo->query('SELECT COUNT(*) FROM "Contacts"')->fetchColumn();
        $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM "Contacts" WHERE LOWER(type) = ?');
        $stmt2->execute(['lead']);
        $data['activeLeads'] = (int)$stmt2->fetchColumn();
        $recentContacts = $pdo->query('SELECT name, type, status FROM "Contacts" ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $innerE) {
        // Fail silently
    }
}

// --- Process Recent Contacts HTML ---
if (empty($recentContacts)) {
    $data['recentContactsHtml'] = '<p style="color:var(--text-muted);font-size:0.9rem;">No contacts yet.</p>';
} else {
    foreach ($recentContacts as $c) {
        $dot    = $c['type'] === 'Client' ? 'blue-dot' : 'green-dot';
        $name   = htmlspecialchars($c['name']);
        $type   = htmlspecialchars($c['type']);
        $status = $c['status'] ? '&mdash; <span class="tag ' . strtolower(htmlspecialchars($c['status'])) . '">' . htmlspecialchars($c['status']) . '</span>' : '';
        $data['recentContactsHtml'] .= '<div class="list-item">'
            . '<span class="dot ' . $dot . '"></span>'
            . '<div class="item-details"><h4>' . $name . '</h4><p>' . $type . ' ' . $status . '</p></div>'
            . '</div>';
    }
}

// --- 3. Upcoming Events (Isolated) ---
$colorMap = ['blue'=>'blue-dot','green'=>'green-dot','red'=>'red-dot','purple'=>'purple-dot','yellow'=>'yellow-dot'];
try {
    $upcomingEvents = $pdo->query("SELECT title, event_date, color FROM calendar WHERE event_date >= CURRENT_DATE ORDER BY event_date ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($upcomingEvents)) {
        $data['upcomingEventsHtml'] = '<p style="color:var(--text-muted);font-size:0.9rem;">No upcoming events.</p>';
    } else {
        foreach ($upcomingEvents as $ev) {
            $dot   = $colorMap[$ev['color']] ?? 'blue-dot';
            $title = htmlspecialchars($ev['title']);
            $date  = date('M j', strtotime($ev['event_date']));
            $data['upcomingEventsHtml'] .= '<div class="list-item">'
                . '<span class="dot ' . $dot . '"></span>'
                . '<div class="item-details"><h4>' . $title . '</h4></div>'
                . '<span class="date">' . $date . '</span>'
                . '</div>';
        }
    }
} catch (Exception $e) {
    $data['upcomingEventsHtml'] = '<p style="color:var(--text-muted);font-size:0.9rem;">No upcoming events.</p>';
}

echo json_encode($data);