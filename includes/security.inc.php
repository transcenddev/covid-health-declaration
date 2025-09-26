<?php

/**
 * Security Functions for COVID-19 Health Declaration System
 * Provides CSRF protection, input validation, sanitization, and security logging
 */

// Generate CSRF token
function generateCSRFToken() {
    // Only generate if session is active
    if (session_status() != PHP_SESSION_ACTIVE) {
        return '';
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    // Can only validate if session is active
    if (session_status() != PHP_SESSION_ACTIVE) {
        return false;
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Regenerate CSRF token (call after successful form submission)
function regenerateCSRFToken() {
    // Only regenerate if session is active
    if (session_status() != PHP_SESSION_ACTIVE) {
        return '';
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

// Sanitize input data
function sanitizeInput($input) {
    if (is_string($input)) {
        // Remove whitespace from beginning and end
        $input = trim($input);
        // Remove backslashes
        $input = stripslashes($input);
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate age range
function validateAge($age) {
    $age = filter_var($age, FILTER_VALIDATE_INT);
    return $age !== false && $age >= 0 && $age <= 150;
}

// Validate temperature range
function validateTemperature($temp) {
    $temp = filter_var($temp, FILTER_VALIDATE_FLOAT);
    return $temp !== false && $temp >= 30.0 && $temp <= 50.0;
}

// Validate enum values for health declaration
function validateHealthEnum($value, $allowedValues = ['Yes', 'No']) {
    return in_array($value, $allowedValues, true);
}

// Validate gender
function validateGender($gender) {
    $allowedGenders = ['Male', 'Female', 'Other'];
    return in_array($gender, $allowedGenders, true);
}

// Validate nationality (basic string validation)
function validateNationality($nationality) {
    return is_string($nationality) && 
           strlen($nationality) >= 2 && 
           strlen($nationality) <= 100 && 
           preg_match('/^[a-zA-Z\s\-\.\']+$/', $nationality);
}

// Validate full name
function validateFullName($name) {
    return is_string($name) && 
           strlen($name) >= 2 && 
           strlen($name) <= 100 && 
           preg_match('/^[a-zA-Z\s\-\.\']+$/', $name);
}

// Log security events
function logSecurityEvent($message, $level = 'WARNING') {
    $logFile = __DIR__ . '/../logs/security.log';
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $requestURI = $_SERVER['REQUEST_URI'] ?? 'unknown';
    
    $logEntry = sprintf(
        "[%s] %s - IP: %s - URI: %s - Agent: %s - Message: %s\n",
        $timestamp,
        $level,
        $clientIP,
        $requestURI,
        $userAgent,
        $message
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Rate limiting function (basic implementation)
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    // Can only work if session is active
    if (session_status() != PHP_SESSION_ACTIVE) {
        return true; // Allow if no session (will be handled elsewhere)
    }
    
    $key = 'rate_limit_' . $action;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Remove old attempts outside time window
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Check if limit exceeded
    if (count($_SESSION[$key]) >= $maxAttempts) {
        return false;
    }
    
    // Add current attempt
    $_SESSION[$key][] = $now;
    return true;
}

// Secure session configuration
function configureSecureSession() {
    // Only set session parameters if no session is active
    if (session_status() == PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        
        // Set session timeout (30 minutes)
        ini_set('session.gc_maxlifetime', 1800);
        ini_set('session.cookie_lifetime', 1800);
    }
    
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically (only if session is active)
    if (session_status() == PHP_SESSION_ACTIVE) {
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Initialize secure session (call this at the very beginning of scripts)
function initializeSecureSession() {
    // Configure and start session only if none exists
    if (session_status() == PHP_SESSION_NONE) {
        // Set secure session parameters before starting
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', 1800);
        ini_set('session.cookie_lifetime', 1800);
        
        // Start the session
        session_start();
        
        // Set initial timestamp
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    // Handle session regeneration for existing sessions
    if (isset($_SESSION['last_regeneration'])) {
        if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Check if user session is valid and not expired
function isValidSession() {
    // Only check if session is already active
    if (session_status() != PHP_SESSION_ACTIVE) {
        return false;
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 1800) { // 30 minutes
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Sanitize output for display
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Generate secure random string
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

?>