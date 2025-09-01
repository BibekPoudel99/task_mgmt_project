<?php
require_once '../library/Database.php';
require_once '../library/Model.php';
require_once '../library/User.php';

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
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>).';
    } else {
        try {
            $user = new User();
            
            // Prepare data for User class
            $userData = [
                'username' => $username,
                'password' => $password,
                'cpassword' => $confirm_password
            ];
            
            $result = $user->createUser($userData);
            
            if ($result['success']) {
                $success = 'Registration successful! You can now <a href="user_login.php">login</a>.';
            } else {
                $error = implode(', ', $result['errors']);
            }
        } catch (Exception $e) {
            $error = 'Registration error: ' . htmlspecialchars($e->getMessage());
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
    <link rel="stylesheet" href="../assets/user_style.css">
</head>
<body class="bg-cream login-body">
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
                <div class="form-text text-muted">
                    Password must be at least 8 characters and contain at least one number and one special character.
                </div>
                <div class="invalid-feedback" id="passwordError">Password requirements not met.</div>
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

        // Username validation
        if (username.value.trim() === '') {
            username.classList.add('is-invalid');
            usernameError.textContent = 'Username is required.';
            usernameError.style.display = 'block';
            valid = false;
        } else {
            username.classList.remove('is-invalid');
            usernameError.style.display = 'none';
        }

        // Password validation
        const passwordValue = password.value.trim();
        if (passwordValue === '') {
            password.classList.add('is-invalid');
            passwordError.textContent = 'Password is required.';
            passwordError.style.display = 'block';
            valid = false;
        } else if (passwordValue.length < 8) {
            password.classList.add('is-invalid');
            passwordError.textContent = 'Password must be at least 8 characters long.';
            passwordError.style.display = 'block';
            valid = false;
        } else if (!/[0-9]/.test(passwordValue)) {
            password.classList.add('is-invalid');
            passwordError.textContent = 'Password must contain at least one number.';
            passwordError.style.display = 'block';
            valid = false;
        } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(passwordValue)) {
            password.classList.add('is-invalid');
            passwordError.textContent = 'Password must contain at least one special character.';
            passwordError.style.display = 'block';
            valid = false;
        } else {
            password.classList.remove('is-invalid');
            passwordError.style.display = 'none';
        }

        // Confirm password validation
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