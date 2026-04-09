<?php
$currentPage = $page    ?? 'overview';
$isAdmin     = $isAdmin ?? false;
$navPrefix   = $navPrefix ?? '';
?>

<aside class="sidebar">
    <div class="brand">
        <img class="brand-logo sidebar-brand-logo" src="https://static.wixstatic.com/media/fc911f_11934eb0cff34f33943001a4acc3fcc9~mv2.png/v1/fill/w_392,h_128,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/2021_O10LogoFile%20copy.png" alt="Oiler 10">
        <div>
            <h2>Oiler 10</h2>
            <p>Customer Management</p>
        </div>
    </div>

    <nav class="nav-menu">
        <a href="<?php echo $navPrefix; ?>index.php?page=overview"
           class="nav-link <?php echo $currentPage === 'overview' ? 'active' : ''; ?>">
            <span class="icon">🏠</span> Overview
        </a>

        <a href="<?php echo $navPrefix; ?>index.php?page=projects"
           class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>">
            <span class="icon">📁</span> Projects
        </a>

        <a href="<?php echo $navPrefix; ?>index.php?page=calendar"
           class="nav-link <?php echo $currentPage === 'calendar' ? 'active' : ''; ?>">
            <span class="icon">📅</span> Calendar
        </a>

        <a href="<?php echo $navPrefix; ?>index.php?page=contacts"
           class="nav-link <?php echo $currentPage === 'contacts' ? 'active' : ''; ?>">
            <span class="icon">👥</span> Contacts
        </a>
    </nav>

    <div class="user-profile">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
        <div class="user-info">
            <span class="name">
                <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                <?php if ($isAdmin): ?>
                    <span class="sidebar-admin-badge">Admin</span>
                <?php endif; ?>
            </span>
            <span class="email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
        </div>
        <a href="<?php echo $navPrefix; ?>logout.php" class="logout-btn">
            <span class="icon">🚪</span> Logout
        </a>
    </div>
</aside>