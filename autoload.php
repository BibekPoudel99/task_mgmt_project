<?php
/**
 * Task Management System Autoloader
 * 
 * This autoloader handles automatic loading of classes from the library directory
 * and provides utility functions for common includes.
 */

// Define base paths
define('TASK_MGMT_ROOT', __DIR__);
define('TASK_MGMT_LIBRARY', TASK_MGMT_ROOT . '/library');

/**
 * PSR-4 compatible autoloader for library classes
 */
spl_autoload_register(function ($className) {
    // Define the base namespace and directory
    $baseNamespace = 'TaskMgmt\\';
    $baseDirectory = TASK_MGMT_LIBRARY . '/';
    
    // Check if the class uses our namespace
    if (strpos($className, $baseNamespace) === 0) {
        // Remove the base namespace
        $relativeClass = substr($className, strlen($baseNamespace));
        
        // Replace namespace separators with directory separators
        $file = $baseDirectory . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Fallback: try to load from library directory directly
    $file = TASK_MGMT_LIBRARY . '/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

/**
 * Utility function to load common dependencies
 */
function load_core_libraries() {
    static $loaded = false;
    
    if (!$loaded) {
        require_once TASK_MGMT_LIBRARY . '/Database.php';
        require_once TASK_MGMT_LIBRARY . '/Session.php';
        require_once TASK_MGMT_LIBRARY . '/Token.php';
        $loaded = true;
    }
}

/**
 * Utility function to load user-related libraries
 */
function load_user_libraries() {
    load_core_libraries();
    require_once TASK_MGMT_LIBRARY . '/Hash.php';
    require_once TASK_MGMT_LIBRARY . '/User.php';
    require_once TASK_MGMT_LIBRARY . '/Validation.php';
}

/**
 * Utility function to load task-related libraries
 */
function load_task_libraries() {
    load_core_libraries();
    require_once TASK_MGMT_LIBRARY . '/TaskUtils.php';
}

/**
 * Utility function to load API-related libraries
 */
function load_api_libraries() {
    load_core_libraries();
    require_once TASK_MGMT_LIBRARY . '/TaskUtils.php';
}

/**
 * Load all essential libraries at once
 */
function load_all_libraries() {
    $libraries = [
        'Database.php',
        'Session.php', 
        'Token.php',
        'Hash.php',
        'User.php',
        'Validation.php',
        'TaskUtils.php',
        'Authentication.php',
        'Config.php',
        'Cookie.php',
        'Message.php',
        'Model.php',
        'Redirect.php',
        'Request.php',
        'Upload.php'
    ];
    
    foreach ($libraries as $library) {
        $file = TASK_MGMT_LIBRARY . '/' . $library;
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

/**
 * Initialize database connection with error handling
 */
function init_database() {
    try {
        $db = new Database();
        return $db->getConnection();
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Helper function to check if user is authenticated
 */
function check_user_auth($role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $isLoggedIn = !empty($_SESSION['user_logged_in']);
    
    if ($role) {
        return $isLoggedIn && ($_SESSION['role'] ?? '') === $role;
    }
    
    return $isLoggedIn;
}

/**
 * Helper function to check admin authentication
 */
function check_admin_auth() {
    return check_user_auth('admin') || !empty($_SESSION['admin_logged_in']);
}

/**
 * Helper function to redirect with message
 */
function redirect_with_message($url, $message, $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    
    header("Location: $url");
    exit;
}

/**
 * Helper function to get and clear flash messages
 */
function get_flash_message() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $message = $_SESSION['flash_message'] ?? null;
    $type = $_SESSION['flash_type'] ?? 'info';
    
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    
    return $message ? ['message' => $message, 'type' => $type] : null;
}

// Auto-start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
