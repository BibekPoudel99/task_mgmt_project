<?php
// user_profile.php
// Placeholder profile page for user
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit();
}
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? 'user@example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - TaskFlow</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/user_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-cream">
    <nav class="navbar navbar-expand-lg navbar-light bg-cream border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold text-olive" href="user_dashboard.php">
                <i class="bi bi-check-circle-fill me-2"></i>TaskFlow
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-olive" href="user_dashboard.php"><i class="bi bi-house-door me-1"></i>Dashboard</a>
                <a class="nav-link text-olive" id="logoutLink" href="user_logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
            </div>
        </div>
    </nav>
    <main class="container py-5">
        <section class="mb-5">
            <h1 class="display-5 fw-bold text-olive mb-2"><i class="bi bi-person-circle me-2"></i>Profile</h1>
            <div class="card shadow-sm" style="max-width: 500px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-person-circle" style="font-size: 3rem; color: #6c757d;"></i>
                        <div class="ms-3">
                            <h4 class="mb-0"><?php echo htmlspecialchars($username); ?></h4>
                            <small class="text-muted">Member</small>
                        </div>
                    </div>
                    <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                    <p class="mb-0 text-muted">More profile features coming soon.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
