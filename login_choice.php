<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css" />
  <style>
    body { background: var(--cream, #faf8f3); }
    .card { max-width: 520px; margin: 10vh auto; }
  </style>
  </head>
<body class="bg-cream">
  <div class="card shadow-sm">
    <div class="card-header bg-light"><h5 class="mb-0">Choose Login Type</h5></div>
    <div class="card-body p-4">
      <div class="d-grid gap-3">
        <a class="btn btn-olive btn-lg" href="admin/layout/login.php">Login as Admin</a>
        <a class="btn btn-sage btn-lg" href="user/user_login.php">Login as User</a>
      </div>
      <p class="mt-4 text-muted small mb-0">
        Admins are provisioned by the system. If you don't have admin access, use the user login.
      </p>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

