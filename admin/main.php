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
?>
<?php
// Generic wrapper page to demonstrate layout reuse
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/nav.php';
?>

<main class="thq-section-padding thq-section-max-width">
  <h1 class="thq-heading-2" style="margin: 16px 0;">Main</h1>
  <p class="thq-body-large">This page uses the shared admin layout (header/nav/footer).</p>
</main>

<?php include __DIR__ . '/layout/footer.php';

