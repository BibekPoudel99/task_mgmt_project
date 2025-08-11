  <?php
  require_once __DIR__.'/../library/Database.php';
  try {
    $db = new Database();
    $pdo = $db->getConnection();
    $stmt = $pdo->query('SELECT 1');
    echo 'DB OK';
  } catch (Throwable $e) {
    http_response_code(500);
    echo 'DB ERROR: ' . htmlspecialchars($e->getMessage());
  }