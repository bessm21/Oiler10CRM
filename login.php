<?php
session_start();

// Already logged in → go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, username, email, password_hash, status, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'denied') {
                $error = 'Your account has been denied. Please contact an administrator.';
            } else {
                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['status']   = $user['status'] ?? 'pending';
                $_SESSION['role']     = $user['role']   ?? 'user';

                if ($user['role'] === 'admin' || $user['status'] === 'approved') {
                    header('Location: index.php');
                } else {
                    header('Location: pending.php');
                }
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oiler 10 CRM — Login</title>
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

    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to your account</p>

    <?php if ($error): ?>
        <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="auth-form">
        <div class="auth-field">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                required
                autofocus
            >
        </div>

        <div class="auth-field">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                required
            >
        </div>

        <button type="submit" class="auth-btn">Sign In</button>
    </form>

    <p class="auth-switch">
        Don't have an account? <a href="signup.php">Create one</a>
    </p>
</div>

</body>
</html>
