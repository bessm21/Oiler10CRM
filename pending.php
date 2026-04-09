<?php
session_start();

// Not logged in → go to login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Already approved or admin → go to CRM
$role   = $_SESSION['role']   ?? 'user';
$status = $_SESSION['status'] ?? 'pending';

if ($role === 'admin' || $status === 'approved') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oiler 10 CRM — Pending Approval</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-card">
    <div class="auth-logo">
        <img class="brand-logo" src="https://static.wixstatic.com/media/fc911f_11934eb0cff34f33943001a4acc3fcc9~mv2.png/v1/fill/w_392,h_128,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/2021_O10LogoFile%20copy.png" alt="Oiler 10">
        <div>
            <h2>Oiler 10</h2>
            <p>Customer Management</p>
        </div>
    </div>

    <?php if ($status === 'denied'): ?>
        <h1 class="auth-title">Access Denied</h1>
        <div class="auth-error">
            Your account request has been denied. Please contact an administrator if you believe this is a mistake.
        </div>
    <?php else: ?>
        <h1 class="auth-title">Pending Approval</h1>
        <div class="pending-notice">
            <strong>Your account is pending admin approval.</strong><br>
            You'll receive access once an administrator reviews your request.
        </div>
    <?php endif; ?>

    <p style="text-align:center;font-size:0.85rem;color:var(--text-muted);margin-top:1rem;">
        Signed in as <strong><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></strong>
    </p>

    <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
        <?php if ($status === 'pending'): ?>
        <button onclick="checkStatus()" class="auth-btn" style="flex:1;" id="checkBtn">
            Check Status
        </button>
        <?php endif; ?>
        <a href="logout.php" class="auth-btn" style="flex:1;display:block;text-align:center;text-decoration:none;background:#64748b;">
            Sign Out
        </a>
    </div>
</div>

<script>
function checkStatus() {
    var btn = document.getElementById('checkBtn');
    btn.textContent = 'Checking...';
    btn.disabled = true;
    fetch('pages/check_status.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'approved' || data.role === 'admin') {
                window.location.href = 'index.php';
            } else {
                btn.textContent = 'Still pending — check back later';
                setTimeout(function() {
                    btn.textContent = 'Check Status';
                    btn.disabled = false;
                }, 3000);
            }
        })
        .catch(function() {
            btn.textContent = 'Check Status';
            btn.disabled = false;
        });
}
</script>

</body>
</html>
