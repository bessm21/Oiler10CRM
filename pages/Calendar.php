<?php
require_once "../config.php";

// -----------------------------------
// 1. GET SELECTED MONTH AND YEAR
// -----------------------------------
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");
$year = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");

if ($month < 1 || $month > 12) {
    $month = date("n");
}

if ($year < 2000 || $year > 2100) {
    $year = date("Y");
}


// -----------------------------------
// 3. HANDLE FORM ACTIONS
// -----------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["action"]) ? $_POST["action"] : "";

    // ADD EVENT
    if ($action === "add") {
    $title = isset($_POST["title"]) ? trim($_POST["title"]) : "";
    $description = isset($_POST["description"]) ? trim($_POST["description"]) : "";
    $day = isset($_POST["day"]) ? (int)$_POST["day"] : 0;
    $eventMonth = isset($_POST["month"]) ? (int)$_POST["month"] : 0;
    $eventYear = isset($_POST["year"]) ? (int)$_POST["year"] : 0;
    $color = isset($_POST["color"]) ? $_POST["color"] : "blue";

    if ($title !== "" && checkdate($eventMonth, $day, $eventYear)) {
        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, event_day, event_month, event_year, color)
            VALUES (:title, :description, :event_day, :event_month, :event_year, :color)
        ");

        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':event_day' => $day,
            ':event_month' => $eventMonth,
            ':event_year' => $eventYear,
            ':color' => $color
        ]);
    }
}

    // EDIT EVENT
    if ($action === "edit") {
        $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
        $title = isset($_POST["title"]) ? trim($_POST["title"]) : "";
        $description = isset($_POST["description"]) ? trim($_POST["description"]) : "";
        $day = isset($_POST["day"]) ? (int)$_POST["day"] : 0;
        $eventMonth = isset($_POST["month"]) ? (int)$_POST["month"] : 0;
        $eventYear = isset($_POST["year"]) ? (int)$_POST["year"] : 0;
        $color = isset($_POST["color"]) ? $_POST["color"] : "blue";

        if ($id > 0 && $title !== "" && checkdate($eventMonth, $day, $eventYear)) {
            $stmt = $pdo->prepare("
            UPDATE events
            SET title = :title,
                description = :description,
                event_day = :event_day,
                event_month = :event_month,
                event_year = :event_year,
                color = :color
            WHERE id = :id
        ");

            $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':event_day' => $day,
                    ':event_month' => $eventMonth,
                    ':event_year' => $eventYear,
                    ':color' => $color,
                    ':id' => $id
            ]);
        }
    }

    // DELETE EVENT
    iif ($action === "delete") {
    $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0};

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}

    header("Location: Calendar.php?month=$month&year=$year");
    exit();
}

// -----------------------------------
// 4. CALENDAR VALUES
// -----------------------------------
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$monthName = date("F", $firstDayOfMonth);
$daysInMonth = date("t", $firstDayOfMonth);
$startDay = date("w", $firstDayOfMonth);

// -----------------------------------
// 5. PREVIOUS AND NEXT MONTH
// -----------------------------------
$prevMonth = $month - 1;
$prevYear = $year;

if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;

if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}


// -----------------------------------
// 7. USER EVENTS
// -----------------------------------
$eventsByDay = [];

$stmt = $pdo->prepare("
    SELECT id, title, description, event_day, event_month, event_year, color
    FROM events
    WHERE event_month = :event_month AND event_year = :event_year
    ORDER BY event_day ASC
");

$stmt->execute([
    ':event_month' => $month,
    ':event_year' => $year
]);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($events as $event) {
    $eventsByDay[$event["event_day"]][] = [
        "id" => $event["id"],
        "title" => $event["title"],
        "description" => $event["description"],
        "day" => $event["event_day"],
        "month" => $event["event_month"],
        "year" => $event["event_year"],
        "color" => $event["color"]
    ];
}

// -----------------------------------
// 8. MONTH NAMES
// -----------------------------------
$months = [
        1 => "January",
        2 => "February",
        3 => "March",
        4 => "April",
        5 => "May",
        6 => "June",
        7 => "July",
        8 => "August",
        9 => "September",
        10 => "October",
        11 => "November",
        12 => "December"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 20px;
            color: #1f2937;
        }

        .calendar-container {
            max-width: 1080px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
            gap: 16px;
            flex-wrap: wrap;
        }

        .header-left h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .calendar-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .calendar-form select,
        .calendar-form button {
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
        }

        .calendar-form button,
        .add-button,
        .popup-form button[type="submit"] {
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
        }

        .calendar-form button:hover,
        .add-button:hover,
        .popup-form button[type="submit"]:hover {
            background: #1d4ed8;
        }

        .add-button {
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .nav-buttons {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-buttons a {
            text-decoration: none;
            font-size: 22px;
            color: #374151;
            font-weight: bold;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .nav-buttons a:hover {
            background: #f3f4f6;
        }

        .calendar-grid {
            padding: 18px;
        }

        .day-names,
        .days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .day-names div {
            text-align: center;
            font-weight: 700;
            padding: 8px 0;
            color: #6b7280;
            font-size: 14px;
        }

        .day-box {
            min-height: 120px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 8px;
            background: #ffffff;
            overflow: hidden;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            cursor: pointer;
        }

        .day-box:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
        }

        .empty-box {
            background: #f9fafb;
            cursor: default;
        }

        .day-number {
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 700;
            color: #111827;
        }

        .event-card {
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 11px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.35);
        }

        .event-title {
            font-weight: 700;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 70px;
        }

        .event-description {
            font-size: 10px;
            color: #374151;
            margin-bottom: 4px;
            line-height: 1.3;
            max-height: 28px;
            overflow: hidden;
        }

        .event-actions {
            position: absolute;
            top: 6px;
            right: 6px;
            display: flex;
            gap: 4px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
        }

        .event-card:hover .event-actions {
            opacity: 1;
            visibility: visible;
        }

        .delete-form {
            margin: 0;
        }

        .edit-btn,
        .delete-btn {
            border: none;
            padding: 3px 7px;
            border-radius: 6px;
            font-size: 10px;
            cursor: pointer;
        }

        .edit-btn {
            background: rgba(255, 255, 255, 0.8);
            color: #111827;
        }

        .delete-btn {
            background: #ffffff;
            color: #b91c1c;
        }

        .blue {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .purple {
            background-color: #f3e8ff;
            color: #9333ea;
        }

        .red {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .green {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .yellow {
            background-color: #fef3c7;
            color: #ca8a04;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.45);
            justify-content: center;
            align-items: center;
            padding: 20px;
            z-index: 999;
        }

        .modal-content {
            background: white;
            width: 100%;
            max-width: 460px;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18);
        }

        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 22px;
            color: #111827;
        }

        .popup-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .popup-form input,
        .popup-form textarea,
        .popup-form select {
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
        }

        .popup-form textarea {
            resize: vertical;
            min-height: 90px;
        }

        .popup-buttons {
            display: flex;
            gap: 10px;
        }

        .cancel-btn {
            background: #e5e7eb;
            color: #111827;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
        }

        .danger-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
        }

        .danger-btn:hover {
            background: #b91c1c;
        }


        @media (max-width: 1100px) {
            .calendar-container {
                max-width: 100%;
            }
        }

        @media (max-width: 900px) {
            body {
                padding: 12px;
            }

            .calendar-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .calendar-grid {
                overflow-x: auto;
            }

            .day-names,
            .days {
                min-width: 860px;
            }
        }
    </style>
</head>
<body>

<div class="calendar-container">
    <div class="calendar-header">
        <div class="header-left">
            <h2><?php echo $monthName . " " . $year; ?></h2>
        </div>

        <div class="header-right">
            <form method="GET" class="calendar-form">
                <select name="month">
                    <?php foreach ($months as $monthNumber => $monthLabel): ?>
                        <option value="<?php echo $monthNumber; ?>" <?php if ($monthNumber == $month) echo "selected"; ?>>
                            <?php echo $monthLabel; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="year">
                    <?php for ($y = 2024; $y <= 2035; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php if ($y == $year) echo "selected"; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <button type="submit">Go</button>
            </form>

            <button class="add-button" onclick="openAddModal()">Add Event</button>

            <span class="nav-buttons">
                <a href="Calendar.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">&lsaquo;</a>
                <a href="Calendar.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">&rsaquo;</a>
            </span>
        </div>
    </div>

    <div class="calendar-grid">
        <div class="day-names">
            <div>Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
        </div>

        <div class="days">
            <?php
            for ($i = 0; $i < $startDay; $i++) {
                echo "<div class='day-box empty-box'></div>";
            }

            for ($day = 1; $day <= $daysInMonth; $day++) {
                echo "<div class='day-box' onclick='openAddModalForDay($day, $month, $year)'>";
                echo "<div class='day-number'>$day</div>";

                if (isset($eventsByDay[$day])) {
                    foreach ($eventsByDay[$day] as $event) {
                        $id = htmlspecialchars($event["id"]);
                        $title = htmlspecialchars($event["title"]);
                        $description = htmlspecialchars($event["description"]);
                        $color = htmlspecialchars($event["color"]);

                        echo "<div class='event-card $color' onclick='event.stopPropagation();'>";
                        echo "<div class='event-title'>$title</div>";

                        if ($description !== "") {
                            echo "<div class='event-description'>$description</div>";
                        }

                        echo "<div class='event-actions'>";

                        echo "<button 
        type='button' 
        class='edit-btn'
        onclick='openEditModal(" .
                                json_encode($event["id"]) . "," .
                                json_encode($event["title"]) . "," .
                                json_encode($event["description"]) . "," .
                                json_encode($event["day"]) . "," .
                                json_encode($event["month"]) . "," .
                                json_encode($event["year"]) . "," .
                                json_encode($event["color"]) .
                                ")'>
        Edit
      </button>";

                        echo "<button 
        type='button' 
        class='delete-btn'
        onclick='openDeleteModal(" . json_encode($event["id"]) . ", " . json_encode($event["title"]) . ")'>
        Delete
      </button>";

                        echo "</div>";

                        echo "</div>";
                    }
                }

                echo "</div>";
            }

            $totalBoxes = $startDay + $daysInMonth;
            $remainingBoxes = 7 - ($totalBoxes % 7);

            if ($remainingBoxes < 7) {
                for ($i = 0; $i < $remainingBoxes; $i++) {
                    echo "<div class='day-box empty-box'></div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- ADD EVENT MODAL -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Add Event</h3>

        <form method="POST" class="popup-form">
            <input type="hidden" name="action" value="add">

            <input type="text" name="title" id="add-title" placeholder="Event Title" required>

            <textarea name="description" id="add-description" placeholder="Event Description"></textarea>


            <select name="color" id="edit-color">
                <option value="blue">Blue</option>
                <option value="purple">Purple</option>
                <option value="red">Red</option>
                <option value="green">Green</option>
                <option value="yellow">Yellow</option>
            </select>

            <select name="day" id="add-day" required>
                <option value="">Select Day</option>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                <?php endfor; ?>
            </select>

            <select name="month" id="add-month" required>
                <?php foreach ($months as $monthNumber => $monthLabel): ?>
                    <option value="<?php echo $monthNumber; ?>" <?php if ($monthNumber == $month) echo "selected"; ?>>
                        <?php echo $monthLabel; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="year" id="add-year" required>
                <?php for ($y = 2024; $y <= 2035; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php if ($y == $year) echo "selected"; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>

            <div class="popup-buttons">
                <button type="submit">Save Event</button>
                <button type="button" class="cancel-btn" onclick="closeAddModal()">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT EVENT MODAL -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit Event</h3>

        <form method="POST" class="popup-form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">

            <input type="text" name="title" id="edit-title" required>

            <textarea name="description" id="edit-description"></textarea>

            <select name="day" id="edit-day" required>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                <?php endfor; ?>
            </select>

            <select name="month" id="edit-month" required>
                <?php foreach ($months as $monthNumber => $monthLabel): ?>
                    <option value="<?php echo $monthNumber; ?>">
                        <?php echo $monthLabel; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="year" id="edit-year" required>
                <?php for ($y = 2024; $y <= 2035; $y++): ?>
                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>

            <div class="popup-buttons">
                <button type="submit">Update Event</button>
                <button type="button" class="cancel-btn" onclick="closeEditModal()">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE EVENT MODAL -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <h3>Delete Event</h3>
        <p id="delete-modal-text">Are you sure you want to delete this event?</p>

        <div class="popup-buttons">
            <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Close</button>
            <button type="button" class="danger-btn" onclick="submitDelete()">Delete</button>
        </div>
    </div>
</div>

<!-- HIDDEN DELETE FORM -->
<form method="POST" id="deleteEventForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete-event-id">
</form>

<script>
    function openAddModal() {
        document.getElementById("add-title").value = "";
        document.getElementById("add-description").value = "";
        document.getElementById("add-day").value = "";
        document.getElementById("add-month").value = "<?php echo $month; ?>";
        document.getElementById("add-year").value = "<?php echo $year; ?>";
        document.getElementById("addModal").style.display = "flex";
    }

    function openAddModalForDay(day, month, year) {
        document.getElementById("add-day").value = day;
        document.getElementById("add-month").value = month;
        document.getElementById("add-year").value = year;
        document.getElementById("add-title").value = "";
        document.getElementById("add-description").value = "";
        document.getElementById("addModal").style.display = "flex";
    }

    function closeAddModal() {
        document.getElementById("addModal").style.display = "none";
    }

    function openEditModal(id, title, description, day, month, year, color) {
        document.getElementById("edit-id").value = id;
        document.getElementById("edit-title").value = title;
        document.getElementById("edit-description").value = description;
        document.getElementById("edit-day").value = day;
        document.getElementById("edit-month").value = month;
        document.getElementById("edit-year").value = year;
        document.getElementById("edit-color").value = color;
        document.getElementById("editModal").style.display = "flex";
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    function openDeleteModal(id, title) {
        document.getElementById("delete-event-id").value = id;
        document.getElementById("delete-modal-text").textContent =
            'Are you sure you want to delete "' + title + '"?';
        document.getElementById("deleteModal").style.display = "flex";
    }

    function closeDeleteModal() {
        document.getElementById("deleteModal").style.display = "none";
    }

    function submitDelete() {
        document.getElementById("deleteEventForm").submit();
    }

    window.onclick = function(event) {
        const addModal = document.getElementById("addModal");
        const editModal = document.getElementById("editModal");
        const deleteModal = document.getElementById("deleteModal");

        if (event.target === addModal) {
            closeAddModal();
        }

        if (event.target === editModal) {
            closeEditModal();
        }

        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    };
</script>

</body>
</html>