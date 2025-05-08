<?php
/**
 * Chess Game Application - Main Entry Point
 */

// Define the application root
define('APP_ROOT', dirname(__FILE__));

// Load configuration
require_once APP_ROOT . '/../config/config.php';

// Initialize session
session_start();

// Routing logic
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Simple routing
switch ($page) {
    case 'login':
        require_once APP_ROOT . '/views/login.php';
        break;
    case 'register':
        require_once APP_ROOT . '/views/register.php';
        break;
    case 'game':
        header('Location: Actual_game/index.php');
        exit;
    case 'profile':
        require_once APP_ROOT . '/views/profile.php';
        break;
    case 'logout':
        // Destroy session and redirect
        session_destroy();
        header('Location: index.php');
        exit;
    default:
        // Home page
        require_once APP_ROOT . '/views/home.php';
} 