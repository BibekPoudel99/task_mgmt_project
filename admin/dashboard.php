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
        if ($action === 'get_csrf_token') {
            // Just return a fresh CSRF token
            echo json_encode(['success' => true, 'csrf_token' => $next]);
            exit;
        }
        
        if ($action === 'toggle_user_status') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $isActive = $_POST['is_active'] === '1' ? 1 : 0;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID', 'csrf_token' => $next]);
                exit;
            }
            
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Get username for logging
                $userStmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
                $userStmt->execute([$userId]);
                $username = $userStmt->fetchColumn();
                
                // Update user status
                $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
                $stmt->execute([$isActive, $userId]);
                
                // If deactivating user (is_active = 0), handle task reassignments
                if ($isActive == 0) {
                    // Unassign all tasks assigned to this user
                    $taskStmt = $pdo->prepare('UPDATE tasks SET assignee_id = NULL WHERE assignee_id = ?');
                    $taskStmt->execute([$userId]);
                    $unassignedTasks = $taskStmt->rowCount();
                    
                    // Log the deactivation for the user to see when they try to login
                    $logStmt = $pdo->prepare('INSERT INTO user_activity_log (user_id, activity_type, description, created_at) VALUES (?, ?, ?, NOW())');
                    $logStmt->execute([
                        $userId, 
                        'account_deactivated', 
                        'Your account has been deactivated by an administrator. Please contact support if you believe this is an error.'
                    ]);
                    
                    $pdo->commit();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => "User '{$username}' has been deactivated. {$unassignedTasks} task(s) have been unassigned.",
                        'csrf_token' => $next
                    ]);
                } else {
                    // Reactivating user
                    // Log the reactivation
                    $logStmt = $pdo->prepare('INSERT INTO user_activity_log (user_id, activity_type, description, created_at) VALUES (?, ?, ?, NOW())');
                    $logStmt->execute([
                        $userId, 
                        'account_reactivated', 
                        'Your account has been reactivated by an administrator. Welcome back!'
                    ]);
                    
                    $pdo->commit();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => "User '{$username}' has been reactivated.",
                        'csrf_token' => $next
                    ]);
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to update user status: ' . $e->getMessage(), 'csrf_token' => $next]);
            }
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
    <link rel="stylesheet" href="../assets/admin_style.css" />
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
            return fetch('dashboard.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_csrf_token'
            })
                .then(res => res.json())
                .then(data => {
                    csrfToken = data.csrf_token || '';
                    return csrfToken;
                })
                .catch(error => {
                    console.error('Error getting CSRF token:', error);
                    return '';
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

            // Check if we have a CSRF token, if not get one first
            if (!csrfToken) {
                getCSRFToken().then(() => {
                    toggleUserStatus(userId, newStatus);
                }).catch(error => {
                    showAlert('Failed to get security token: ' + error.message, 'danger');
                });
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