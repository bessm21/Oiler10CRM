<?php $currentPage = $page ?? 'overview'; ?>

<aside class="sidebar">
    <div class="brand">
        <div class="logo-box">O10</div>
        <div>
            <h2>Oiler 10</h2>
            <p>Customer Management</p>
        </div>
    </div>

    <nav class="nav-menu">
        <a href="index.php?page=overview" class="nav-link <?php echo $currentPage === 'overview' ? 'active' : ''; ?>">
            <span class="icon">🏠</span> Overview
        </a>

        <a href="index.php?page=overview" class="nav-link">
            <span class="icon">📁</span> Projects
        </a>

        <a href="index.php?page=calendar" class="nav-link <?php echo $currentPage === 'calendar' ? 'active' : ''; ?>">
            <span class="icon">📅</span> Calendar
        </a>

        <a href="index.php?page=overview" class="nav-link">
            <span class="icon">✅</span> To-Do List
        </a>

        <a href="index.php?page=contacts" class="nav-link <?php echo $currentPage === 'contacts' ? 'active' : ''; ?>">
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