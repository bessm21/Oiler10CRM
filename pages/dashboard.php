<?php
require_once 'config.php';

$isAdmin = $isAdmin ?? false;  // set by index.php

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
$pendingUsers      = [];

// ==========================================
// ISOLATED DASHBOARD QUERIES
// ==========================================

// --- 1. Project Stats (Isolated) ---
try {
    $totalProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

    // LOWER() ensures it counts correctly whether it's 'Active' or 'active'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE LOWER(status) = ?");

    $stmt->execute(['active']);
    $activeProjects = (int)$stmt->fetchColumn();

    $stmt->execute(['planning']);
    $planningProjects = (int)$stmt->fetchColumn();

    $stmt->execute(['completed']);
    $completedProjects = (int)$stmt->fetchColumn();

    if ($totalProjects > 0) {
        $completionRate = round(($completedProjects / $totalProjects) * 100);
    }

    // THE FIX: We must cast the text column to a number before running SUM()
    // NULLIF prevents crashes if a cell is completely blank
    $totalHours = (int)$pdo->query("SELECT COALESCE(SUM(NULLIF(estimated_hours, '')::NUMERIC), 0) FROM projects")->fetchColumn();

} catch (Exception $e) {
    // Fail silently without breaking the rest of the page
}

// --- 2. Contact Stats (Isolated) ---
try {
    // Trying the lowercase table name first based on standard Supabase schema
    $totalContacts = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE LOWER(type) = ?");
    $stmt2->execute(['lead']);
    $activeLeads = (int)$stmt2->fetchColumn();

    $recentContacts = $pdo->query("SELECT name, type, status FROM contacts ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if Supabase forced the uppercase "Contacts"
    try {
        $totalContacts = (int)$pdo->query('SELECT COUNT(*) FROM "Contacts"')->fetchColumn();
        $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM "Contacts" WHERE LOWER(type) = ?');
        $stmt2->execute(['lead']);
        $activeLeads = (int)$stmt2->fetchColumn();
        $recentContacts = $pdo->query('SELECT name, type, status FROM "Contacts" ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $innerE) {
        // Fail silently
    }
}

// --- 3. Upcoming Events (Isolated) ---
try {
    $upcomingEvents = $pdo->query(
            "SELECT title, event_date, color FROM calendar WHERE event_date >= CURRENT_DATE ORDER BY event_date ASC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fail silently
}

// --- 4. Pending Users (Isolated) ---
try {
    if ($isAdmin) {
        $pendingUsers = $pdo->query(
                "SELECT user_id, username, email FROM users WHERE status = 'pending' ORDER BY user_id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Fail silently
}

function statusDotClass(string $type): string {
    return $type === 'Client' ? 'blue-dot' : 'green-dot';
}

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
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'there'); ?>!<?php if ($isAdmin): ?> <span class="admin-badge">Admin</span><?php endif; ?> Here's what's happening.</p>
    </header>

    <div class="grid-row-4">
        <div class="card stat-card">
            <div class="icon">📁</div>
            <h3 id="dash-total-projects"><?php echo $totalProjects; ?></h3>
            <p>Total Projects</p>
        </div>
        <div class="card stat-card">
            <div class="icon">📈</div>
            <h3 id="dash-active-projects"><?php echo $activeProjects; ?></h3>
            <p>Active Projects</p>
        </div>
        <div class="card stat-card">
            <div class="icon">👥</div>
            <h3 id="dash-total-contacts"><?php echo $totalContacts; ?></h3>
            <p>Total Contacts</p>
        </div>
        <div class="card stat-card">
            <div class="icon">🎯</div>
            <h3 id="dash-active-leads"><?php echo $activeLeads; ?></h3>
            <p>Active Leads</p>
        </div>
    </div>

    <div class="grid-row-2">

        <div class="card list-card">
            <div class="card-header">
                <h3>Recent Contacts</h3>
                <a href="javascript:void(0);" onclick="switchPage('contacts-view')">View all</a>
            </div>
            <div id="dash-recent-contacts">
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
        </div>

        <div class="card list-card">
            <div class="card-header">
                <h3>Upcoming Events</h3>
                <a href="javascript:void(0);" onclick="switchPage('calendar-view')">View calendar</a>
            </div>
            <div id="dash-upcoming-events">
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

    </div>

    <div class="grid-row-3">
        <div class="card">
            <p class="card-subtitle">⏱️ Total Hours Estimated</p>
            <h2 id="dash-total-hours"><?php echo $totalHours; ?></h2>
            <p class="card-desc">Across all projects</p>
        </div>
        <div class="card">
            <p class="card-subtitle">🗓️ In Planning</p>
            <h2 id="dash-planning-projects"><?php echo $planningProjects; ?></h2>
            <p class="card-desc">Projects being scoped</p>
        </div>
        <div class="card">
            <p class="card-subtitle">📈 Completion Rate</p>
            <h2 id="dash-completion-rate"><?php echo $completionRate; ?>%</h2>
            <p class="card-desc" id="dash-completion-desc"><?php echo $completedProjects; ?> of <?php echo $totalProjects; ?> projects completed</p>
        </div>
    </div>

<?php if ($isAdmin): ?>
    <div class="card admin-panel">
        <div class="card-header">
            <h3>🔐 Pending User Approvals</h3>
            <button class="admin-refresh-btn" onclick="reloadAdminPanel()">Refresh</button>
        </div>
        <div id="admin-pending-list">
            <?php if (empty($pendingUsers)): ?>
                <p style="color:var(--text-muted);font-size:0.9rem;">No pending users.</p>
            <?php else: ?>
                <?php foreach ($pendingUsers as $u): ?>
                    <div class="admin-user-row">
                        <div class="admin-user-info">
                            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                            <span><?php echo htmlspecialchars($u['email']); ?></span>
                        </div>
                        <div class="admin-user-actions">
                            <button class="admin-approve-btn" onclick="adminAction('approve', '<?php echo htmlspecialchars($u['user_id'], ENT_QUOTES); ?>')">Approve</button>
                            <button class="admin-deny-btn"    onclick="adminAction('deny',    '<?php echo htmlspecialchars($u['user_id'], ENT_QUOTES); ?>')">Deny</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>