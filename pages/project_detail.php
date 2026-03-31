<?php
require_once "../config.php";

$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if ($id === '') {
    die("Project ID is missing.");
}

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM projects
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(array(':id' => $id));
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        die("Project not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

$deliverables = json_decode($project["deliverables"], true);
$tasks = json_decode($project["tasks"], true);
$constraints = json_decode($project["constraints"], true);
$approvals = json_decode($project["approvals"], true);
$assumptions = json_decode($project["assumptions"], true);

$launchDate = isset($constraints["launchDate"]) ? $constraints["launchDate"] : "N/A";
$projectStartDate = isset($constraints["projectStartDate"]) ? $constraints["projectStartDate"] : "N/A";
$projectEndDate = isset($constraints["projectEndDate"]) ? $constraints["projectEndDate"] : "N/A";
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
    <?php include "../components/sidebar.php"; ?>

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

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value"><?php echo htmlspecialchars($project["status"]); ?></div>
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
                        <div class="detail-label">Estimated Hours</div>
                        <div class="detail-value"><?php echo htmlspecialchars($project["estimated_hours"]); ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Project Start</div>
                        <div class="detail-value"><?php echo htmlspecialchars($projectStartDate); ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Project End</div>
                        <div class="detail-value"><?php echo htmlspecialchars($projectEndDate); ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Deliverables</div>
                        <div class="detail-value">
                            <ul>
                                <?php if (is_array($deliverables)): ?>
                                    <?php foreach ($deliverables as $d): ?>
                                        <li><?php echo htmlspecialchars($d["description"]); ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Tasks</div>
                        <div class="detail-value">
                            <ul>
                                <?php if (is_array($tasks)): ?>
                                    <?php foreach ($tasks as $t): ?>
                                        <li><?php echo htmlspecialchars($t["description"]); ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Out of Scope</div>
                        <div class="detail-value"><?php echo htmlspecialchars($project["out_of_scope"]); ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">WBS Attached</div>
                        <div class="detail-value"><?php echo $project["wbs_attached"] ? "Yes" : "No"; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>