<?php
session_start();
if (empty($_SESSION['user_logged_in'])) {
    header('Location: user_login.php');
    exit;
}
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .dashboard-container {
            max-width: 700px;
            margin: 60px auto;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 16px rgba(0,0,0,0.08);
        }
        .dashboard-title {
            font-weight: 700;
            color: #343a40;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .dashboard-desc {
            color: #555;
            margin-bottom: 2rem;
            text-align: center;
        }
        .dashboard-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-title">Welcome, <?= htmlspecialchars($username) ?>!</div>
        <div class="dashboard-desc">
            <span>
                Stay organized and efficient with your personal task dashboard.<br>
                View, manage, and track your tasks and deadlines all in one place.
            </span>
        </div>
        <div class="dashboard-actions">
            <a href="tasks.php" class="btn btn-primary">View Tasks</a>
            <a href="profile.php" class="btn btn-outline-secondary">Profile</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</body>
</html>