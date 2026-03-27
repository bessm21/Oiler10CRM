<?php
// Path is relative to index.php when included
require_once 'config.php';

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

    // Redirect fix to keep the calendar view active after a POST
    header("Location: index.php?page=calendar&month=" . $month . "&year=" . $year);
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
        1 => "January", 2 => "February", 3 => "March", 4 => "April",
        5 => "May", 6 => "June", 7 => "July", 8 => "August",
        9 => "September", 10 => "October", 11 => "November", 12 => "December"
);
?>

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
            <form method="GET" action="index.php" class="calendar-form">
                <input type="hidden" name="page" value="calendar">
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
                <a href="index.php?page=calendar&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">‹</a>
                <a href="index.php?page=calendar&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">›</a>
            </span>
        </div>
    </div>

    <div class="calendar-grid">
        <div class="day-names">
            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
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
                        $color = htmlspecialchars($event["color"] ?? "blue");
                        echo "<div class='event-card $color' onclick='event.stopPropagation();'>";
                        echo "<div class='event-title'>" . htmlspecialchars($event["title"]) . "</div>";

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

                        echo "<button type='button' class='delete-btn' onclick='openCalDeleteModal("
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
                <?php for ($d = 1; $d <= 31; $d++): ?>
                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                <?php endfor; ?>
            </select>
            <select name="month" id="add-month" required>
                <?php foreach ($months as $mNum => $mLab): ?>
                    <option value="<?php echo $mNum; ?>" <?php if ($mNum == $month) echo "selected"; ?>><?php echo htmlspecialchars($mLab); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="year" id="add-year" required>
                <?php for ($y = 2024; $y <= 2035; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php if ($y == $year) echo "selected"; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <div class="popup-buttons">
                <button type="button" class="primary-btn" onclick="submitAddForm()">Save Event</button>
                <button type="button" class="cancel-btn" onclick="closeAddModal()">Close</button>
            </div>
        </form>
    </div>
</div>

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
                <?php foreach ($months as $mNum => $mLab): ?>
                    <option value="<?php echo $mNum; ?>"><?php echo htmlspecialchars($mLab); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="year" id="edit-year" required>
                <?php for ($y = 2024; $y <= 2035; $y++): ?>
                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <div class="popup-buttons">
                <button type="button" class="primary-btn" onclick="submitEditForm()">Update Event</button>
                <button type="button" class="cancel-btn" onclick="closeEditModal()">Close</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="calDeleteModal">
    <div class="modal-content">
        <h3>Delete Event</h3>
        <p id="cal-delete-modal-text">Are you sure you want to delete this event?</p>
        <div class="popup-buttons">
            <button type="button" class="cancel-btn" onclick="closeCalDeleteModal()">Close</button>
            <button type="button" class="primary-btn" onclick="submitDelete()">Delete</button>
        </div>
    </div>
</div>

<form method="POST" id="deleteEventForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete-event-id">
</form>

<script>
    var CAL_URL = "index.php?page=calendar&month=<?php echo $month; ?>&year=<?php echo $year; ?>";

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

    function closeAddModal() { document.getElementById("addModal").style.display = "none"; }

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

    function submitAddForm() {
        var title = document.getElementById("add-title").value.trim();
        if (!title) { alert("Please enter an event title."); return; }

        var day   = document.getElementById("add-day").value;
        var month = document.getElementById("add-month").value;
        var year  = document.getElementById("add-year").value;
        var color = document.getElementById("add-color").value || "blue";

        // Optimistic: insert card into the right day box immediately
        var dayBox = findDayBox(day, month, year);
        if (dayBox) { dayBox.appendChild(buildEventCard(title, color)); }

        closeAddModal();

        var fd = new FormData(document.querySelector("#addModal form"));
        fetch(CAL_URL, { method: "POST", body: fd }).then(function() { location.reload(); });
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

    function closeEditModal() { document.getElementById("editModal").style.display = "none"; }

    function submitEditForm() {
        var title = document.getElementById("edit-title").value.trim();
        if (!title) { alert("Please enter an event title."); return; }

        var id    = document.getElementById("edit-id").value;
        var color = document.getElementById("edit-color").value || "blue";

        // Optimistic: update title on the existing card immediately
        document.querySelectorAll(".event-card").forEach(function(card) {
            var btn = card.querySelector(".delete-btn");
            if (btn && btn.getAttribute("onclick").includes("openCalDeleteModal(" + id + ",")) {
                var titleEl = card.querySelector(".event-title");
                if (titleEl) titleEl.textContent = title;
                card.className = "event-card " + color;
            }
        });

        closeEditModal();

        var fd = new FormData(document.querySelector("#editModal form"));
        fetch(CAL_URL, { method: "POST", body: fd }).then(function() { location.reload(); });
    }

    function openCalDeleteModal(id, title) {
        document.getElementById("delete-event-id").value = id;
        document.getElementById("cal-delete-modal-text").innerHTML = 'Are you sure you want to delete "' + title + '"?';
        document.getElementById("calDeleteModal").style.display = "flex";
    }

    function closeCalDeleteModal() { document.getElementById("calDeleteModal").style.display = "none"; }

    function submitDelete() {
        var id = document.getElementById("delete-event-id").value;

        closeCalDeleteModal();
        document.querySelectorAll(".event-card").forEach(function(card) {
            var btn = card.querySelector(".delete-btn");
            if (btn && btn.getAttribute("onclick").includes("openCalDeleteModal(" + id + ",")) {
                card.remove();
            }
        });

        var fd = new FormData();
        fd.append("action", "delete");
        fd.append("id", id);
        fetch(CAL_URL, { method: "POST", body: fd });
    }

    // --- helpers ---

    function findDayBox(day, month, year) {
        var boxes = document.querySelectorAll(".day-box");
        for (var i = 0; i < boxes.length; i++) {
            var oc = boxes[i].getAttribute("onclick") || "";
            if (oc.includes("openAddModalForDay(" + day + "," + month + "," + year + ")")) {
                return boxes[i];
            }
        }
        return null;
    }

    function buildEventCard(title, color) {
        var card = document.createElement("div");
        card.className = "event-card " + color;
        card.setAttribute("onclick", "event.stopPropagation();");
        card.innerHTML = '<div class="event-title">' + title + '</div>';
        return card;
    }

    window.onclick = function(event) {
        if (event.target === document.getElementById("addModal")) closeAddModal();
        if (event.target === document.getElementById("editModal")) closeEditModal();
        if (event.target === document.getElementById("calDeleteModal")) closeCalDeleteModal();
    };
</script>