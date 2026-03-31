<?php
session_start();

// Already logged in → go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Basic validation
    if ($email === '' || $username === '' || $password === '' || $confirm === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check for existing email
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            // Check for existing username
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                $error = 'That username is already taken.';
            } else {
                // Create account
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (email, username, password_hash) VALUES (:email, :username, :hash)'
                );
                $stmt->execute([
                    ':email'    => $email,
                    ':username' => $username,
                    ':hash'     => $hash,
                ]);
                $success = 'Account created! You can now sign in.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oiler 10 CRM — Sign Up</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-box">O10</div>
        <div>
            <h2>Oiler 10</h2>
            <p>Customer Management</p>
        </div>
    </div>

    <h1 class="auth-title">Create an account</h1>
    <p class="auth-subtitle">Join the Oiler 10 CRM</p>

    <?php if ($error): ?>
        <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="auth-success">
            <?php echo htmlspecialchars($success); ?>
            <a href="login.php">Sign in &rarr;</a>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="signup.php" class="auth-form">
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
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="e.g. jsmith"
                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                required
                minlength="3"
            >
        </div>

        <div class="auth-field">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Min. 6 characters"
                required
                minlength="6"
            >
        </div>

        <div class="auth-field">
            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Re-enter your password"
                required
            >
        </div>

        <button type="submit" class="auth-btn">Create Account</button>
    </form>
    <?php endif; ?>

    <p class="auth-switch">
        Already have an account? <a href="login.php">Sign in</a>
    </p>
</div>

</body>
</html>
