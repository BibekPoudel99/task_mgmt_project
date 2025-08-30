<?php
session_start();
if (empty($_SESSION['admin_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Access denied</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4">';
    echo '<div class="alert alert-danger">Access denied. Admins only.</div>';
    echo '<a class="btn btn-primary" href="/layout/login.php">Go to login</a>';
    echo '</body></html>';
    exit;
}
?>
<?php
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --dl-color-theme-neutral-light: #f5f7fa;
            --dl-color-theme-neutral-dark: #333;
            --dl-color-theme-accent: #c3cfe2;
            --dl-color-theme-primary: #007bff;
            --dl-color-theme-secondary: #6c757d;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--dl-color-theme-neutral-light);
            color: var(--dl-color-theme-neutral-dark);
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .dashboard-header {
            background: linear-gradient(135deg, var(--dl-color-theme-neutral-light) 0%, var(--dl-color-theme-accent) 100%);
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
            color: var(--dl-color-theme-neutral-dark);
        }
        .dashboard-header p {
            font-size: 1.1rem;
            color: #555;
            font-weight: 400;
        }
        .user-management {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .user-management h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dl-color-theme-neutral-dark);
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
            border: 1px solid #d1d5db;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .search-bar input:focus, .search-bar select:focus {
            border-color: var(--dl-color-theme-primary);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            outline: none;
        }
        .search-bar button {
            background-color: var(--dl-color-theme-primary);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #fff;
            transition: background-color 0.3s, transform 0.2s;
        }
        .search-bar button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .table {
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
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
            color: var(--dl-color-theme-neutral-dark);
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
        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-bar input, .search-bar select, .search-bar button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="thq-heading-2">Admin Dashboard</h1>
            <p class="thq-body-large">Welcome to the admin dashboard. Use the navigation to manage the site.</p>
        </div>
        <section class="user-management">
            <h2 class="thq-heading-4">User Management</h2>
            <div class="search-bar">
                <input type="text" id="user-search" class="form-control" placeholder="Search users by name or email...">
                <select id="user-status" class="form-select">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <button class="btn btn-primary" onclick="loadUsers()">Search</button>
            </div>
            <div id="users-table-container">
                <div class="alert alert-info">Loading users...</div>
            </div>
        </section>
    </main>

    <script>
        let csrfToken = '';

        function loadUsers() {
            const q = document.getElementById('user-search').value;
            const status = document.getElementById('user-status').value;
            let url = '../admin_api/users.php?q=' + encodeURIComponent(q) + '&status=' + encodeURIComponent(status);
            fetch(url, {credentials: 'same-origin'})
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('users-table-container').innerHTML = '<div class="alert alert-danger">Failed to load users.</div>';
                        return;
                    }
                    renderUsersTable(data.users);
                });
        }

        function renderUsersTable(users) {
            if (!users.length) {
                document.getElementById('users-table-container').innerHTML = '<div class="alert alert-warning">No users found.</div>';
                return;
            }
            let html = `<table class="table table-bordered table-striped"><thead>
                <tr><th>ID</th><th>Username</th><th>Email</th><th>Status</th><th>Action</th></tr>
                </thead><tbody>`;
            users.forEach(u => {
                html += `<tr>
                    <td>${u.id}</td>
                    <td>${u.username}</td>
                    <td>${u.email}</td>
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

        function toggleUserStatus(userId, newStatus) {
            if (!confirm('Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this user?')) return;
            fetch('../admin_api/users.php', {credentials: 'same-origin'})
                .then(() => {
                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('action', 'set_active');
                    formData.append('user_id', userId);
                    formData.append('is_active', newStatus);
                    fetch('../admin_api/users.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        csrfToken = data.csrf_token || '';
                        if (data.success) {
                            loadUsers();
                        } else {
                            alert(data.message || 'Failed to update user.');
                        }
                    });
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
        });
    </script>

    <?php include __DIR__ . '/layout/footer.php'; ?>
</body>
</html>