<?php
require_once "../config.php"; // change to ../core/config.php if your config is in /core

/* GET MONTH + YEAR */
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date("n");
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date("Y");

if ($month < 1 || $month > 12) {
    $month = (int)date("n");
}

if ($year < 2000 || $year > 2100) {
    $year = (int)date("Y");
}

/* HANDLE FORM ACTIONS */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["action"]) ? $_POST["action"] : "";

    /* ADD EVENT */
    if ($action === "add") {
        $title = isset($_POST["title"]) ? trim($_POST["title"]) : "";
        $description = isset($_POST["description"]) ? trim($_POST["description"]) : "";
        $day = isset($_POST["day"]) ? (int)$_POST["day"] : 0;
        $eventMonth = isset($_POST["month"]) ? (int)$_POST["month"] : 0;
        $eventYear = isset($_POST["year"]) ? (int)$_POST["year"] : 0;
        $eventTime = (isset($_POST["time"]) && $_POST["time"] !== "") ? $_POST["time"] : null;
        $location = isset($_POST["location"]) ? trim($_POST["location"]) : "";
        $color = isset($_POST["color"]) ? $_POST["color"] : "blue";

        if ($location === "") {
            $location = null;
        }

        if ($title !== "" && checkdate($eventMonth, $day, $eventYear)) {
            $eventDate = sprintf('%04d-%02d-%02d', $eventYear, $eventMonth, $day);

            $stmt = $pdo->prepare("
            INSERT INTO calendar (title, description, event_date, event_time, location, color) 
            VALUES (:title, :description, :event_date, :event_time, :location, :color)
        ");

            $stmt->execute(array(
                    ':title' => $title,
                    ':description' => $description,
                    ':event_date' => $eventDate,
                    ':event_time' => $eventTime,
                    ':location' => $location,
                    ':color' => $color
            ));
        }
    }


    /* EDIT EVENT */
    if ($action === "edit") {
        $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
        $title = isset($_POST["title"]) ? trim($_POST["title"]) : "";
        $description = isset($_POST["description"]) ? trim($_POST["description"]) : "";
        $day = isset($_POST["day"]) ? (int)$_POST["day"] : 0;
        $eventMonth = isset($_POST["month"]) ? (int)$_POST["month"] : 0;
        $eventYear = isset($_POST["year"]) ? (int)$_POST["year"] : 0;
        $eventTime = (isset($_POST["time"]) && $_POST["time"] !== "") ? $_POST["time"] : null;
        $location = isset($_POST["location"]) ? trim($_POST["location"]) : "";
        $color = isset($_POST["color"]) ? $_POST["color"] : "blue";

        if ($location === "") {
            $location = null;
        }

        if ($id > 0 && $title !== "" && checkdate($eventMonth, $day, $eventYear)) {
            $eventDate = sprintf('%04d-%02d-%02d', $eventYear, $eventMonth, $day);

            $stmt = $pdo->prepare("
            UPDATE calendar
            SET title = :title,
                description = :description,
                event_date = :event_date,
                event_time = :event_time,
                location = :location,
                color = :color
            WHERE id = :id
        ");

            $stmt->execute(array(
                    ':title' => $title,
                    ':description' => $description,
                    ':event_date' => $eventDate,
                    ':event_time' => $eventTime,
                    ':location' => $location,
                    ':color' => $color,
                    ':id' => $id
            ));
        }
    }

    /* DELETE EVENT */
    if ($action === "delete") {
        $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM calendar WHERE id = :id");
            $stmt->execute(array(':id' => $id));
        }
    }

    header("Location: Calendar.php?month=" . $month . "&year=" . $year);
    exit();
}

/* CALENDAR VALUES */
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$monthName = date("F", $firstDayOfMonth);
$daysInMonth = (int)date("t", $firstDayOfMonth);
$startDay = (int)date("w", $firstDayOfMonth);

/* PREV / NEXT */
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

/* LOAD EVENTS FROM DATABASE */
$eventsByDay = array();

$stmt = $pdo->prepare("
    SELECT id, title, description, event_date, event_time, location, color
    FROM calendar
    WHERE EXTRACT(MONTH FROM event_date) = :month
      AND EXTRACT(YEAR FROM event_date) = :year
    ORDER BY event_date ASC, event_time ASC NULLS LAST, id ASC
");

$stmt->execute(array(
        ':month' => $month,
        ':year' => $year
));

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($events as $event) {
    $day = (int)date("j", strtotime($event["event_date"]));

    $eventsByDay[$day][] = array(
            "id" => $event["id"],
            "title" => $event["title"],
            "description" => $event["description"],
            "day" => $day,
            "month" => $month,
            "year" => $year,
            "date" => $event["event_date"],
            "time" => $event["event_time"],
            "location" => $event["location"],
            "color" => $event["color"]
    );
}

$months = array(
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
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">

    <aside class="sidebar">
        <div class="brand">
            <div class="logo-box">O10</div>
            <div>
                <h2>Oiler 10</h2>
                <p>Customer Management</p>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="../index.php" class="nav-link">
                <span class="icon">🏠</span> Overview
            </a>
            <a href="projects.php" class="nav-link">
                <span class="icon">📁</span> Projects
            </a>
            <a href="Calendar.php" class="nav-link active">
                <span class="icon">📅</span> Calendar
            </a>
            <a href="#" class="nav-link">
                <span class="icon">✅</span> To-Do List
            </a>
            <a href="#" class="nav-link">
                <span class="icon">👥</span> Contacts
            </a>
        </nav>

        <div class="user-profile">
            <div class="avatar">B</div>
            <div class="user-info">
                <span class="name">bodiugiulian</span>
                <span class="email">user@gmail.com</span>
            </div>
            <button class="logout-btn">
                <span class="icon">🚪</span> Logout
            </button>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Calendar</h1>
            <p>Manage deadlines and events.</p>
        </div>

        <div class="calendar-container">
            <div class="calendar-header">
                <div class="header-left">
                    <h2><?php echo htmlspecialchars($monthName . " " . $year); ?></h2>
                </div>

                <div class="header-right">
                    <form method="GET" class="calendar-form">
                        <select name="month">
                            <?php foreach ($months as $num => $label): ?>
                                <option value="<?php echo $num; ?>" <?php if ($num == $month) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($label); ?>
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

                    <button class="add-button" type="button" onclick="openAddModal()">Add Event</button>

                    <span class="nav-buttons">
                        <a href="Calendar.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">‹</a>
                        <a href="Calendar.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">›</a>
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
                        echo "<div class='day-box' onclick='openAddModalForDay(" . $day . "," . $month . "," . $year . ")'>";
                        echo "<div class='day-number'>" . $day . "</div>";

                        if (isset($eventsByDay[$day])) {
                            foreach ($eventsByDay[$day] as $event) {
                                $id = htmlspecialchars($event["id"]);
                                $title = htmlspecialchars($event["title"]);
                                $desc = htmlspecialchars($event["description"]);
                                $time = htmlspecialchars($event["time"]);
                                $location = htmlspecialchars($event["location"]);

                                $color = htmlspecialchars($event["color"] ?? "blue");
                                echo "<div class='event-card $color' onclick='event.stopPropagation();'>";
                                echo "<div class='event-title'>" . $title . "</div>";

                                if ($desc !== "") {
                                    echo "<div class='event-description'>" . $desc . "</div>";
                                }

                                if ($time !== "") {
                                    $formattedTime = date("g:i A", strtotime($time));
                                    echo "<div class='event-description'>🕒 $formattedTime</div>";
                                }

                                if ($location !== "") {
                                    echo "<div class='event-description'>📍 " . $location . "</div>";
                                }

                                echo "<div class='event-actions'>";

                                echo "<button type='button' class='edit-btn' onclick='openEditModal("
                                        . json_encode($event["id"]) . ","
                                        . json_encode($event["title"]) . ","
                                        . json_encode($event["description"]) . ","
                                        . json_encode($event["day"]) . ","
                                        . json_encode($event["month"]) . ","
                                        . json_encode($event["year"]) . ","
                                        . json_encode($event["time"]) . ","
                                        . json_encode($event["location"]) . ","
                                        . json_encode($event["color"] ?? "blue")
                                        . ")'>Edit</button>";

                                echo "<button type='button' class='delete-btn' onclick='openDeleteModal("
                                        . json_encode($event["id"]) . ","
                                        . json_encode($event["title"])
                                        . ")'>Delete</button>";

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
    </main>
</div>

<!-- ADD EVENT MODAL -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Add Event</h3>

        <form method="POST" class="popup-form">
            <input type="hidden" name="action" value="add">

            <input type="text" name="title" id="add-title" placeholder="Event Title" required>
            <textarea name="description" id="add-description" placeholder="Event Description"></textarea>
            <input type="time" name="time" id="add-time">
            <input type="text" name="location" id="add-location" placeholder="Location">

            <select name="color" id="add-color">
                <option value="blue">Blue</option>
                <option value="green">Green</option>
                <option value="red">Red</option>
                <option value="purple">Purple</option>
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
                        <?php echo htmlspecialchars($monthLabel); ?>
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
                <button type="submit" class="primary-btn">Save Event</button>
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
            <input type="time" name="time" id="edit-time">
            <input type="text" name="location" id="edit-location" placeholder="Location">

            <select name="color" id="edit-color">
                <option value="blue">Blue</option>
                <option value="green">Green</option>
                <option value="red">Red</option>
                <option value="purple">Purple</option>
                <option value="yellow">Yellow</option>
            </select>

            <select name="day" id="edit-day" required>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                <?php endfor; ?>
            </select>

            <select name="month" id="edit-month" required>
                <?php foreach ($months as $monthNumber => $monthLabel): ?>
                    <option value="<?php echo $monthNumber; ?>"><?php echo htmlspecialchars($monthLabel); ?></option>
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
        document.getElementById("add-time").value = "";
        document.getElementById("add-location").value = "";
        document.getElementById("add-day").value = "";
        document.getElementById("add-month").value = "<?php echo $month; ?>";
        document.getElementById("add-year").value = "<?php echo $year; ?>";
        document.getElementById("add-color").value = "blue";
        document.getElementById("addModal").style.display = "flex";
    }

    function closeAddModal() {
        document.getElementById("addModal").style.display = "none";
    }

    function openAddModalForDay(day, month, year) {
        document.getElementById("add-title").value = "";
        document.getElementById("add-description").value = "";
        document.getElementById("add-time").value = "";
        document.getElementById("add-location").value = "";
        document.getElementById("add-day").value = day;
        document.getElementById("add-month").value = month;
        document.getElementById("add-year").value = year;
        document.getElementById("addModal").style.display = "flex";
    }

    function openEditModal(id, title, description, day, month, year, time, location, color) {
        document.getElementById("edit-id").value = id;
        document.getElementById("edit-title").value = title;
        document.getElementById("edit-description").value = description;
        document.getElementById("edit-time").value = time ? time.substring(0, 5) : "";
        document.getElementById("edit-location").value = location || "";
        document.getElementById("edit-day").value = day;
        document.getElementById("edit-month").value = month;
        document.getElementById("edit-year").value = year;
        document.getElementById("edit-color").value = color || "blue";
        document.getElementById("editModal").style.display = "flex";
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    function openDeleteModal(id, title) {
        document.getElementById("delete-event-id").value = id;
        document.getElementById("delete-modal-text").innerHTML =
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
        var addModal = document.getElementById("addModal");
        var editModal = document.getElementById("editModal");
        var deleteModal = document.getElementById("deleteModal");

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