<?php
// Default redirect location
$location = isset($location) ? $location : '/task_mgmt/index.html';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Task Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
        }
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .error-number {
            font-size: 8rem;
            font-weight: 900;
            color: #667eea;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: transform 0.3s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .error-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <i class="bi bi-exclamation-triangle error-icon"></i>
            <div class="error-number">404</div>
            <h1 class="error-title">Page Not Found</h1>
            <p class="error-message">
                Oops! The page you're looking for doesn't exist. It might have been moved, deleted, or you entered the wrong URL.
            </p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="<?= htmlspecialchars($location); ?>" class="btn btn-primary btn-home">
                    <i class="bi bi-house-door me-2"></i>Go to Home
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Go Back
                </button>
            </div>
        </div>
    </div>
</body>
</html>