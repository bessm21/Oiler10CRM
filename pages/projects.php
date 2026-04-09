<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create_project") {
    $projectNo = trim($_POST["project_no"] ?? "");
    $dateCreated = trim($_POST["date_created"] ?? "");
    $clientName = trim($_POST["client_name"] ?? "");
    $projectName = trim($_POST["project_name"] ?? "");
    $objectives = trim($_POST["objectives"] ?? "");
    $outOfScope = trim($_POST["out_of_scope"] ?? "");
    $estimatedHours = trim($_POST["estimated_hours"] ?? "0");
    $status = "active";
    $wbsAttached = ($_POST["wbs_attached"] ?? "false") === "true";
    $wbsLink = trim($_POST["wbs_link"] ?? "");

    $deliverablesLines = array_filter(array_map('trim', explode("\n", $_POST["deliverables_text"] ?? "")));
    $tasksLines = array_filter(array_map('trim', explode("\n", $_POST["tasks_text"] ?? "")));
    $assumptionsLines = array_filter(array_map('trim', explode("\n", $_POST["assumptions_text"] ?? "")));

    $deliverables = array();
    foreach ($deliverablesLines as $index => $line) {
        $deliverables[] = array(
                "no" => $index + 1,
                "description" => $line
        );
    }

    $tasks = array();
    foreach ($tasksLines as $index => $line) {
        $tasks[] = array(
                "no" => $index + 1,
                "description" => $line,
                "deliverableNo" => 1
        );
    }

    $assumptions = array();
    foreach ($assumptionsLines as $index => $line) {
        $assumptions[] = array(
                "no" => $index + 1,
                "assumption" => $line
        );
    }

    $constraints = array(
            "projectStartDate" => trim($_POST["project_start_date"] ?? ""),
            "launchDate" => trim($_POST["launch_date"] ?? ""),
            "projectEndDate" => trim($_POST["project_end_date"] ?? ""),
            "hardDeadlines" => trim($_POST["hard_deadlines"] ?? ""),
            "keyMilestones" => trim($_POST["key_milestones"] ?? ""),
            "budgetConstraints" => trim($_POST["budget_constraints"] ?? ""),
            "qualityConstraints" => trim($_POST["quality_constraints"] ?? ""),
            "equipmentConstraints" => trim($_POST["equipment_constraints"] ?? ""),
            "regulatoryConstraints" => trim($_POST["regulatory_constraints"] ?? "")
    );

    $approvals = array(
            "agentName" => trim($_POST["agent_name"] ?? ""),
            "agentDate" => trim($_POST["agent_date"] ?? ""),
            "clientName" => trim($_POST["approval_client_name"] ?? ""),
            "clientDate" => trim($_POST["client_date"] ?? ""),
            "agentSignature" => "",
            "clientSignature" => ""
    );

    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO projects (
                project_no,
                date_created,
                client_name,
                project_name,
                objectives,
                deliverables,
                tasks,
                wbs_attached,
                wbs_link,
                out_of_scope,
                assumptions,
                constraints,
                estimated_hours,
                approvals,
                status,
                user_id
            ) VALUES (
                :project_no,
                :date_created,
                :client_name,
                :project_name,
                :objectives,
                :deliverables,
                :tasks,
                :wbs_attached,
                :wbs_link,
                :out_of_scope,
                :assumptions,
                :constraints,
                :estimated_hours,
                :approvals,
                :status,
                :user_id
            )
        ");

        $insertStmt->execute(array(
                ':project_no' => $projectNo,
                ':date_created' => $dateCreated,
                ':client_name' => $clientName,
                ':project_name' => $projectName,
                ':objectives' => $objectives,
                ':deliverables' => json_encode($deliverables),
                ':tasks' => json_encode($tasks),
                ':wbs_attached' => $wbsAttached ? 'true' : 'false',
                ':wbs_link' => $wbsLink,
                ':out_of_scope' => $outOfScope,
                ':assumptions' => json_encode($assumptions),
                ':constraints' => json_encode($constraints),
                ':estimated_hours' => $estimatedHours,
                ':approvals' => json_encode($approvals),
                ':status' => $status,
                ':user_id' => $_SESSION['user_id'] ?? null
        ));

        header("Location: index.php?page=projects");
        exit;
    } catch (PDOException $e) {
        echo "<div class='empty-message'>Create project error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
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

$filter = isset($_GET['status']) ? trim($_GET['status']) : "All Projects";

$filteredProjects = array();
$activeCount = 0;
$completedCount = 0;

foreach ($projects as $project) {
    $statusLower = strtolower(trim($project["status"]));

    if ($statusLower === "completed") {
        $completedCount++;
    } else {
        $activeCount++;
    }

    if (
            $filter === "All Projects" ||
            ($filter === "Active" && $statusLower !== "completed") ||
            ($filter === "Completed" && $statusLower === "completed")
    ) {
        $filteredProjects[] = $project;
    }
}
?>

<div class="page-header-row">
    <div>
        <h1>Projects</h1>
        <p><?php echo count($projects); ?> total projects • <?php echo $activeCount; ?> active</p>
    </div>

    <button type="button" class="new-project-btn" onclick="openNewProjectModal()">
        + New Project
    </button>
</div>

<div class="project-filters">
    <a href="index.php?page=projects&status=All Projects" class="filter-btn <?php echo $filter === 'All Projects' ? 'active' : ''; ?>">All Projects</a>
    <a href="index.php?page=projects&status=Active" class="filter-btn <?php echo $filter === 'Active' ? 'active' : ''; ?>">Active</a>
    <a href="index.php?page=projects&status=Completed" class="filter-btn <?php echo $filter === 'Completed' ? 'active' : ''; ?>">Completed</a>
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

                $statusLower = strtolower(trim($project["status"]));
                $isCompleted = ($statusLower === "completed");
                ?>
                <div class="project-card">
                    <div class="project-header">
                        <div class="project-title-row">
                            <div class="project-icon">🗂</div>
                            <div>
                                <p class="project-title-text">
                                    <a class="project-link" href="pages/project_detail.php?id=<?php echo urlencode($project["id"]); ?>&tab=overview">
                                        <?php echo htmlspecialchars($project["project_name"]); ?>
                                    </a>

                                    <?php if ($isCompleted): ?>
                                        <span class="badge badge-completed">completed</span>
                                    <?php else: ?>
                                        <span class="badge badge-active">active</span>
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
            <div class="status-row status-completed">
                <span>Completed</span>
                <strong><?php echo $completedCount; ?></strong>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="newProjectModal">
    <div class="modal-content" style="max-width: 1100px;">
        <h3>New Project Scope</h3>

        <form method="POST" class="popup-form">
            <input type="hidden" name="action" value="create_project">

            <div class="scope-form-grid">
                <div class="scope-card">
                    <h4>Project No.</h4>
                    <input type="text" name="project_no" required>
                </div>

                <div class="scope-card">
                    <h4>Date Created</h4>
                    <input type="date" name="date_created" required>
                </div>

                <div class="scope-card">
                    <h4>Client Name</h4>
                    <input
                            type="text"
                            name="client_name"
                            placeholder="Enter client name"
                            required
                    >
                </div>

                <div class="scope-card">
                    <h4>Project Name</h4>
                    <input
                            type="text"
                            name="project_name"
                            placeholder="Enter project name"
                            required
                    >
                </div>

                <div class="scope-card full-width">
                    <h4>Project Objectives</h4>
                    <textarea name="objectives" rows="4" required></textarea>
                </div>

                <div class="scope-card full-width">
                    <h4>Deliverables</h4>
                    <textarea name="deliverables_text" rows="4" placeholder="One deliverable per line"></textarea>
                </div>

                <div class="scope-card full-width">
                    <h4>Tasks</h4>
                    <textarea name="tasks_text" rows="4" placeholder="One task per line"></textarea>
                </div>

                <div class="scope-card">
                    <h4>WBS Attached</h4>
                    <select name="wbs_attached">
                        <option value="false">No</option>
                        <option value="true">Yes</option>
                    </select>
                </div>

                <div class="scope-card">
                    <h4>WBS Link</h4>
                    <input type="text" name="wbs_link">
                </div>

                <div class="scope-card full-width">
                    <h4>Out of Scope</h4>
                    <textarea name="out_of_scope" rows="3"></textarea>
                </div>

                <div class="scope-card full-width">
                    <h4>Assumptions</h4>
                    <textarea name="assumptions_text" rows="3" placeholder="One assumption per line"></textarea>
                </div>

                <div class="scope-card">
                    <h4>Project Start Date</h4>
                    <input type="date" name="project_start_date">
                </div>

                <div class="scope-card">
                    <h4>Launch Date</h4>
                    <input type="date" name="launch_date">
                </div>

                <div class="scope-card">
                    <h4>Project End Date</h4>
                    <input type="date" name="project_end_date">
                </div>

                <div class="scope-card">
                    <h4>Hard Deadlines</h4>
                    <input type="text" name="hard_deadlines">
                </div>

                <div class="scope-card">
                    <h4>Key Milestones</h4>
                    <input type="text" name="key_milestones">
                </div>

                <div class="scope-card">
                    <h4>Budget Constraints</h4>
                    <input type="text" name="budget_constraints">
                </div>

                <div class="scope-card">
                    <h4>Quality Constraints</h4>
                    <input type="text" name="quality_constraints">
                </div>

                <div class="scope-card">
                    <h4>Equipment Constraints</h4>
                    <input type="text" name="equipment_constraints">
                </div>

                <div class="scope-card">
                    <h4>Regulatory Constraints</h4>
                    <input type="text" name="regulatory_constraints">
                </div>

                <div class="scope-card">
                    <h4>Estimated Hours</h4>
                    <input type="number" name="estimated_hours" min="0">
                </div>

                <div class="scope-card">
                    <h4>Agent Name</h4>
                    <input type="text" name="agent_name">
                </div>

                <div class="scope-card">
                    <h4>Agent Date</h4>
                    <input type="date" name="agent_date">
                </div>

                <div class="scope-card">
                    <h4>Client Name (Approval)</h4>
                    <input type="text" name="approval_client_name">
                </div>

                <div class="scope-card">
                    <h4>Client Date</h4>
                    <input type="date" name="client_date">
                </div>
            </div>

            <div class="popup-buttons">
                <button type="submit" class="primary-btn">Create Project</button>
                <button type="button" class="cancel-btn" onclick="closeNewProjectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
    function openNewProjectModal() {
        document.getElementById('newProjectModal').style.display = 'flex';
    }

    function closeNewProjectModal() {
        document.getElementById('newProjectModal').style.display = 'none';
    }

    window.addEventListener('click', function(e) {
        const modal = document.getElementById('newProjectModal');
        if (e.target === modal) {
            closeNewProjectModal();
        }
    });
</script>