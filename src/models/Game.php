<?php
require_once __DIR__ . '/../includes/database.php';

// Initial board setup in FEN notation
$INITIAL_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

// Load game by ID
function load_game($gameId) {
    $db = Database::getInstance();
    $game = $db->fetchOne("SELECT * FROM games WHERE game_id = ?", [$gameId]);
    
    if (!$game) {
        return false;
    }
    
    // Load moves
    $game['moves'] = load_moves($gameId);
    
    return $game;
}

// Load all moves for a game
function load_moves($gameId) {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM game_moves WHERE game_id = ? ORDER BY move_id ASC",
        [$gameId]
    );
}

// Create a new game
function create_game($whitePlayerId, $blackPlayerId) {
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO games (white_player_id, black_player_id, status) VALUES (?, ?, 'waiting')",
        [$whitePlayerId, $blackPlayerId]
    );
    
    $gameId = $db->lastInsertId();
    
    if ($gameId) {
        return ['success' => true, 'game_id' => $gameId];
    }
    
    return ['success' => false, 'message' => 'Failed to create game'];
}

// Start the game
function start_game($gameId) {
    $db = Database::getInstance();
    $game = load_game($gameId);
    
    if (!$game) {
        return ['success' => false, 'message' => 'Game not loaded'];
    }
    
    if ($game['status'] !== 'waiting') {
        return ['success' => false, 'message' => 'Game already started or completed'];
    }
    
    $db->query(
        "UPDATE games SET status = 'active', start_time = NOW() WHERE game_id = ?",
        [$gameId]
    );
    
    return ['success' => true];
}

// Make a move
function make_move($gameId, $userId, $moveNotation, $pieceType, $fromPosition, $toPosition, $moveData = []) {
    $db = Database::getInstance();
    $game = load_game($gameId);
    
    if (!$game) {
        return ['success' => false, 'message' => 'Game not loaded'];
    }
    
    if ($game['status'] !== 'active') {
        return ['success' => false, 'message' => 'Game not active'];
    }
    
    // Check if it's the user's turn
    $isWhiteTurn = count($game['moves']) % 2 === 0;
    $isUsersTurn = ($isWhiteTurn && $userId == $game['white_player_id']) || 
                  (!$isWhiteTurn && $userId == $game['black_player_id']);
    
    if (!$isUsersTurn) {
        return ['success' => false, 'message' => 'Not your turn'];
    }
    
    // Insert the move
    $isCapture = isset($moveData['is_capture']) ? $moveData['is_capture'] : false;
    $isCheck = isset($moveData['is_check']) ? $moveData['is_check'] : false;
    $isCheckmate = isset($moveData['is_checkmate']) ? $moveData['is_checkmate'] : false;
    $isCastle = isset($moveData['is_castle']) ? $moveData['is_castle'] : false;
    $isPromotion = isset($moveData['is_promotion']) ? $moveData['is_promotion'] : false;
    $promotionPiece = isset($moveData['promotion_piece']) ? $moveData['promotion_piece'] : null;
    
    $db->query(
        "INSERT INTO game_moves (game_id, user_id, move_notation, piece_type, from_position, to_position, 
        is_capture, is_check, is_checkmate, is_castle, is_promotion, promotion_piece) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $gameId, $userId, $moveNotation, $pieceType, $fromPosition, $toPosition,
            $isCapture, $isCheck, $isCheckmate, $isCastle, $isPromotion, $promotionPiece
        ]
    );
    
    // Check if the game is over
    if ($isCheckmate) {
        end_game($gameId, $userId);
    }
    
    return ['success' => true];
}

// End the game
function end_game($gameId, $winnerId = null) {
    $db = Database::getInstance();
    $game = load_game($gameId);
    
    if (!$game) {
        return ['success' => false, 'message' => 'Game not loaded'];
    }
    
    if ($game['status'] !== 'active') {
        return ['success' => false, 'message' => 'Game not active'];
    }
    
    $db->query(
        "UPDATE games SET status = 'completed', winner_id = ?, end_time = NOW() WHERE game_id = ?",
        [$winnerId, $gameId]
    );
    
    // Update player stats
    if ($winnerId) {
        // Winner gets a win
        update_user_stats($winnerId, 'win', 15);
        
        // Loser gets a loss
        $loserId = ($winnerId == $game['white_player_id']) ? $game['black_player_id'] : $game['white_player_id'];
        update_user_stats($loserId, 'loss', -15);
    } else {
        // Draw
        update_user_stats($game['white_player_id'], 'draw', 0);
        update_user_stats($game['black_player_id'], 'draw', 0);
    }
    
    return ['success' => true];
}

// Update user stats (replacement for the User class functionality)
function update_user_stats($userId, $result, $ratingChange) {
    $db = Database::getInstance();
    
    // Update games played and rating
    $db->query(
        "UPDATE users SET games_played = games_played + 1, rating = rating + ? WHERE user_id = ?",
        [$ratingChange, $userId]
    );
    
    // Update specific stat counter
    if ($result === 'win') {
        $db->query("UPDATE users SET games_won = games_won + 1 WHERE user_id = ?", [$userId]);
    } elseif ($result === 'loss') {
        $db->query("UPDATE users SET games_lost = games_lost + 1 WHERE user_id = ?", [$userId]);
    } elseif ($result === 'draw') {
        $db->query("UPDATE users SET games_drawn = games_drawn + 1 WHERE user_id = ?", [$userId]);
    }
}

// Abandon game
function abandon_game($gameId, $userId) {
    $db = Database::getInstance();
    $game = load_game($gameId);
    
    if (!$game) {
        return ['success' => false, 'message' => 'Game not loaded'];
    }
    
    if ($game['status'] !== 'active' && $game['status'] !== 'waiting') {
        return ['success' => false, 'message' => 'Game already completed'];
    }
    
    // The other player wins
    $winnerId = ($userId == $game['white_player_id']) ? $game['black_player_id'] : $game['white_player_id'];
    
    $db->query(
        "UPDATE games SET status = 'abandoned', winner_id = ?, end_time = NOW() WHERE game_id = ?",
        [$winnerId, $gameId]
    );
    
    // Update player stats
    update_user_stats($winnerId, 'win', 15);
    update_user_stats($userId, 'loss', -15);
    
    return ['success' => true];
}

// Get active games for user
function get_active_games_for_user($userId) {
    $db = Database::getInstance();
    
    return $db->fetchAll(
        "SELECT * FROM games 
        WHERE (white_player_id = ? OR black_player_id = ?) 
        AND (status = 'waiting' OR status = 'active')
        ORDER BY start_time DESC",
        [$userId, $userId]
    );
}

// Get completed games for user
function get_completed_games_for_user($userId, $limit = 10) {
    $db = Database::getInstance();
    
    return $db->fetchAll(
        "SELECT * FROM games 
        WHERE (white_player_id = ? OR black_player_id = ?) 
        AND (status = 'completed' OR status = 'abandoned')
        ORDER BY end_time DESC
        LIMIT ?",
        [$userId, $userId, $limit]
    );
}

// Get game data with player information
function get_game_data($gameId) {
    $db = Database::getInstance();
    $game = load_game($gameId);
    
    if (!$game) {
        return null;
    }
    
    // Get player info
    $whitePlayer = $db->fetchOne("SELECT username, rating FROM users WHERE user_id = ?", [$game['white_player_id']]);
    $blackPlayer = $db->fetchOne("SELECT username, rating FROM users WHERE user_id = ?", [$game['black_player_id']]);
    
    return [
        'game_id' => $game['game_id'],
        'status' => $game['status'],
        'white_player' => [
            'id' => $game['white_player_id'],
            'username' => $whitePlayer['username'],
            'rating' => $whitePlayer['rating']
        ],
        'black_player' => [
            'id' => $game['black_player_id'],
            'username' => $blackPlayer['username'],
            'rating' => $blackPlayer['rating']
        ],
        'winner_id' => $game['winner_id'],
        'start_time' => $game['start_time'],
        'end_time' => $game['end_time'],
        'moves' => $game['moves'],
        'current_turn' => (count($game['moves']) % 2 === 0) ? 'white' : 'black'
    ];
}

// Get FEN representation of the current board state
function get_current_fen($gameId) {
    global $INITIAL_FEN;
    // This is a simplified implementation
    // In a real application, you'd need to calculate the FEN based on moves
    // For now, we'll just return the initial position
    return $INITIAL_FEN;
} 