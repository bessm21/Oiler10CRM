<main class="main-content">
    <header class="page-header">
        <h1>Dashboard Overview</h1>
        <p>Welcome back! Here's what's happening with your projects and contacts.</p>
    </header>

    <div class="grid-row-4">
        <div class="card stat-card">
            <div class="icon orange-icon">📁</div>
            <h3><?php echo htmlspecialchars($totalProjects); ?></h3>
            <p>Total Projects</p>
        </div>
        <div class="card stat-card">
            <div class="icon green-icon">📈</div>
            <h3><?php echo htmlspecialchars($activeProjects); ?></h3>
            <p>Active Projects</p>
        </div>
        <div class="card stat-card">
            <div class="icon blue-icon">👥</div>
            <h3><?php echo htmlspecialchars($totalContacts); ?></h3>
            <p>Total Contacts</p>
        </div>
        <div class="card stat-card">
            <div class="icon purple-icon">✔️</div>
            <h3><?php echo htmlspecialchars($activeLeads); ?></h3>
            <p>Active Leads</p>
        </div>
    </div>

    <div class="grid-row-2">
        <div class="card list-card">
            <div class="card-header">
                <h3>Recent Projects</h3>
                <a href="#">View all</a>
            </div>
            <div class="list-item">
                <span class="dot blue-dot"></span>
                <div class="item-details">
                    <h4>test</h4>
                    <p>test</p>
                </div>
                <span class="date">2/24/2026</span>
            </div>
        </div>

        <div class="card list-card">
            <div class="card-header">
                <h3>Recent Contacts</h3>
                <a href="#">View all</a>
            </div>
            <div class="list-item">
                <span class="dot green-dot"></span>
                <div class="item-details">
                    <h4>Giulian Bodiu</h4>
                    <p>test</p>
                </div>
                <span class="date">2/24/2026</span>
            </div>
        </div>
    </div>

    <div class="grid-row-3">
        <div class="card">
            <p class="card-subtitle">⏱️ Total Hours Estimated</p>
            <h2>100</h2>
            <p class="card-desc">Across all projects</p>
        </div>
        <div class="card">
            <p class="card-subtitle">🗓️ In Planning</p>
            <h2><?php echo htmlspecialchars($planningProjects); ?></h2>
            <p class="card-desc">Projects being scoped</p>
        </div>
        <div class="card">
            <p class="card-subtitle">📈 Completion Rate</p>
            <h2>0%</h2>
            <p class="card-desc">Projects completed or on-hold</p>
        </div>
    </div>
</main>