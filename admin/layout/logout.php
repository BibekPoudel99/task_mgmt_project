<?php
session_start();
session_destroy();
header('Location: /task_mgmt/login_choice.php');
exit;