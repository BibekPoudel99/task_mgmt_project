<?php
// Secure session settings MUST be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

session_start();

// Load admin configuration
$admin_config = require_once '../config/admin_config.php';

$error = '';

// Initialize login attempts tracking
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if account is locked due to too many failed attempts
if ($_SESSION['admin_login_attempts'] >= $admin_config['max_login_attempts']) {
    $time_since_last = time() - $_SESSION['last_attempt_time'];
    if ($time_since_last < $admin_config['lockout_duration']) {
        $remaining_time = $admin_config['lockout_duration'] - $time_since_last;
        $minutes = floor($remaining_time / 60);
        $seconds = $remaining_time % 60;
        $error = "Account locked due to too many failed attempts. Try again in {$minutes}m {$seconds}s.";
    } else {
        // Reset attempts after lockout period
        $_SESSION['admin_login_attempts'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
            $_SESSION['admin_login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } elseif ($username === $admin_config['admin_username'] && 
                  password_verify($password, $admin_config['admin_password_hash'])) {
            // Successful login
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_last_activity'] = time();
            
            // Reset login attempts on successful login
            $_SESSION['admin_login_attempts'] = 0;
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Log successful login (optional)
            error_log("Admin login successful from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
            $_SESSION['admin_login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            
            // Log failed login attempt (optional)
            error_log("Admin login failed for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
    }
    
    // Generate new CSRF token after each submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin_style.css">
</head>
<body class="admin-login-body">
    <div class="login-container admin-login-container">
        <h2 class="login-title admin-login-title">Admin Login</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger admin-alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control admin-form-control" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control admin-form-control" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn btn-primary admin-btn-primary" 
                    <?php echo !empty($error) && strpos($error, 'locked') !== false ? 'disabled' : ''; ?>>
                Login
            </button>
        </form>
        
        <div class="back-link admin-back-link">
            <a href="../index.html">← Back to Home</a>
        </div>
    </div>
</body>
</html>
