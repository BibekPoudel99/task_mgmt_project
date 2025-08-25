<?php
require_once __DIR__ . '/../library/Database.php';
require_once __DIR__ . '/../library/TaskUtils.php';

$taskUtils = new TaskUtils();
$taskUtils->updateMissedTasks(); // Don't assign if it returns void

// If you need a count, query it separately or modify the method above
echo "Tasks updated as missed at " . date('Y-m-d H:i:s') . "\n";