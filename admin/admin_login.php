<?php
session_start();
require_once __DIR__ . '/../library/Database.php';
require_once __DIR__ . '/../library/Hash.php';

$db = new Database();
$pdo = $db->getConnection();
$error = '';

// Explicit admin list by username (adjust as needed)
$admins = [
  'admin',
  
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!in_array($username, $admins, true)) {
        $error = 'You are not authorized as admin.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, username, hashed_password, COALESCE(is_active,1) as is_active FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && (int)$user['is_active'] === 1 && Hash::verify($password, $user['hashed_password'])) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'admin';
                header('Location: dashboard.php');
                exit;
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .card { max-width: 420px; margin: 10vh auto; }
  </style>
</head>
<body>
  <div class="card shadow-sm">
    <div class="card-header bg-light"><h5 class="mb-0">Admin Login</h5></div>
    <div class="card-body">
      <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input class="form-control" name="username" required />
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" name="password" required />
        </div>
        <button class="btn btn-primary w-100" type="submit">Login</button>
      </form>
      <div class="mt-3 text-center"><a href="../login_choice.php">Back</a></div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

