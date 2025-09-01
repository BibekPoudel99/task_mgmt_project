<?php
/**
 * Admin Configuration File
 * This file contains secure admin credentials
 * Password hash generated using: password_hash('your_new_secure_password', PASSWORD_DEFAULT)
 */
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    die('Access denied');
}

return [
    'admin_username' => 'admin',
    'admin_password_hash' => '$2y$10$EZgldHUEm3ij2c30vuIVn.BfMzkpGX6HPJiQe9gks/Agrb.wMbn8e',
    'session_timeout' => 1800, // 30 minutes
    'max_login_attempts' => 3,
    'lockout_duration' => 900 // 15 minutes
];

/*
To generate a new password hash, run this PHP code:
echo password_hash('YourNewPassword', PASSWORD_DEFAULT);

Then replace the 'admin_password_hash' value above with the generated hash.
*/
