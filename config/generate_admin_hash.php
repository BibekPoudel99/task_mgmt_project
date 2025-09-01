<?php
/**
 * Password Hash Generator for Admin
 * Run this script to generate a secure password hash for the admin account
 * 
 * Usage: php generate_admin_hash.php
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line for security reasons.');
}

echo "Admin Password Hash Generator\n";
echo "============================\n\n";

echo "Enter your new admin password: ";
$password = trim(fgets(STDIN));

if (strlen($password) < 8) {
    echo "Password must be at least 8 characters long.\n";
    exit(1);
}

// Validate password strength
if (!preg_match('/[A-Z]/', $password)) {
    echo "Warning: Password should contain at least one uppercase letter.\n";
}
if (!preg_match('/[a-z]/', $password)) {
    echo "Warning: Password should contain at least one lowercase letter.\n";
}
if (!preg_match('/[0-9]/', $password)) {
    echo "Warning: Password should contain at least one number.\n";
}
if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    echo "Warning: Password should contain at least one special character.\n";
}

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "\nGenerated password hash:\n";
echo $hash . "\n\n";

echo "Copy this hash and update the 'admin_password_hash' value in config/admin_config.php\n";
echo "Remember to delete this script after use for security.\n";
?>
