<?php
session_start();
require_once '../library/Database.php';
$db = new Database();
$conn = $db->getConnection();

$error = '';
$account_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'All fields are required.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, hashed_password, COALESCE(is_active,1) as is_active FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['hashed_password'])) {
                // Check if account is active
                if ((int)$user['is_active'] === 0) {
                    // Get the latest deactivation message
                    $logStmt = $conn->prepare('
                        SELECT description 
                        FROM user_activity_log 
                        WHERE user_id = ? AND activity_type = "account_deactivated" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    ');
                    $logStmt->execute([$user['id']]);
                    $logResult = $logStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $account_message = $logResult ? $logResult['description'] : 
                        'Your account has been deactivated by an administrator. Please contact support.';
                    
                    $error = 'account_deactivated';
                } else {
                    // Login successful
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'user';
                    header('Location: user_dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/user_style.css">
</head>
<body class="bg-cream login-body">
    <div class="login-container">
        <div class="login-title">User Login</div>
        
        <?php if ($error === 'account_deactivated'): ?>
            <div class="alert-deactivated">
                <div class="text-center">
                    <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                    <h5 class="alert-deactivated-title">Account Deactivated</h5>
                </div>
                <p class="alert-deactivated-text">>
                    <?php echo htmlspecialchars($account_message); ?>
                </p>
                
                <div class="support-contact">
                    <p class="support-contact-text">
                        <i class="bi bi-envelope me-2"></i>
                        Need help? Contact support: 
                        <a href="mailto:poudelbibek86@gmail.com" class="support-contact-link">
                            support@taskflow.com
                        </a>
                    </p>
                </div>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post" autocomplete="off" novalidate onsubmit="return validateForm();">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    <?php echo ($error === 'account_deactivated') ? 'readonly' : 'required'; ?>
                    autofocus
                >
                <div class="invalid-feedback" id="usernameError">Username is required.</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        <?php echo ($error === 'account_deactivated') ? 'readonly' : 'required'; ?>
                    >
                    <span class="input-group-text toggle-password" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
                <div class="invalid-feedback" id="passwordError">Password is required.</div>
            </div>
            
            <?php if ($error !== 'account_deactivated'): ?>
                <button type="submit" class="btn btn-theme w-100">Login</button>
            <?php else: ?>
                <div class="text-center">
                    <button type="button" class="btn btn-secondary w-100" disabled>
                        <i class="bi bi-lock-fill me-2"></i>
                        Account Deactivated
                    </button>
                </div>
            <?php endif; ?>
        </form>
        <div class="mt-3 text-center"><a href="../index.html">Back</a></div>
        <div class="mt-3 text-center">
            <a href="user_registration.php" class="btn btn-link">Register here</a>
        </div>
    </div>
    <script>
    // Password visibility toggle
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });

    // Client-side validation
    function validateForm() {
        let valid = true;
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const usernameError = document.getElementById('usernameError');
        const passwordError = document.getElementById('passwordError');

        if (username.value.trim() === '') {
            username.classList.add('is-invalid');
            usernameError.style.display = 'block';
            valid = false;
        } else {
            username.classList.remove('is-invalid');
            usernameError.style.display = 'none';
        }

        if (password.value.trim() === '') {
            password.classList.add('is-invalid');
            passwordError.style.display = 'block';
            valid = false;
        } else {
            password.classList.remove('is-invalid');
            passwordError.style.display = 'none';
        }

        return valid;
    }
    </script>
</body>
</html>