<?php
require_once 'config.php';

$totalProjects    = 0;
$activeProjects   = 0;
$planningProjects = 0;
$completedProjects = 0;
$totalContacts    = 0;
$activeLeads      = 0;
$totalHours       = 0;
$completionRate   = 0;
$recentContacts   = [];
$upcomingEvents   = [];

try {
    // --- Project stats ---
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

    // --- Contact stats ---
    $totalContacts = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE type = ?");
    $stmt2->execute(['Lead']);
    $activeLeads = (int)$stmt2->fetchColumn();

    // --- Recent contacts (last 5) ---
    $recentContacts = $pdo->query(
        "SELECT name, type, status FROM contacts ORDER BY id DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    // --- Upcoming calendar events (next 5 from today) ---
    $upcomingEvents = $pdo->query(
        "SELECT title, event_date, color FROM calendar
         WHERE event_date >= CURRENT_DATE
         ORDER BY event_date ASC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Fail silently — zeros already set above
}

// Status dot color per contact type
function statusDotClass(string $type): string {
    return $type === 'Client' ? 'blue-dot' : 'green-dot';
}

// Map event color to a readable label shown in the dot
function eventDotClass(string $color): string {
    $map = [
        'blue'   => 'blue-dot',
        'green'  => 'green-dot',
        'red'    => 'red-dot',
        'purple' => 'purple-dot',
        'yellow' => 'yellow-dot',
    ];
    return $map[$color] ?? 'blue-dot';
}
?>

<header class="page-header">
    <h1>Dashboard Overview</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'there'); ?>! Here's what's happening.</p>
</header>

<!-- Stat Cards -->
<div class="grid-row-4">
    <div class="card stat-card">
        <div class="icon">📁</div>
        <h3><?php echo $totalProjects; ?></h3>
        <p>Total Projects</p>
    </div>
    <div class="card stat-card">
        <div class="icon">📈</div>
        <h3><?php echo $activeProjects; ?></h3>
        <p>Active Projects</p>
    </div>
    <div class="card stat-card">
        <div class="icon">👥</div>
        <h3><?php echo $totalContacts; ?></h3>
        <p>Total Contacts</p>
    </div>
    <div class="card stat-card">
        <div class="icon">🎯</div>
        <h3><?php echo $activeLeads; ?></h3>
        <p>Active Leads</p>
    </div>
</div>

<!-- Recent Contacts + Upcoming Events -->
<div class="grid-row-2">

    <div class="card list-card">
        <div class="card-header">
            <h3>Recent Contacts</h3>
            <a href="javascript:void(0);" onclick="switchPage('contacts-view')">View all</a>
        </div>
        <?php if (empty($recentContacts)): ?>
            <p style="color:var(--text-muted);font-size:0.9rem;">No contacts yet.</p>
        <?php else: ?>
            <?php foreach ($recentContacts as $contact): ?>
                <div class="list-item">
                    <span class="dot <?php echo statusDotClass($contact['type']); ?>"></span>
                    <div class="item-details">
                        <h4><?php echo htmlspecialchars($contact['name']); ?></h4>
                        <p><?php echo htmlspecialchars($contact['type']); ?>
                            <?php if ($contact['status']): ?>
                                &mdash; <span class="tag <?php echo strtolower(htmlspecialchars($contact['status'])); ?>"><?php echo htmlspecialchars($contact['status']); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card list-card">
        <div class="card-header">
            <h3>Upcoming Events</h3>
            <a href="javascript:void(0);" onclick="switchPage('calendar-view')">View calendar</a>
        </div>
        <?php if (empty($upcomingEvents)): ?>
            <p style="color:var(--text-muted);font-size:0.9rem;">No upcoming events.</p>
        <?php else: ?>
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="list-item">
                    <span class="dot <?php echo eventDotClass($event['color']); ?>"></span>
                    <div class="item-details">
                        <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                    </div>
                    <span class="date"><?php echo date('M j', strtotime($event['event_date'])); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Bottom Stats -->
<div class="grid-row-3">
    <div class="card">
        <p class="card-subtitle">⏱️ Total Hours Estimated</p>
        <h2><?php echo $totalHours; ?></h2>
        <p class="card-desc">Across all projects</p>
    </div>
    <div class="card">
        <p class="card-subtitle">🗓️ In Planning</p>
        <h2><?php echo $planningProjects; ?></h2>
        <p class="card-desc">Projects being scoped</p>
    </div>
    <div class="card">
        <p class="card-subtitle">📈 Completion Rate</p>
        <h2><?php echo $completionRate; ?>%</h2>
        <p class="card-desc"><?php echo $completedProjects; ?> of <?php echo $totalProjects; ?> projects completed</p>
    </div>
</div>
