<?php
session_start();
if (empty($_SESSION['admin_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Access denied</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4">';
    echo '<div class="alert alert-danger">Access denied. Admins only.</div>';
    echo '<a class="btn btn-primary" href="../login_choice.php">Go to login</a>';
    echo '</body></html>';
    exit;
}
?>
<?php
// Simple admin dashboard that uses the extracted layout
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/nav.php';
?>

<main class="thq-section-padding thq-section-max-width" style="margin-top:10px; padding-top: 10px;">
  <h1 class="thq-heading-2" style="margin: 5px 0;">Admin Dashboard</h1>
  <p class="thq-body-large">Welcome to the admin dashboard. Use the navigation to manage the site.</p>
</main>

<section style="margin-top:0px;">
    <h2 class="thq-heading-4">User Management</h2>
    <div class="mb-3">
      <input type="text" id="user-search" class="form-control" placeholder="Search users by name or email..." style="max-width:300px;display:inline-block;">
      <select id="user-status" class="form-select" style="width:auto;display:inline-block;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
      <button class="btn btn-primary" onclick="loadUsers()">Search</button>
    </div>
    <div id="users-table-container">
      <div>Loading users...</div>
    </div>
  </section>

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
    document.getElementById('users-table-container').innerHTML = '<div>No users found.</div>';
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
  // Get CSRF token first
  fetch('../admin_api/users.php', {credentials: 'same-origin'})
    .then(() => {
      // Now send POST
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

// Initial load
document.addEventListener('DOMContentLoaded', function() {
  loadUsers();
});
</script>

<?php include __DIR__ . '/layout/footer.php';

