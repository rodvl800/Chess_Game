<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Default XAMPP user, change for production
define('DB_PASS', '');      // Default XAMPP password, change for production
define('DB_NAME', 'chess_game');

// Application configuration
define('APP_NAME', 'Chess Game');
define('APP_URL', 'http://localhost/Chess_Game');

// Session configuration
define('SESSION_NAME', 'chess_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('UTC'); 