<?php
session_start();
require_once '../library/Hash.php';
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

            if ($user && Hash::verify($password, $user['hashed_password'])) {
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
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/index.css">
    <style>
        body { background: #FAF7F2; }
        .login-container {
            max-width: 420px;
            margin: 10vh auto;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
        .login-title { font-weight: 800; color: #2D3B2D; margin-bottom: 1.25rem; text-align: center; }
        .btn-theme { background-color: #7AA874; color: #fff; font-weight: 600; border: none; }
        .btn-theme:hover { background-color: #6A9767; }
        
        .alert-deactivated {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 1px solid #f87171;
            color: #dc2626;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .alert-deactivated .alert-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .support-contact {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-cream">
    <div class="login-container">
        <div class="login-title">User Login</div>
        
        <?php if ($error === 'account_deactivated'): ?>
            <div class="alert-deactivated">
                <div class="text-center">
                    <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                    <h5 style="color: #dc2626; font-weight: 600; margin-bottom: 15px;">Account Deactivated</h5>
                </div>
                <p style="margin: 0; line-height: 1.5; text-align: center;">
                    <?php echo htmlspecialchars($account_message); ?>
                </p>
                
                <div class="support-contact">
                    <p style="margin: 0; font-size: 14px; color: #64748b; font-weight: 500;">
                        <i class="bi bi-envelope me-2"></i>
                        Need help? Contact support: 
                        <a href="mailto:poudelbibek86@gmail.com" style="color: #3182ce; text-decoration: none;">
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
                    <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
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