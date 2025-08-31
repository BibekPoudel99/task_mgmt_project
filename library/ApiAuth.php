<?php
/**
 * Centralized API Authentication and Response Helper
 * Reduces code duplication across API endpoints
 */
class ApiAuth {
    
    /**
     * Initialize API response with common headers
     */
    public static function initApiResponse() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');
    }
    
    /**
     * Check if user is authenticated with rate limiting
     * @return array User info if authenticated, false if not
     */
    public static function requireUserAuth() {
        // Check rate limiting first
        if (!self::checkRateLimit()) {
            self::errorResponse('Too many requests. Please try again later.', 429);
            exit;
        }
        
        if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
            self::unauthorizedResponse();
            exit;
        }
        return [
            'user_id' => (int) $_SESSION['user_id'],
            'logged_in' => $_SESSION['user_logged_in']
        ];
    }
    
    /**
     * Check if admin is authenticated with rate limiting
     */
    public static function requireAdminAuth() {
        // Check rate limiting first
        if (!self::checkRateLimit()) {
            self::errorResponse('Too many requests. Please try again later.', 429);
            exit;
        }
        
        if (empty($_SESSION['admin_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
            self::unauthorizedResponse();
            exit;
        }
    }
    
    /**
     * Simple rate limiting: max 100 requests per 5 minutes per IP
     */
    private static function checkRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheFile = __DIR__ . '/../cache/rate_limit_' . md5($ip) . '.tmp';
        $cacheDir = dirname($cacheFile);
        
        // Create cache directory if it doesn't exist
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $maxRequests = 100;
        $timeWindow = 300; // 5 minutes
        $now = time();
        
        // Read current count
        $data = [];
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = json_decode($content, true) ?: [];
        }
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Check if limit exceeded
        if (count($data) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        $data[] = $now;
        
        // Save back to file
        file_put_contents($cacheFile, json_encode($data), LOCK_EX);
        
        return true;
    }
    
    /**
     * Get database connection using singleton pattern
     */
    public static function getDatabase() {
        static $db = null;
        if ($db === null) {
            require_once __DIR__ . '/Database.php';
            $db = new Database();
        }
        return $db;
    }
    
    /**
     * Validate CSRF token and generate new one
     */
    public static function validateCsrfToken($token) {
        require_once __DIR__ . '/Token.php';
        require_once __DIR__ . '/Session.php';
        
        if (!Token::check($token)) {
            $new = Session::put('csrf_token', md5(uniqid()));
            http_response_code(419);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid CSRF token', 
                'csrf_token' => $new
            ]);
            exit;
        }
        return Session::put('csrf_token', md5(uniqid()));
    }
    
    /**
     * Standard unauthorized response with logging
     */
    public static function unauthorizedResponse() {
        self::logError('Unauthorized access attempt', 401);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    }
    
    /**
     * Standard error response with logging
     */
    public static function errorResponse($message, $code = 500, $extraData = []) {
        // Log the error for debugging
        self::logError($message, $code, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
        
        http_response_code($code);
        $response = array_merge(['success' => false, 'message' => $message], $extraData);
        echo json_encode($response);
    }
    
    /**
     * Log errors to file with context
     */
    private static function logError($message, $code, $backtrace = []) {
        $logFile = __DIR__ . '/../logs/api_errors.log';
        $logDir = dirname($logFile);
        
        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $context = '';
        if (!empty($backtrace)) {
            $caller = $backtrace[0] ?? [];
            $context = sprintf(' [%s:%s]', 
                basename($caller['file'] ?? 'unknown'), 
                $caller['line'] ?? '0'
            );
        }
        
        $logEntry = sprintf(
            "[%s] HTTP %d: %s | User: %s | IP: %s | URI: %s | UA: %s%s\n",
            $timestamp, $code, $message, $userId, $ip, $requestUri, $userAgent, $context
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Standard success response
     */
    public static function successResponse($data = [], $message = null) {
        $response = ['success' => true];
        if ($message) $response['message'] = $message;
        $response = array_merge($response, $data);
        echo json_encode($response);
    }
}
