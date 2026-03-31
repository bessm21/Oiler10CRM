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
        <a href="index.php?page=overview"
           class="nav-link <?php echo $currentPage === 'overview' ? 'active' : ''; ?>">
            <span class="icon">🏠</span> Overview
        </a>

        <a href="javascript:void(0);"
           class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>"
           onclick="switchPage('projects-view')">
            <span class="icon">📁</span> Projects
        </a>

        <a href="javascript:void(0);"
           class="nav-link <?php echo $currentPage === 'calendar' ? 'active' : ''; ?>"
           onclick="switchPage('calendar-view')">
            <span class="icon">📅</span> Calendar
        </a>

        <a href="javascript:void(0);"
           class="nav-link <?php echo $currentPage === 'contacts' ? 'active' : ''; ?>"
           onclick="switchPage('contacts-view')">
            <span class="icon">👥</span> Contacts
        </a>
    </nav>

    <div class="user-profile">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
        <div class="user-info">
            <span class="name"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
            <span class="email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
        </div>
        <a href="logout.php" class="logout-btn">
            <span class="icon">🚪</span> Logout
        </a>
    </div>
</aside>