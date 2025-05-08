<?php
/**
 * Application Configuration
 */

// Session configuration - Must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
define('SESSION_NAME', 'chess_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Change to your database username
define('DB_PASS', '');      // Change to your database password
define('DB_NAME', 'chess_game');

// Application configuration
define('APP_NAME', 'Chess Game');
define('APP_URL', 'http://localhost/Chess_Game');

// Error reporting - Only enable detailed errors in development
$environment = 'development'; // Change to 'production' for live site
if ($environment === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Time zone
date_default_timezone_set('UTC'); 