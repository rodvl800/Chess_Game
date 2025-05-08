<?php
require_once '../includes/config.php';
require_once '../php/User.php';
require_once '../php/Game.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
    exit;
}

// Get the request path
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/Chess_Game/php/api.php', '', $path);
$path = trim($path, '/');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get request body for POST/PUT requests
$body = [];
if ($method === 'POST' || $method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);
}

// Route the request
switch ($path) {
    case 'game/new':
        if ($method === 'POST') {
            handleNewGame($body);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        break;
        
    case (preg_match('/^game\/(\d+)$/', $path, $matches) ? true : false):
        $gameId = $matches[1];
        switch ($method) {
            case 'GET':
                handleGetGame($gameId);
                break;
            case 'POST':
                handleMakeMove($gameId, $body);
                break;
            case 'PUT':
                handleUpdateGame($gameId, $body);
                break;
            default:
                sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        break;
        
    case 'users':
        if ($method === 'GET') {
            handleGetUsers();
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        break;
        
    default:
        sendJsonResponse(['success' => false, 'message' => 'Not found'], 404);
}

// Handler functions
function handleNewGame($data) {
    if (!isset($data['opponent_id'])) {
        sendJsonResponse(['success' => false, 'message' => 'Opponent ID is required'], 400);
        return;
    }
    
    $opponentId = intval($data['opponent_id']);
    if ($opponentId == $_SESSION['user_id']) {
        sendJsonResponse(['success' => false, 'message' => 'Cannot play against yourself'], 400);
        return;
    }
    
    $game = new Game();
    $isWhite = rand(0, 1) === 1;
    $whiteId = $isWhite ? $_SESSION['user_id'] : $opponentId;
    $blackId = $isWhite ? $opponentId : $_SESSION['user_id'];
    
    $result = $game->createGame($whiteId, $blackId);
    
    if ($result['success']) {
        sendJsonResponse(['success' => true, 'game_id' => $result['game_id']]);
    } else {
        sendJsonResponse(['success' => false, 'message' => $result['message'] ?? 'Failed to create game'], 500);
    }
}

function handleGetGame($gameId) {
    $game = new Game($gameId);
    $gameData = $game->getGameData();
    
    if ($gameData) {
        sendJsonResponse(['success' => true, 'game' => $gameData]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Game not found'], 404);
    }
}

function handleMakeMove($gameId, $data) {
    if (!isset($data['from']) || !isset($data['to'])) {
        sendJsonResponse(['success' => false, 'message' => 'Move positions are required'], 400);
        return;
    }
    
    $game = new Game($gameId);
    $result = $game->makeMove($_SESSION['user_id'], $data['from'], $data['to'], $data['promotion_piece'] ?? null);
    
    if ($result['success']) {
        sendJsonResponse(['success' => true, 'game' => $result['game']]);
    } else {
        sendJsonResponse(['success' => false, 'message' => $result['message'] ?? 'Invalid move'], 400);
    }
}

function handleUpdateGame($gameId, $data) {
    if (!isset($data['action'])) {
        sendJsonResponse(['success' => false, 'message' => 'Action is required'], 400);
        return;
    }
    
    $game = new Game($gameId);
    
    switch ($data['action']) {
        case 'resign':
            $result = $game->resign($_SESSION['user_id']);
            break;
        case 'draw':
            $result = $game->offerDraw($_SESSION['user_id']);
            break;
        case 'accept_draw':
            $result = $game->acceptDraw($_SESSION['user_id']);
            break;
        default:
            sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
            return;
    }
    
    if ($result['success']) {
        sendJsonResponse(['success' => true, 'game' => $result['game']]);
    } else {
        sendJsonResponse(['success' => false, 'message' => $result['message'] ?? 'Action failed'], 400);
    }
}

function handleGetUsers() {
    $db = Database::getInstance();
    $users = $db->fetchAll("SELECT user_id, username, rating FROM users WHERE user_id != ? ORDER BY username", [$_SESSION['user_id']]);
    sendJsonResponse(['success' => true, 'users' => $users]);
}

// Helper function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
} 