<?php
session_start();
$page = 'projects';
$isAdmin = (($_SESSION['role'] ?? 'user') === 'admin');
require_once "../config.php";

$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if ($id === '') {
    die("Project ID is missing.");
}

date_default_timezone_set('America/New_York');

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM projects
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(array(':id' => $id));
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    $constraints = json_decode($project["constraints"] ?? "{}", true);
    $deliverables = json_decode($project["deliverables"] ?? "[]", true);
    $tasks = json_decode($project["tasks"] ?? "[]", true);
    $assumptions = json_decode($project["assumptions"] ?? "[]", true);
    $approvals = json_decode($project["approvals"] ?? "{}", true);

    $deliverablesText = "";
    if (!empty($deliverables)) {
        $deliverableLines = array();
        foreach ($deliverables as $item) {
            $deliverableLines[] = $item["description"] ?? "";
        }
        $deliverablesText = implode("\n", $deliverableLines);
    }

    $tasksText = "";
    if (!empty($tasks)) {
        $taskLines = array();
        foreach ($tasks as $item) {
            $taskLines[] = $item["description"] ?? "";
        }
        $tasksText = implode("\n", $taskLines);
    }

    $assumptionsText = "";
    if (!empty($assumptions)) {
        $assumptionLines = array();
        foreach ($assumptions as $item) {
            $assumptionLines[] = $item["assumption"] ?? "";
        }
        $assumptionsText = implode("\n", $assumptionLines);
    }

    if (!$project) {
        die("Project not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

$objectivesStmt = $pdo->prepare("
    SELECT id, objective_text, is_completed, sort_order
    FROM project_objectives
    WHERE project_id = :project_id
    ORDER BY is_completed ASC, sort_order ASC, created_at ASC
");
$objectivesStmt->execute(array(':project_id' => $project["id"]));
$objectives = $objectivesStmt->fetchAll(PDO::FETCH_ASSOC);

$filesStmt = $pdo->prepare("
    SELECT id, file_name, file_path, uploaded_at
    FROM project_files
    WHERE project_id = :project_id
    ORDER BY uploaded_at DESC
");
$filesStmt->execute(array(':project_id' => $project["id"]));
$projectFiles = $filesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "send_chat_message") {
    $tabName = $_POST["tab_name"];
    $messageText = trim($_POST["message_text"]);
    $username = isset($_SESSION["username"]) ? $_SESSION["username"] : "User";

    if ($messageText !== "") {
        $chatStmt = $pdo->prepare("
            INSERT INTO project_chat_messages (project_id, tab_name, username, message_text)
            VALUES (:project_id, :tab_name, :username, :message_text)
        ");
        $chatStmt->execute(array(
                ':project_id' => $project["id"],
                ':tab_name' => $tabName,
                ':username' => $username,
                ':message_text' => $messageText
        ));
    }

    header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=" . urlencode($tabName));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_chat_message") {
    $messageId = $_POST["message_id"] ?? "";
    $tabName = $_POST["tab_name"] ?? "overview";

    if ($messageId !== "") {
        $deleteChatStmt = $pdo->prepare("
            DELETE FROM project_chat_messages
            WHERE id = :id AND project_id = :project_id
        ");
        $deleteChatStmt->execute(array(
                ':id' => $messageId,
                ':project_id' => $project["id"]
        ));
    }

    header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=" . urlencode($tabName));
    exit;
}

$currentTab = isset($_GET["tab"]) ? $_GET["tab"] : "overview";

$chatMessagesStmt = $pdo->prepare("
    SELECT id, username, message_text, created_at
    FROM project_chat_messages
    WHERE project_id = :project_id AND tab_name = :tab_name
    ORDER BY created_at ASC
");
$chatMessagesStmt->execute(array(
        ':project_id' => $project["id"],
        ':tab_name' => $currentTab
));
$chatMessages = $chatMessagesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["action"]) ? $_POST["action"] : "";

    if ($action === "update_scope") {
        $projectNo = trim($_POST["project_no"] ?? "");
        $dateCreated = trim($_POST["date_created"] ?? "");
        $clientName = trim($_POST["client_name"] ?? "");
        $projectName = trim($_POST["project_name"] ?? "");
        $objectives = trim($_POST["objectives"] ?? "");
        $outOfScope = trim($_POST["out_of_scope"] ?? "");
        $estimatedHours = trim($_POST["estimated_hours"] ?? "0");
        $wbsAttached = trim($_POST["wbs_attached"] ?? "false");
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

        $updateStmt = $pdo->prepare("
        UPDATE projects
        SET project_no = :project_no,
            date_created = :date_created,
            client_name = :client_name,
            project_name = :project_name,
            objectives = :objectives,
            deliverables = :deliverables,
            tasks = :tasks,
            wbs_attached = :wbs_attached,
            wbs_link = :wbs_link,
            out_of_scope = :out_of_scope,
            assumptions = :assumptions,
            constraints = :constraints,
            estimated_hours = :estimated_hours,
            approvals = :approvals,
            updated_at = now()
        WHERE id = :id
    ");

        $updateStmt->execute(array(
                ':project_no' => $projectNo,
                ':date_created' => $dateCreated,
                ':client_name' => $clientName,
                ':project_name' => $projectName,
                ':objectives' => $objectives,
                ':deliverables' => json_encode($deliverables),
                ':tasks' => json_encode($tasks),
                ':wbs_attached' => $wbsAttached,
                ':wbs_link' => $wbsLink,
                ':out_of_scope' => $outOfScope,
                ':assumptions' => json_encode($assumptions),
                ':constraints' => json_encode($constraints),
                ':estimated_hours' => $estimatedHours,
                ':approvals' => json_encode($approvals),
                ':id' => $project["id"]
        ));

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=scope");
        exit;
    }

    if ($action === "add_objective") {
        $objectiveText = trim($_POST["objective_text"] ?? "");

        if ($objectiveText !== "") {
            $insertStmt = $pdo->prepare("
                INSERT INTO project_objectives (project_id, objective_text, sort_order)
                VALUES (:project_id, :objective_text, 0)
            ");
            $insertStmt->execute(array(
                    ':project_id' => $project["id"],
                    ':objective_text' => $objectiveText
            ));
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=objectives");
        exit;
    }

    if ($action === "complete_objectives" && !empty($_POST["objective_ids"])) {
        foreach ($_POST["objective_ids"] as $objectiveId) {
            $updateStmt = $pdo->prepare("
                UPDATE project_objectives
                SET is_completed = true
                WHERE id = :id AND project_id = :project_id
            ");
            $updateStmt->execute(array(
                    ':id' => $objectiveId,
                    ':project_id' => $project["id"]
            ));
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=objectives");
        exit;
    }

    if ($action === "edit_objective") {
        $objectiveId = $_POST["objective_id"] ?? "";
        $objectiveText = trim($_POST["objective_text"] ?? "");

        if ($objectiveId !== "" && $objectiveText !== "") {
            $editStmt = $pdo->prepare("
                UPDATE project_objectives
                SET objective_text = :objective_text
                WHERE id = :id AND project_id = :project_id
            ");
            $editStmt->execute(array(
                    ':objective_text' => $objectiveText,
                    ':id' => $objectiveId,
                    ':project_id' => $project["id"]
            ));
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=objectives");
        exit;
    }

    if ($action === "delete_objective") {
        $objectiveId = $_POST["objective_id"] ?? "";

        if ($objectiveId !== "") {
            $deleteStmt = $pdo->prepare("
                DELETE FROM project_objectives
                WHERE id = :id AND project_id = :project_id
            ");
            $deleteStmt->execute(array(
                    ':id' => $objectiveId,
                    ':project_id' => $project["id"]
            ));
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=objectives");
        exit;
    }

    if ($action === "complete_project") {
        $completeProjectStmt = $pdo->prepare("
        UPDATE projects
        SET status = 'completed'
        WHERE id = :id
    ");
        $completeProjectStmt->execute(array(
                ':id' => $project["id"]
        ));

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=overview");
        exit;
    }

    if ($action === "mark_incomplete") {
        $objectiveId = $_POST["objective_id"] ?? "";

        if ($objectiveId !== "") {
            $incompleteStmt = $pdo->prepare("
                UPDATE project_objectives
                SET is_completed = false
                WHERE id = :id AND project_id = :project_id
            ");
            $incompleteStmt->execute(array(
                    ':id' => $objectiveId,
                    ':project_id' => $project["id"]
            ));
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=objectives");
        exit;
    }

    if ($action === "reorder_objectives" && !empty($_POST["objective_order"])) {
        $objectiveOrder = $_POST["objective_order"];

        foreach ($objectiveOrder as $index => $objectiveId) {
            $reorderStmt = $pdo->prepare("
                UPDATE project_objectives
                SET sort_order = :sort_order
                WHERE id = :id AND project_id = :project_id
            ");
            $reorderStmt->execute(array(
                    ':sort_order' => $index,
                    ':id' => $objectiveId,
                    ':project_id' => $project["id"]
            ));
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=objectives");
        exit;
    }

    if ($action === "delete_chat_message") {
        $messageId = $_POST["message_id"] ?? "";

        if ($messageId !== "") {
            $deleteChatStmt = $pdo->prepare("
                DELETE FROM project_chat_messages
                WHERE id = :id AND project_id = :project_id
            ");
            $deleteChatStmt->execute(array(
                    ':id' => $messageId,
                    ':project_id' => $project["id"]
            ));
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=" . urlencode($currentTab));
        exit;
    }

    if ($action === "send_chat_message") {
        $tabName = $_POST["tab_name"] ?? "overview";
        $messageText = trim($_POST["message_text"] ?? "");
        $username = isset($_SESSION["username"]) ? $_SESSION["username"] : "User";

        if ($messageText !== "") {
            $chatStmt = $pdo->prepare("
                INSERT INTO project_chat_messages (project_id, tab_name, username, message_text)
                VALUES (:project_id, :tab_name, :username, :message_text)
            ");
            $chatStmt->execute(array(
                    ':project_id' => $project["id"],
                    ':tab_name' => $tabName,
                    ':username' => $username,
                    ':message_text' => $messageText
            ));
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=" . urlencode($tabName));
        exit;
    }

    if ($action === "upload_project_file" && isset($_FILES["project_file"])) {
        if ($_FILES["project_file"]["error"] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . "/uploads/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = $_FILES["project_file"]["name"];
            $tmpName = $_FILES["project_file"]["tmp_name"];
            $storedName = time() . "_" . basename($originalName);
            $fullPath = $uploadDir . $storedName;

            if (move_uploaded_file($tmpName, $fullPath)) {
                $insertFileStmt = $pdo->prepare("
                INSERT INTO project_files (project_id, file_name, file_path)
                VALUES (:project_id, :file_name, :file_path)
            ");
                $insertFileStmt->execute(array(
                        ':project_id' => $project["id"],
                        ':file_name' => $originalName,
                        ':file_path' => "pages/uploads/" . $storedName
                ));
            }
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=files");
        exit;
    }

    if ($action === "delete_file") {
        $fileId = $_POST["file_id"] ?? "";

        if ($fileId !== "") {
            $getFileStmt = $pdo->prepare("
            SELECT file_path
            FROM project_files
            WHERE id = :id AND project_id = :project_id
        ");
            $getFileStmt->execute(array(
                    ':id' => $fileId,
                    ':project_id' => $project["id"]
            ));
            $fileRow = $getFileStmt->fetch(PDO::FETCH_ASSOC);

            if ($fileRow) {
                $fullPath = dirname(__DIR__) . "/" . $fileRow["file_path"];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }

                $deleteFileStmt = $pdo->prepare("
                DELETE FROM project_files
                WHERE id = :id AND project_id = :project_id
            ");
                $deleteFileStmt->execute(array(
                        ':id' => $fileId,
                        ':project_id' => $project["id"]
                ));
            }
        }

        header("Location: project_detail.php?id=" . urlencode($project["id"]) . "&tab=files");
        exit;
    }
}

$fileStmt = $pdo->prepare("
    SELECT id, file_name, file_path, uploaded_at
    FROM project_files
    WHERE project_id = :project_id
    ORDER BY uploaded_at DESC
");
$fileStmt->execute(array(
        ':project_id' => $project["id"]
));
$projectFiles = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

$deliverables = json_decode($project["deliverables"], true);
$tasks = json_decode($project["tasks"], true);
$constraints = json_decode($project["constraints"], true);
$assumptions = json_decode($project["assumptions"], true);
$approvals = json_decode($project["approvals"], true);

$launchDate = isset($constraints["launchDate"]) ? $constraints["launchDate"] : "";
$projectStartDate = isset($constraints["projectStartDate"]) ? $constraints["projectStartDate"] : "";
$projectEndDate = isset($constraints["projectEndDate"]) ? $constraints["projectEndDate"] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Detail | Oiler 10 CRM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .detail-page {
            max-width: 1000px;
            margin: 0 auto;
        }

        .detail-card {
            background: white;
            border: 1px solid #d7dce2;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 18px;
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 600;
        }

        .detail-title {
            margin: 0 0 10px 0;
            font-size: 34px;
            color: #1f2937;
        }

        .detail-subtitle {
            margin: 0 0 18px 0;
            color: #6b7280;
            font-size: 16px;
        }

        .detail-description {
            margin: 20px 0 24px 0;
            line-height: 1.7;
            color: #374151;
            font-size: 16px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .detail-item {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }

        .detail-label {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .detail-value {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }

        ul {
            margin: 0;
            padding-left: 18px;
        }

        li {
            margin-bottom: 6px;
        }

        @media (max-width: 700px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <?php
    $navPrefix = '../';
    include "../components/sidebar.php";
    ?>

    <main class="main-content">
        <div class="detail-page">
            <a href="../index.php?page=projects" class="back-link">← Back to Projects</a>

            <div class="detail-card">
                <h1 class="detail-title"><?php echo htmlspecialchars($project["project_name"]); ?></h1>

                <p class="detail-subtitle">
                    <?php echo htmlspecialchars($project["client_name"]); ?> • <?php echo htmlspecialchars($project["project_no"]); ?>
                </p>

                <p class="detail-description">
                    <?php echo htmlspecialchars($project["objectives"]); ?>
                </p>

                <div class="project-tabs">
                    <button class="project-tab <?php if ($currentTab === 'overview') echo 'active'; ?>" data-tab="overview" onclick="showProjectTab('overview')">Overview</button>
                    <button class="project-tab <?php if ($currentTab === 'objectives') echo 'active'; ?>" data-tab="objectives" onclick="showProjectTab('objectives')">Objectives</button>
                    <button class="project-tab <?php if ($currentTab === 'scope') echo 'active'; ?>" data-tab="scope" onclick="showProjectTab('scope')">Scope</button>
                    <button class="project-tab <?php if ($currentTab === 'files') echo 'active'; ?>" data-tab="files" onclick="showProjectTab('files')">Files</button>
                </div>

                <div class="project-detail-layout">
                    <div class="project-detail-main">

                        <div id="tab-overview" class="project-tab-panel" style="<?php if ($currentTab !== 'overview') echo 'display:none;'; ?>">
                            <?php if (strtolower(trim($project["status"])) !== "completed"): ?>
                                <div style="margin-bottom: 18px;">
                                    <button type="button" class="primary-btn" onclick="openCompleteProjectModal()">
                                        Mark Project As Completed
                                    </button>
                                </div>
                            <?php endif; ?>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value">
                                        <?php echo strtolower(trim($project["status"])) === "completed" ? "Completed" : "Active"; ?>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Date Created</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($project["date_created"]); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Launch Date</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($launchDate); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Project Start</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($projectStartDate); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Project End</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($projectEndDate); ?></div>
                                </div>
                            </div>
                        </div>

                        <div id="tab-objectives" class="project-tab-panel" style="<?php if ($currentTab !== 'objectives') echo 'display:none;'; ?>">
                            <div class="objective-toolbar">
                                <h3>Objectives</h3>
                                <div>
                                    <button class="primary-btn" type="button" onclick="toggleAddObjectiveForm()">+ Add Objective</button>
                                    <button class="primary-btn" type="button" id="markCompleteBtn" style="display:none;" onclick="submitCompleteObjectives()">Mark as Complete</button>
                                </div>
                            </div>

                            <form method="POST" id="addObjectiveForm" style="display:none; margin-bottom:16px;">
                                <input type="hidden" name="action" value="add_objective">
                                <textarea name="objective_text" placeholder="Enter objective..." style="width:100%; padding:12px; border-radius:10px; border:1px solid #d1d5db;"></textarea>
                                <div style="margin-top:10px; display:flex; gap:10px;">
                                    <button class="primary-btn" type="submit">Add Objective</button>
                                    <button class="cancel-btn" type="button" onclick="toggleAddObjectiveForm()">Cancel</button>
                                </div>
                            </form>

                            <form method="POST" id="completeObjectivesForm">
                                <input type="hidden" name="action" value="complete_objectives">

                                <div class="objective-list">
                                    <?php foreach ($objectives as $objective): ?>
                                        <?php if (!$objective["is_completed"]): ?>
                                            <div class="objective-item" draggable="true" data-id="<?php echo $objective["id"]; ?>">
                                                <div class="objective-drag">☰</div>
                                                <div class="objective-text"><?php echo htmlspecialchars($objective["objective_text"]); ?></div>

                                                <div class="objective-actions">
                                                    <button type="button" onclick="openEditObjectiveModal('<?php echo $objective["id"]; ?>', '<?php echo htmlspecialchars(addslashes($objective["objective_text"])); ?>')">✏️</button>
                                                    <button type="button" onclick="openDeleteObjectiveModal('<?php echo $objective["id"]; ?>')">🗑️</button>
                                                    <input type="checkbox" name="objective_ids[]" value="<?php echo $objective["id"]; ?>" onchange="toggleMarkCompleteBtn()">
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </form>

                            <form method="POST" id="reorderObjectivesForm">
                                <input type="hidden" name="action" value="reorder_objectives">
                                <div id="objective-order-inputs"></div>
                            </form>

                            <h3 style="margin-top:24px;">Completed</h3>
                            <div class="completed-objective-list">
                                <?php foreach ($objectives as $objective): ?>
                                    <?php if ($objective["is_completed"]): ?>
                                        <div class="objective-item completed-objective">
                                            <div class="objective-text"><?php echo htmlspecialchars($objective["objective_text"]); ?></div>
                                            <button type="button" class="completed-remove-btn" onclick="openMarkIncompleteModal('<?php echo $objective["id"]; ?>')">×</button>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="tab-scope" class="project-tab-panel" style="<?php if ($currentTab !== 'scope') echo 'display:none;'; ?>">
                            <form method="POST" id="scopeForm">
                                <input type="hidden" name="action" value="update_scope">

                                <div class="scope-actions">
                                    <button type="button" class="primary-btn" id="editScopeBtn" onclick="enableScopeEditing()">
                                        Edit Scope
                                    </button>

                                    <button type="submit" class="primary-btn" id="saveScopeBtn" style="display:none;">
                                        Save Scope
                                    </button>

                                    <button type="button" class="cancel-btn" id="cancelScopeBtn" style="display:none;" onclick="cancelScopeEditing()">
                                        Cancel
                                    </button>
                                </div>

                                <div class="scope-form-grid">
                                    <div class="scope-card">
                                        <h4>Project Name</h4>
                                        <input
                                                type="text"
                                                name="project_name"
                                                value="<?php echo htmlspecialchars($project["project_name"] ?? ""); ?>"
                                                class="scope-editable"
                                                readonly
                                        >
                                    </div>

                                    <div class="scope-card">
                                        <h4>Client Name</h4>
                                        <input
                                                type="text"
                                                name="client_name"
                                                value="<?php echo htmlspecialchars($project["client_name"] ?? ""); ?>"
                                                class="scope-editable"
                                                readonly
                                        >
                                    </div>

                                    <div class="scope-card">
                                        <h4>Project No.</h4>
                                        <input
                                                type="text"
                                                name="project_no"
                                                value="<?php echo htmlspecialchars($project["project_no"] ?? ""); ?>"
                                                class="scope-editable"
                                                readonly
                                        >
                                    </div>

                                    <div class="scope-card">
                                        <h4>Date Created</h4>
                                        <input
                                                type="date"
                                                name="date_created"
                                                value="<?php echo htmlspecialchars($project["date_created"] ?? ""); ?>"
                                                class="scope-editable"
                                                readonly
                                        >
                                    </div>

                                    <div class="scope-card full-width">
                                        <h4>Project Objectives</h4>
                                        <textarea
                                                name="objectives"
                                                class="scope-editable"
                                                readonly
                                        ><?php echo htmlspecialchars($project["objectives"] ?? ""); ?></textarea>
                                    </div>

                                    <div class="scope-card full-width">
                                        <h4>Deliverables</h4>
                                        <textarea
                                                name="deliverables_text"
                                                class="scope-editable"
                                                readonly
                                        ><?php echo htmlspecialchars($deliverablesText ?? ""); ?></textarea>
                                    </div>

                                    <div class="scope-card full-width">
                                        <h4>Tasks</h4>
                                        <textarea
                                                name="tasks_text"
                                                class="scope-editable"
                                                readonly
                                        ><?php echo htmlspecialchars($tasksText ?? ""); ?></textarea>
                                    </div>

                                    <div class="scope-card full-width">
                                        <h4>Out of Scope</h4>
                                        <textarea
                                                name="out_of_scope"
                                                class="scope-editable"
                                                readonly
                                        ><?php echo htmlspecialchars($project["out_of_scope"] ?? ""); ?></textarea>
                                    </div>

                                    <div class="scope-card full-width">
                                        <h4>Assumptions</h4>
                                        <textarea
                                                name="assumptions_text"
                                                class="scope-editable"
                                                readonly
                                        ><?php echo htmlspecialchars($assumptionsText ?? ""); ?></textarea>
                                    </div>

                                    <div class="scope-card">
                                        <h4>Project Start Date</h4>
                                        <input
                                                type="date"
                                                name="project_start_date"
                                                value="<?php echo htmlspecialchars($projectStartDate ?? ""); ?>"
                                                class="scope-editable"
                                                readonly
                                        >
                                    </div>

                                    <div class="scope-card">
                                        <h4>Launch / Go-Live Date</h4>
                                        <input
                                                type="date"
                                                name="launch_date"
                                                value="<?php echo htmlspecialchars($launchDate ?? ""); ?>"
                                                class="scope-editable"
                                                readonly
                                        >
                                    </div>

                                    <div class="scope-card">
                                        <h4>Project End Date</h4>
                                        <input
                                                type="date"
                                                name="project_end_date"
                                                value="<?php echo htmlspecialchars($projectEndDate ?? ""); ?>"
                                                class="scope-editable"
                                                readonly
                                        >
                                    </div>

                                    <div class="scope-card">
                                        <h4>Estimated Hours</h4>
                                        <input
                                                type="number"
                                                name="estimated_hours"
                                                value="<?php echo htmlspecialchars($project["estimated_hours"] ?? "0"); ?>"
                                                class="scope-editable"
                                                readonly
                                        >
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div id="tab-files" class="project-tab-panel" style="<?php if ($currentTab !== 'files') echo 'display:none;'; ?>">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_project_file">
                                <label class="file-upload-box">
                                    +
                                    <input type="file" name="project_file" style="display:none;" onchange="this.form.submit()">
                                </label>
                            </form>

                            <div class="uploaded-files-list">
                                <h3>Uploaded Files</h3>

                                <?php if (!empty($projectFiles)): ?>
                                    <div class="uploaded-files-list">
                                        <?php foreach ($projectFiles as $file): ?>
                                            <div class="uploaded-file-item">
                                                <a href="../<?php echo htmlspecialchars($file["file_path"]); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($file["file_name"]); ?>
                                                </a>

                                                <form method="POST" style="display:inline;"
                                                      onsubmit="return confirm('Are you sure you want to delete this file?');">
                                                    <input type="hidden" name="action" value="delete_file">
                                                    <input type="hidden" name="file_id" value="<?php echo htmlspecialchars($file["id"]); ?>">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p>No uploaded files yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <aside class="project-chat-panel">
                        <h3>Chat</h3>

                        <div class="chat-log">
                            <?php
                            $lastMessageDate = "";

                            foreach ($chatMessages as $message):
                                $messageTimestamp = strtotime($message["created_at"]);
                                $messageDateLabel = date("F j, Y", $messageTimestamp);

                                if ($messageDateLabel !== $lastMessageDate):
                                    $lastMessageDate = $messageDateLabel;
                                    ?>
                                    <div class="chat-date-divider">
                                        <span><?php echo htmlspecialchars($messageDateLabel); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="chat-message">
                                    <form method="POST" class="chat-delete-form"
                                          onsubmit="return confirm('Are you sure you want to delete this message?');">
                                        <input type="hidden" name="action" value="delete_chat_message">
                                        <input type="hidden" name="message_id" value="<?php echo htmlspecialchars($message["id"]); ?>">
                                        <input type="hidden" name="tab_name" value="<?php echo htmlspecialchars($currentTab); ?>">
                                        <button type="submit" class="chat-delete-btn">×</button>
                                    </form>

                                    <div class="chat-meta">
                                        <?php echo htmlspecialchars($message["username"]); ?> • <?php echo date("g:i A", $messageTimestamp); ?>
                                    </div>

                                    <div class="chat-bubble">
                                        <?php echo htmlspecialchars($message["message_text"]); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="send_chat_message">
                            <input type="hidden" name="tab_name" id="chat-tab-name" value="<?php echo htmlspecialchars($currentTab); ?>">

                            <div class="chat-input-row">
                                <input type="text" name="message_text" placeholder="Text your group">
                                <button class="primary-btn" type="submit">Send</button>
                            </div>
                        </form>
                    </aside>
                </div>
                </div>
            </div>
        </div>

        <div class="modal" id="editObjectiveModal">
            <div class="modal-content">
                <h3>Edit Objective</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_objective">
                    <input type="hidden" name="objective_id" id="edit-objective-id">
                    <textarea name="objective_text" id="edit-objective-text" style="width:100%; padding:12px; border-radius:10px; border:1px solid #d1d5db;"></textarea>

                    <div class="popup-buttons">
                        <button class="primary-btn" type="submit">Save</button>
                        <button class="cancel-btn" type="button" onclick="closeEditObjectiveModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal" id="deleteObjectiveModal">
            <div class="modal-content">
                <h3>Delete Objective?</h3>
                <p>Are you sure you want to delete this objective?</p>

                <form method="POST">
                    <input type="hidden" name="action" value="delete_objective">
                    <input type="hidden" name="objective_id" id="delete-objective-id">

                    <div class="popup-buttons">
                        <button class="danger-btn" type="submit">Delete</button>
                        <button class="cancel-btn" type="button" onclick="closeDeleteObjectiveModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal" id="markIncompleteModal">
            <div class="modal-content">
                <h3>Mark objective as incomplete?</h3>

                <form method="POST">
                    <input type="hidden" name="action" value="mark_incomplete">
                    <input type="hidden" name="objective_id" id="mark-incomplete-id">

                    <div class="popup-buttons">
                        <button class="primary-btn" type="submit">Yes</button>
                        <button class="cancel-btn" type="button" onclick="closeMarkIncompleteModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal" id="completeProjectModal">
            <div class="modal-content">
                <h3>Mark project as completed?</h3>
                <p>Are you sure you want to mark this project as completed?</p>

                <form method="POST">
                    <input type="hidden" name="action" value="complete_project">

                    <div class="popup-buttons">
                        <button type="submit" class="primary-btn">Yes, Complete</button>
                        <button type="button" class="cancel-btn" onclick="closeCompleteProjectModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
<script>
    function showProjectTab(tabName) {
        window.location.href = 'project_detail.php?id=<?php echo urlencode($project["id"]); ?>&tab=' + tabName;
    }
</script>
<script>
    function toggleAddObjectiveForm() {
        const form = document.getElementById('addObjectiveForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function toggleMarkCompleteBtn() {
        const checked = document.querySelectorAll('input[name="objective_ids[]"]:checked');
        document.getElementById('markCompleteBtn').style.display = checked.length > 0 ? 'inline-block' : 'none';
    }

    function submitCompleteObjectives() {
        document.getElementById('completeObjectivesForm').submit();
    }
</script>
<script>
    function openEditObjectiveModal(id, text) {
        document.getElementById('edit-objective-id').value = id;
        document.getElementById('edit-objective-text').value = text;
        document.getElementById('editObjectiveModal').style.display = 'flex';
    }

    function closeEditObjectiveModal() {
        document.getElementById('editObjectiveModal').style.display = 'none';
    }

    function openDeleteObjectiveModal(id) {
        document.getElementById('delete-objective-id').value = id;
        document.getElementById('deleteObjectiveModal').style.display = 'flex';
    }

    function closeDeleteObjectiveModal() {
        document.getElementById('deleteObjectiveModal').style.display = 'none';
    }

    function openMarkIncompleteModal(id) {
        document.getElementById('mark-incomplete-id').value = id;
        document.getElementById('markIncompleteModal').style.display = 'flex';
    }

    function closeMarkIncompleteModal() {
        document.getElementById('markIncompleteModal').style.display = 'none';
    }

    window.addEventListener('click', function(e) {
        ['editObjectiveModal', 'deleteObjectiveModal', 'markIncompleteModal', 'deleteChatModal'].forEach(function(id) {
            const modal = document.getElementById(id);
            if (modal && e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    window.addEventListener('click', function(e) {
        const completeProjectModal = document.getElementById('completeProjectModal');
        if (e.target === completeProjectModal) {
            closeCompleteProjectModal();
        }
    });
</script>
<script>
    let draggedObjective = null;

    document.querySelectorAll('.objective-item[draggable="true"]').forEach(function(item) {
        item.addEventListener('dragstart', function() {
            draggedObjective = this;
            this.classList.add('dragging');
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            saveObjectiveOrder();
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            const container = this.parentNode;
            if (draggedObjective && draggedObjective !== this) {
                const rect = this.getBoundingClientRect();
                const next = (e.clientY - rect.top) > rect.height / 2;
                container.insertBefore(draggedObjective, next ? this.nextSibling : this);
            }
        });
    });

    function saveObjectiveOrder() {
        const container = document.querySelector('.objective-list');
        const inputWrap = document.getElementById('objective-order-inputs');
        inputWrap.innerHTML = '';

        container.querySelectorAll('.objective-item[draggable="true"]').forEach(function(item) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'objective_order[]';
            input.value = item.dataset.id;
            inputWrap.appendChild(input);
        });

        document.getElementById('reorderObjectivesForm').submit();
    }
</script>
<script>
    function openDeleteChatModal(id) {
        document.getElementById('delete-chat-id').value = id;
        document.getElementById('deleteChatModal').style.display = 'flex';
    }

    function closeDeleteChatModal() {
        document.getElementById('deleteChatModal').style.display = 'none';
    }
</script>
<script>
    function openCompleteProjectModal() {
        document.getElementById('completeProjectModal').style.display = 'flex';
    }

    function closeCompleteProjectModal() {
        document.getElementById('completeProjectModal').style.display = 'none';
    }
</script>
<script>
    let originalScopeValues = {};

    function enableScopeEditing() {
        const textFields = document.querySelectorAll('#scopeForm .scope-editable');
        const selectFields = document.querySelectorAll('#scopeForm .scope-editable-select');

        originalScopeValues = {};

        textFields.forEach(function(field, index) {
            originalScopeValues["text_" + index] = field.value;
            field.removeAttribute('readonly');
        });

        selectFields.forEach(function(field, index) {
            originalScopeValues["select_" + index] = field.value;
            field.removeAttribute('disabled');
        });

        document.getElementById('editScopeBtn').style.display = 'none';
        document.getElementById('saveScopeBtn').style.display = 'inline-block';
        document.getElementById('cancelScopeBtn').style.display = 'inline-block';
    }

    function cancelScopeEditing() {
        const textFields = document.querySelectorAll('#scopeForm .scope-editable');
        const selectFields = document.querySelectorAll('#scopeForm .scope-editable-select');

        textFields.forEach(function(field, index) {
            field.value = originalScopeValues["text_" + index] ?? field.value;
            field.setAttribute('readonly', 'readonly');
        });

        selectFields.forEach(function(field, index) {
            field.value = originalScopeValues["select_" + index] ?? field.value;
            field.setAttribute('disabled', 'disabled');
        });

        document.getElementById('editScopeBtn').style.display = 'inline-block';
        document.getElementById('saveScopeBtn').style.display = 'none';
        document.getElementById('cancelScopeBtn').style.display = 'none';
    }
</script>