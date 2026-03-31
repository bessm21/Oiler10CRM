<?php
try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            project_no,
            date_created,
            client_name,
            project_name,
            objectives,
            deliverables,
            estimated_hours,
            status,
            constraints
        FROM projects
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='empty-message'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    return;
}

$filter = isset($_GET['status']) ? $_GET['status'] : "All";

$filteredProjects = array();
foreach ($projects as $project) {
    if ($filter === "All" || strtolower($project["status"]) === strtolower($filter)) {
        $filteredProjects[] = $project;
    }
}

$totalHours = 0;
$totalDeliverables = 0;
$activeCount = 0;
$planningCount = 0;
$onHoldCount = 0;
$completedCount = 0;

foreach ($projects as $p) {
    $totalHours += (int)$p["estimated_hours"];

    $deliverables = json_decode($p["deliverables"], true);
    if (is_array($deliverables)) {
        $totalDeliverables += count($deliverables);
    }

    if (strtolower($p["status"]) === "active") {
        $activeCount++;
    } elseif (strtolower($p["status"]) === "planning") {
        $planningCount++;
    } elseif (strtolower($p["status"]) === "on hold") {
        $onHoldCount++;
    } elseif (strtolower($p["status"]) === "completed") {
        $completedCount++;
    }
}
?>

<header class="page-header">
    <h1>Projects</h1>
    <p><?php echo count($projects); ?> total projects • <?php echo $activeCount; ?> active</p>
</header>

<div class="top-bar">
    <div></div>
    <a href="#" class="new-project-btn">+ New Project</a>
</div>

<div class="filter-buttons">
    <a href="index.php?page=projects&status=All" class="<?php if ($filter == 'All') echo 'active-filter'; ?>">All Projects</a>
    <a href="index.php?page=projects&status=Active" class="<?php if ($filter == 'Active') echo 'active-filter'; ?>">Active</a>
    <a href="index.php?page=projects&status=Planning" class="<?php if ($filter == 'Planning') echo 'active-filter'; ?>">Planning</a>
    <a href="index.php?page=projects&status=Completed" class="<?php if ($filter == 'Completed') echo 'active-filter'; ?>">Completed</a>
</div>

<div class="main-layout">
    <div class="left-column">
        <?php if (count($filteredProjects) > 0): ?>
            <?php foreach ($filteredProjects as $project): ?>
                <?php
                $deliverables = json_decode($project["deliverables"], true);
                $deliverableCount = is_array($deliverables) ? count($deliverables) : 0;

                $constraints = json_decode($project["constraints"], true);
                $launchDate = is_array($constraints) && isset($constraints["launchDate"])
                    ? $constraints["launchDate"]
                    : "N/A";

                $statusLower = strtolower($project["status"]);
                ?>
                <div class="project-card">
                    <div class="project-header">
                        <div class="project-title-row">
                            <div class="project-icon">🗂</div>
                            <div>
                                <p class="project-title-text">
                                    <a class="project-link" href="pages/project_detail.php?id=<?php echo urlencode($project["id"]); ?>">
                                        <?php echo htmlspecialchars($project["project_name"]); ?>
                                    </a>

                                    <?php if ($statusLower === "active"): ?>
                                        <span class="badge badge-active">active</span>
                                    <?php elseif ($statusLower === "planning"): ?>
                                        <span class="badge badge-planning">planning</span>
                                    <?php elseif ($statusLower === "on hold"): ?>
                                        <span class="badge badge-hold">on hold</span>
                                    <?php else: ?>
                                        <span class="badge badge-completed">completed</span>
                                    <?php endif; ?>
                                </p>

                                <p class="project-subtitle">
                                    <?php echo htmlspecialchars($project["client_name"]); ?> • <?php echo htmlspecialchars($project["project_no"]); ?>
                                </p>
                            </div>
                        </div>
                        <div class="project-arrow">›</div>
                    </div>

                    <p class="project-description"><?php echo htmlspecialchars($project["objectives"]); ?></p>

                    <div class="project-meta">
                        <span><strong>Created:</strong> <?php echo htmlspecialchars($project["date_created"]); ?></span>
                        <span><strong>Launch:</strong> <?php echo htmlspecialchars($launchDate); ?></span>
                        <span><strong>Deliverables:</strong> <?php echo $deliverableCount; ?></span>
                        <span><strong>Hours:</strong> <?php echo (int)$project["estimated_hours"]; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-message">
                No projects found for this filter.
            </div>
        <?php endif; ?>
    </div>

    <div class="right-column">
        <div class="sidebar-card">
            <h3>Status Overview</h3>
            <div class="status-row status-active">
                <span>Active</span>
                <strong><?php echo $activeCount; ?></strong>
            </div>
            <div class="status-row status-planning">
                <span>Planning</span>
                <strong><?php echo $planningCount; ?></strong>
            </div>
            <div class="status-row status-hold">
                <span>On Hold</span>
                <strong><?php echo $onHoldCount; ?></strong>
            </div>
            <div class="status-row status-completed">
                <span>Completed</span>
                <strong><?php echo $completedCount; ?></strong>
            </div>
        </div>

        <div class="sidebar-card">
            <h3>Project Metrics</h3>
            <div class="metric-label">Total Hours Estimated</div>
            <div class="metric-value"><?php echo $totalHours; ?></div>

            <div class="metric-label">Total Deliverables</div>
            <div class="metric-value"><?php echo $totalDeliverables; ?></div>

            <div class="metric-label">Avg. Project Duration</div>
            <div class="metric-value" style="font-size:32px;">N/A</div>
        </div>
    </div>
</div>