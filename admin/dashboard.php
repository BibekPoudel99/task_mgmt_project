<?php
session_start();

// Check if admin is logged in
if (empty($_SESSION['admin_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Access denied</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4">';
    echo '<div class="alert alert-danger">Access denied. Admins only.</div>';
    echo '<a class="btn btn-primary" href="login.php">Go to login</a>';
    echo '</body></html>';
    exit;
}

// Include required libraries for database connection
require_once '../library/Database.php';

// Initialize database connection for statistics
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Fetch real statistics from database (only user-related)
    $stats = [];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE COALESCE(is_active, 1) = 1");
    $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Inactive users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE COALESCE(is_active, 1) = 0");
    $stats['inactive_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // New users this month
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stats['new_users_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (Exception $e) {
    // Fallback to zero stats if database connection fails
    $stats = [
        'users' => 0,
        'active_users' => 0,
        'inactive_users' => 0,
        'new_users_month' => 0
    ];
}

// Handle AJAX requests for user management (only activation/deactivation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Include required libraries
    require_once '../library/Session.php';
    require_once '../library/Token.php';
    
    $csrf = $_POST['csrf_token'] ?? '';
    if (!Token::check($csrf)) {
        $new = Session::put('csrf_token', md5(uniqid()));
        http_response_code(419);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token', 'csrf_token' => $new]);
        exit;
    }
    
    $next = Session::put('csrf_token', md5(uniqid()));
    $action = $_POST['action'];
    
    try {
        if ($action === 'toggle_user_status') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $isActive = $_POST['is_active'] === '1' ? 1 : 0;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID', 'csrf_token' => $next]);
                exit;
            }
            
            $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
            $stmt->execute([$isActive, $userId]);
            echo json_encode(['success' => true, 'message' => 'User status updated successfully', 'csrf_token' => $next]);
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Unknown action', 'csrf_token' => $next]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'csrf_token' => $next]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Task Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css" />
    <link rel="stylesheet" href="../assets/index.css" />
    <style>
        html { 
            scroll-behavior: smooth; 
        }
        
        body { 
            margin: 0; 
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            background-color: #f5f7fa;
            color: #333;
        }
        
        :root {
            --dl-color-theme-neutral-light: #f5f7fa;
            --dl-color-theme-neutral-dark: #333;
            --dl-color-theme-accent: #c3cfe2;
            --dl-color-theme-primary: #007bff;
            --dl-color-theme-secondary: #6c757d;
            --accent-primary: #3182ce;
            --accent-secondary: #4299e1;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --bg-card: #ffffff;
            --border-primary: #e2e8f0;
            --shadow-light: rgba(49, 130, 206, 0.1);
        }



        /* Dashboard Styles */
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--dl-color-theme-accent) 100%);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .dashboard-header p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px var(--shadow-light);
            border-left: 4px solid var(--accent-primary);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-card .description {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .user-management {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .user-management h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-primary);
        }
        
        .search-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .search-bar input, .search-bar select {
            border-radius: 8px;
            border: 1px solid var(--border-primary);
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .search-bar input:focus, .search-bar select:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 5px rgba(49, 130, 206, 0.3);
            outline: none;
        }
        
        .search-bar button {
            background-color: var(--accent-primary);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #fff;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .search-bar button:hover {
            background-color: #2c5aa0;
            transform: translateY(-2px);
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
            background: var(--bg-card);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .table th, .table td {
            padding: 14px;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        
        .table th {
            background-color: #e9ecef;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8f9fa;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .btn-sm {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: transform 0.2s, background-color 0.3s;
        }
        
        .btn-sm:hover {
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #333;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            font-size: 0.95rem;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        /* Logout Section Styles */
        .logout-section {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .logout-btn {
            background: var(--accent-primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .logout-btn:hover {
            background: #2c5aa0;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-bar input, .search-bar select, .search-bar button {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .logout-section {
                bottom: 10px;
                right: 10px;
            }
        }
    </style>
</head>
<body>


    <!-- Dashboard Content -->
    <main class="dashboard-container">
        <!-- Statistics Cards -->
        <div class="dashboard-header">
            <h1 class="thq-heading-2">Admin Dashboard</h1>
            <p>Manage your TaskFlow system efficiently</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $stats['users']; ?></div>
                <div class="description">Registered users in the system</div>
            </div>
            <div class="stat-card">
                <h3>Active Users</h3>
                <div class="number"><?php echo $stats['active_users']; ?></div>
                <div class="description">Currently active users</div>
            </div>
            <div class="stat-card">
                <h3>Inactive Users</h3>
                <div class="number"><?php echo $stats['inactive_users']; ?></div>
                <div class="description">Deactivated users</div>
            </div>
            <div class="stat-card">
                <h3>New This Month</h3>
                <div class="number"><?php echo $stats['new_users_month']; ?></div>
                <div class="description">Users registered this month</div>
            </div>
        </div>

        <!-- User Management Section -->
        <section class="user-management" id="users-section">
            <h2 class="thq-heading-4">User Management</h2>
            
            <div class="search-bar">
                <input type="text" id="user-search" class="form-control" placeholder="Search users by name or email..." value="">
                <select id="user-status" class="form-select">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <button class="btn btn-primary" onclick="loadUsers()">Search</button>
                <button class="btn btn-secondary" onclick="clearSearch()">Clear</button>
            </div>
            
            <!-- Alert Container -->
            <div id="alert-container"></div>
            
            <div id="users-table-container">
                <div class="alert alert-info">Loading users...</div>
            </div>
        </section>
    </main>

    <!-- Logout Section -->
    <div class="logout-section">
        <button class="logout-btn" onclick="confirmLogout()">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
        </button>
    </div>

    <script>
        let csrfToken = '';

        // Show alert function
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            alertContainer.appendChild(alert);
            
            // Remove alert after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Load users from API
        function loadUsers() {
            const q = document.getElementById('user-search').value;
            const status = document.getElementById('user-status').value;
            let url = '../admin_api/users.php?q=' + encodeURIComponent(q) + '&status=' + encodeURIComponent(status);
            
            fetch(url, {credentials: 'same-origin'})
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('users-table-container').innerHTML = '<div class="alert alert-danger">Failed to load users: ' + (data.message || 'Unknown error') + '</div>';
                        return;
                    }
                    renderUsersTable(data.users);
                })
                .catch(error => {
                    document.getElementById('users-table-container').innerHTML = '<div class="alert alert-danger">Error loading users: ' + error.message + '</div>';
                });
        }

        // Render users table
        function renderUsersTable(users) {
            if (!users.length) {
                document.getElementById('users-table-container').innerHTML = '<div class="alert alert-warning">No users found.</div>';
                return;
            }
            
            let html = `<table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>`;
            
            users.forEach(u => {
                html += `<tr>
                    <td>${u.id}</td>
                    <td>${escapeHtml(u.username || '')}</td>
                    <td>${escapeHtml(u.email || '')}</td>
                    <td>${u.is_active == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
                    <td>
                        <button class="btn btn-sm ${u.is_active == 1 ? 'btn-warning' : 'btn-success'}" onclick="toggleUserStatus(${u.id}, ${u.is_active == 1 ? 0 : 1})">
                            ${u.is_active == 1 ? 'Deactivate' : 'Activate'}
                        </button>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            document.getElementById('users-table-container').innerHTML = html;
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Get CSRF token
        function getCSRFToken() {
            return fetch('../admin_api/users.php', {credentials: 'same-origin'})
                .then(res => res.json())
                .then(data => {
                    csrfToken = data.csrf_token || '';
                    return csrfToken;
                });
        }

        // Clear search function
        function clearSearch() {
            document.getElementById('user-search').value = '';
            document.getElementById('user-status').value = '';
            loadUsers();
        }

        // Toggle user status function
        function toggleUserStatus(userId, newStatus) {
            if (!confirm('Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this user?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'toggle_user_status');
            formData.append('user_id', userId);
            formData.append('is_active', newStatus);
            formData.append('csrf_token', csrfToken);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                csrfToken = data.csrf_token || '';
                if (data.success) {
                    showAlert(data.message, 'success');
                    loadUsers(); // Reload users
                    // Also reload statistics
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('An error occurred: ' + error.message, 'danger');
            });
        }

        // Scroll to users section
        function scrollToUsers() {
            document.getElementById('users-section').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Confirm logout function
        function confirmLogout() {
            if (confirm('Are you sure you want to logout? You will be redirected to the login page.')) {
                window.location.href = 'logout.php';
            }
        }

        // Handle Enter key in search input
        document.addEventListener('DOMContentLoaded', function() {
            // Get CSRF token and load users
            getCSRFToken().then(() => {
                loadUsers();
            });

            // Add enter key listener to search input
            document.getElementById('user-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    loadUsers();
                }
            });

            // Add change listener to status select
            document.getElementById('user-status').addEventListener('change', function() {
                loadUsers();
            });
        });
    </script>
</body>
</html>