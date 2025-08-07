<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .dashboard-container {
            max-width: 800px;
            margin: 60px auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        .dashboard-title {
            font-weight: 700;
            color: #343a40;
            margin-bottom: 2rem;
            text-align: center;
        }
        .btn-theme {
            background-color: #007bff;
            color: #fff;
            font-weight: 600;
        }
        .btn-theme:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-title">Welcome to the Admin Panel</div>
        <p class="text-center">You are logged in as <strong>admin</strong>.</p>
        <div class="d-flex justify-content-center">
            <a href="logout.php" class="btn btn-theme">Logout</a>
        </div>
        <!-- Add more admin features here -->
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>