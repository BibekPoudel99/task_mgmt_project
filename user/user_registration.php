<?php
require_once '../library/Database.php'; // Make sure $conn is your PDO connection
$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($username === '' || $password === '' || $confirm_password === '') {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if user already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists. Please choose another.';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, hashed_password) VALUES (?, ?)");
                $stmt->execute([$username, $hashed_password]);
                $success = 'Registration successful! You can now <a href="user_login.php">login</a>.';
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
    <title>User Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/index.css">
    <style>
        body { background: #FAF7F2; }
        .register-container {
            max-width: 420px;
            margin: 10vh auto;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
        .register-title { font-weight: 800; color: #2D3B2D; margin-bottom: 1.25rem; text-align: center; }
        .btn-theme { background-color: #7AA874; color: #fff; font-weight: 600; border: none; }
        .btn-theme:hover { background-color: #6A9767; }
    </style>
</head>
<body class="bg-cream">
    <div class="register-container">
        <div class="register-title">User Registration</div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off" novalidate onsubmit="return validateForm();">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    required
                    autofocus
                >
                <div class="invalid-feedback" id="usernameError">Username is required.</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    required
                >
                <div class="invalid-feedback" id="passwordError">Password is required.</div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                >
                <div class="invalid-feedback" id="confirmPasswordError">Passwords must match.</div>
            </div>
            <button type="submit" class="btn btn-theme w-100">Register</button>
        </form>
        <div class="mt-3 text-center">
            <a href="user_login.php" class="btn btn-link">Already have an account? Login</a>
        </div>
    </div>
    <script>
    // Client-side validation
    function validateForm() {
        let valid = true;
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const usernameError = document.getElementById('usernameError');
        const passwordError = document.getElementById('passwordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');

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

        if (confirmPassword.value.trim() === '' || confirmPassword.value !== password.value) {
            confirmPassword.classList.add('is-invalid');
            confirmPasswordError.style.display = 'block';
            valid = false;
        } else {
            confirmPassword.classList.remove('is-invalid');
            confirmPasswordError.style.display = 'none';
        }

        return valid;
    }
    </script>