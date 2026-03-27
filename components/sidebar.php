<?php
// Ensure we use the $page variable defined in index.php
$currentPage = $page ?? 'overview';
?>

<aside class="sidebar">
    <div class="brand">
        <div class="logo-box">O10</div>
        <div>
            <h2>Oiler 10</h2>
            <p>Customer Management</p>
        </div>
    </div>

    <nav class="nav-menu">
        <a href="javascript:void(0);"
           class="nav-link <?php echo $currentPage === 'overview' ? 'active' : ''; ?>"
           onclick="switchPage('dashboard-view')">
            <span class="icon">🏠</span> Overview
        </a>

        <a href="javascript:void(0);"
           class="nav-link"
           onclick="switchPage('dashboard-view')">
            <span class="icon">📁</span> Projects
        </a>

        <a href="javascript:void(0);"
           class="nav-link <?php echo $currentPage === 'calendar' ? 'active' : ''; ?>"
           onclick="switchPage('calendar-view')">
            <span class="icon">📅</span> Calendar
        </a>

        <a href="javascript:void(0);"
           class="nav-link"
           onclick="switchPage('dashboard-view')">
            <span class="icon">✅</span> To-Do List
        </a>

        <a href="javascript:void(0);"
           class="nav-link <?php echo $currentPage === 'contacts' ? 'active' : ''; ?>"
           onclick="switchPage('contacts-view')">
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