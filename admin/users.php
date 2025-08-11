<?php
session_start();
if (empty($_SESSION['user_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Access denied</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4">';
    echo '<div class="alert alert-danger">Access denied. Admins only.</div>';
    echo '<a class="btn btn-primary" href="../login_choice.php">Go to login</a>';
    echo '</body></html>';
    exit;
}
require_once __DIR__ . '/../library/Session.php';
require_once __DIR__ . '/../library/Token.php';
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/nav.php';
// Generate CSRF for admin actions
$csrf = Token::input();
?>

<main class="thq-section-padding thq-section-max-width">
  <h1 class="thq-heading-2" style="margin: 16px 0;">Manage Users</h1>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-center">
        <div class="col-md-6">
          <input id="searchInput" type="text" class="form-control" placeholder="Search by username or email" />
        </div>
        <div class="col-md-3">
          <select id="statusFilter" class="form-select">
            <option value="">All</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
        <div class="col-md-3 text-end">
          <button id="searchBtn" class="btn btn-olive">Search</button>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="usersTableBody">
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/layout/footer.php'; ?>
<script>
  let csrfToken = (function(){
    const wrapper = document.createElement('div');
    wrapper.innerHTML = <?php echo json_encode($csrf); ?>;
    const inp = wrapper.querySelector('input[name="csrf_token"]');
    return inp ? inp.value : '';
  })();

  async function fetchUsers(q = '', status = '') {
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (status !== '') params.append('status', status);
    const res = await fetch('../admin_api/users.php?' + params.toString());
    const data = await res.json();
    return Array.isArray(data?.users) ? data.users : [];
  }

  async function updateUserStatus(userId, isActive) {
    const body = new FormData();
    body.append('action', 'set_active');
    body.append('user_id', userId);
    body.append('is_active', isActive ? '1' : '0');
    body.append('csrf_token', csrfToken);
    const res = await fetch('../admin_api/users.php', { method: 'POST', body });
    const data = await res.json();
    if (data?.csrf_token) csrfToken = data.csrf_token;
    return data?.success;
  }

  function renderRows(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = users.map(u => `
      <tr>
        <td>${u.id}</td>
        <td>${escapeHtml(u.username)}</td>
        <td>${u.email ? escapeHtml(u.email) : '-'}</td>
        <td>${u.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
        <td>
          ${u.is_active
            ? `<button class="btn btn-sm btn-outline-danger" onclick="onToggle(${u.id}, false)">Deactivate</button>`
            : `<button class="btn btn-sm btn-outline-success" onclick="onToggle(${u.id}, true)">Activate</button>`}
        </td>
      </tr>
    `).join('');
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  async function onToggle(id, toActive) {
    const ok = await updateUserStatus(id, toActive);
    if (ok) {
      load();
    } else {
      alert('Failed to update user status');
    }
  }

  async function load() {
    const q = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    const users = await fetchUsers(q, status);
    renderRows(users);
  }

  document.getElementById('searchBtn').addEventListener('click', load);
  document.getElementById('searchInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') load(); });
  document.getElementById('statusFilter').addEventListener('change', load);
  load();
</script>

