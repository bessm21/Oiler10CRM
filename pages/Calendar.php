<?php
require_once "../config.php";

/* GET MONTH + YEAR */
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");
$year = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");

if ($month < 1 || $month > 12) $month = date("n");
if ($year < 2000 || $year > 2100) $year = date("Y");


/* HANDLE FORM ACTIONS */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    /* ADD EVENT */
    if ($action === "add") {

        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $day = (int)($_POST["day"] ?? 0);
        $eventMonth = (int)($_POST["month"] ?? 0);
        $eventYear = (int)($_POST["year"] ?? 0);
        $color = $_POST["color"] ?? "blue";

        if ($title !== "" && checkdate($eventMonth,$day,$eventYear)) {

            $stmt = $pdo->prepare("
INSERT INTO events
(title,description,event_day,event_month,event_year,color)
VALUES (:title,:description,:day,:month,:year,:color)
");

            $stmt->execute([
                    ":title"=>$title,
                    ":description"=>$description,
                    ":day"=>$day,
                    ":month"=>$eventMonth,
                    ":year"=>$eventYear,
                    ":color"=>$color
            ]);
        }
    }


    /* EDIT EVENT */
    if ($action === "edit") {

        $id = (int)($_POST["id"] ?? 0);
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $day = (int)($_POST["day"] ?? 0);
        $eventMonth = (int)($_POST["month"] ?? 0);
        $eventYear = (int)($_POST["year"] ?? 0);
        $color = $_POST["color"] ?? "blue";

        if ($id > 0 && $title !== "" && checkdate($eventMonth,$day,$eventYear)) {

            $stmt = $pdo->prepare("
UPDATE events
SET title=:title,
description=:description,
event_day=:day,
event_month=:month,
event_year=:year,
color=:color
WHERE id=:id
");

            $stmt->execute([
                    ":title"=>$title,
                    ":description"=>$description,
                    ":day"=>$day,
                    ":month"=>$eventMonth,
                    ":year"=>$eventYear,
                    ":color"=>$color,
                    ":id"=>$id
            ]);
        }
    }


    /* DELETE EVENT */
    if ($action === "delete") {

        $id = (int)($_POST["id"] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id=:id");
            $stmt->execute([":id"=>$id]);
        }
    }

    header("Location: Calendar.php?month=$month&year=$year");
    exit();
}


/* CALENDAR VALUES */

$firstDayOfMonth = mktime(0,0,0,$month,1,$year);
$monthName = date("F",$firstDayOfMonth);
$daysInMonth = date("t",$firstDayOfMonth);
$startDay = date("w",$firstDayOfMonth);


/* PREV / NEXT */

$prevMonth = $month-1;
$prevYear = $year;

if($prevMonth<1){
    $prevMonth=12;
    $prevYear--;
}

$nextMonth=$month+1;
$nextYear=$year;

if($nextMonth>12){
    $nextMonth=1;
    $nextYear++;
}


/* LOAD EVENTS FROM DATABASE */

$eventsByDay=[];

$stmt=$pdo->prepare("
SELECT id,title,description,event_day,event_month,event_year,color
FROM events
WHERE event_month=:month AND event_year=:year
ORDER BY event_day ASC
");

$stmt->execute([
        ":month"=>$month,
        ":year"=>$year
]);

$events=$stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($events as $event){

    $eventsByDay[$event["event_day"]][]=[
            "id"=>$event["id"],
            "title"=>$event["title"],
            "description"=>$event["description"],
            "day"=>$event["event_day"],
            "month"=>$event["event_month"],
            "year"=>$event["event_year"],
            "color"=>$event["color"]
    ];
}


$months=[
        1=>"January",2=>"February",3=>"March",4=>"April",
        5=>"May",6=>"June",7=>"July",8=>"August",
        9=>"September",10=>"October",11=>"November",12=>"December"
];
?>

<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>Calendar</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="app-container">

    <!-- SIDEBAR -->
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
                🏠 Overview
            </a>

            <a href="projects.php" class="nav-link">
                📁 Projects
            </a>

            <a href="Calendar.php" class="nav-link active">
                📅 Calendar
            </a>

            <a href="#" class="nav-link">
                ✅ To-Do List
            </a>

            <a href="#" class="nav-link">
                👥 Contacts
            </a>

        </nav>

        <div class="user-profile">

            <div class="avatar">B</div>

            <div class="user-info">
                <span class="name">bodiugiulian</span>
                <span class="email">user@gmail.com</span>
            </div>

            <button class="logout-btn">
                🚪 Logout
            </button>

        </div>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">

        <div class="page-header">
            <h1>Calendar</h1>
            <p>Manage deadlines and events.</p>
        </div>


        <div class="calendar-container">

            <div class="calendar-header">

                <div class="header-left">
                    <h2><?php echo $monthName." ".$year; ?></h2>
                </div>

                <div class="header-right">

                    <form method="GET" class="calendar-form">

                        <select name="month">
                            <?php foreach($months as $num=>$label): ?>
                                <option value="<?php echo $num ?>" <?php if($num==$month) echo "selected" ?>>
                                    <?php echo $label ?>
                                </option>
                            <?php endforeach ?>
                        </select>

                        <select name="year">
                            <?php for($y=2024;$y<=2035;$y++): ?>
                                <option value="<?php echo $y ?>" <?php if($y==$year) echo "selected" ?>>
                                    <?php echo $y ?>
                                </option>
                            <?php endfor ?>
                        </select>

                        <button type="submit">Go</button>

                    </form>

                    <button class="add-button" onclick="openAddModal()">Add Event</button>

                    <span class="nav-buttons">

<a href="Calendar.php?month=<?php echo $prevMonth ?>&year=<?php echo $prevYear ?>">
‹
</a>

<a href="Calendar.php?month=<?php echo $nextMonth ?>&year=<?php echo $nextYear ?>">
›
</a>

</span>

                </div>

            </div>


            <div class="calendar-grid">

                <div class="day-names">
                    <div>Sun</div><div>Mon</div><div>Tue</div>
                    <div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                </div>


                <div class="days">

                    <?php

                    for($i=0;$i<$startDay;$i++){
                        echo "<div class='day-box empty-box'></div>";
                    }

                    for($day=1;$day<=$daysInMonth;$day++){

                        echo "<div class='day-box' onclick='openAddModalForDay($day,$month,$year)'>";

                        echo "<div class='day-number'>$day</div>";

                        if(isset($eventsByDay[$day])){

                            foreach($eventsByDay[$day] as $event){

                                $id=htmlspecialchars($event["id"]);
                                $title=htmlspecialchars($event["title"]);
                                $desc=htmlspecialchars($event["description"]);
                                $color=htmlspecialchars($event["color"]);

                                echo "<div class='event-card $color' onclick='event.stopPropagation();'>";

                                echo "<div class='event-title'>$title</div>";

                                if($desc!==""){
                                    echo "<div class='event-description'>$desc</div>";
                                }

                                echo "<div class='event-actions'>";

                                echo "<button type='button' class='edit-btn'
onclick='openEditModal(".json_encode($event["id"]).",".json_encode($event["title"]).",".json_encode($event["description"]).",".json_encode($event["day"]).",".json_encode($event["month"]).",".json_encode($event["year"]).",".json_encode($event["color"]).")'>
Edit
</button>";

                                echo "<button type='button' class='delete-btn'
onclick='openDeleteModal(".json_encode($event["id"]).",".json_encode($event["title"]).")'>
Delete
</button>";

                                echo "</div>";

                                echo "</div>";
                            }
                        }

                        echo "</div>";
                    }

                    ?>

                </div>

            </div>

        </div>

    </main>

</div>

<script>

    function openAddModal(){
        document.getElementById("addModal").style.display="flex";
    }

    function closeAddModal(){
        document.getElementById("addModal").style.display="none";
    }

    function openAddModalForDay(day,month,year){

        document.getElementById("add-day").value=day;
        document.getElementById("add-month").value=month;
        document.getElementById("add-year").value=year;

        openAddModal();
    }

    function openEditModal(id,title,description,day,month,year,color){

        document.getElementById("edit-id").value=id;
        document.getElementById("edit-title").value=title;
        document.getElementById("edit-description").value=description;
        document.getElementById("edit-day").value=day;
        document.getElementById("edit-month").value=month;
        document.getElementById("edit-year").value=year;
        document.getElementById("edit-color").value=color;

        document.getElementById("editModal").style.display="flex";
    }

    function closeEditModal(){
        document.getElementById("editModal").style.display="none";
    }

    function openDeleteModal(id,title){

        document.getElementById("delete-event-id").value=id;

        document.getElementById("delete-modal-text").innerText=
            'Are you sure you want to delete "'+title+'"?';

        document.getElementById("deleteModal").style.display="flex";
    }

    function closeDeleteModal(){
        document.getElementById("deleteModal").style.display="none";
    }

    function submitDelete(){
        document.getElementById("deleteEventForm").submit();
    }

</script>

</body>
</html>
