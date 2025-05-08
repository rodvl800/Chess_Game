<?php
require_once __DIR__ . '/../includes/database.php';

class Game {
    private $db;
    private $gameId;
    private $gameData;
    private static $INITIAL_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    
    // Constructor
    public function __construct($gameId = null) {
        $this->db = Database::getInstance();
        
        if ($gameId) {
            $this->gameId = $gameId;
            $this->loadGame();
        }
    }
    
    // Load game data
    private function loadGame() {
        $game = $this->db->fetchOne("SELECT * FROM games WHERE game_id = ?", [$this->gameId]);
        
        if (!$game) {
            return false;
        }
        
        // Load moves
        $game['moves'] = $this->loadMoves();
        
        $this->gameData = $game;
        return true;
    }
    
    // Load all moves for a game
    private function loadMoves() {
        return $this->db->fetchAll(
            "SELECT * FROM game_moves WHERE game_id = ? ORDER BY move_id ASC",
            [$this->gameId]
        );
    }
    
    // Create a new game
    public function createGame($whitePlayerId, $blackPlayerId) {
        $this->db->query(
            "INSERT INTO games (white_player_id, black_player_id, status) VALUES (?, ?, 'waiting')",
            [$whitePlayerId, $blackPlayerId]
        );
        
        $gameId = $this->db->lastInsertId();
        
        if ($gameId) {
            $this->gameId = $gameId;
            $this->loadGame();
            return ['success' => true, 'game_id' => $gameId];
        }
        
        return ['success' => false, 'message' => 'Failed to create game'];
    }
    
    // Start the game
    public function startGame() {
        if (!$this->gameData) {
            return ['success' => false, 'message' => 'Game not loaded'];
        }
        
        if ($this->gameData['status'] !== 'waiting') {
            return ['success' => false, 'message' => 'Game already started or completed'];
        }
        
        $this->db->query(
            "UPDATE games SET status = 'active', start_time = NOW() WHERE game_id = ?",
            [$this->gameId]
        );
        
        $this->loadGame(); // Refresh game data
        return ['success' => true];
    }
    
    // Make a move
    public function makeMove($userId, $fromPosition, $toPosition, $promotionPiece = null) {
        if (!$this->gameData) {
            return ['success' => false, 'message' => 'Game not loaded'];
        }
        
        if ($this->gameData['status'] !== 'active') {
            return ['success' => false, 'message' => 'Game not active'];
        }
        
        // Check if it's the user's turn
        $isWhiteTurn = count($this->gameData['moves']) % 2 === 0;
        $isUsersTurn = ($isWhiteTurn && $userId == $this->gameData['white_player_id']) || 
                      (!$isWhiteTurn && $userId == $this->gameData['black_player_id']);
        
        if (!$isUsersTurn) {
            return ['success' => false, 'message' => 'Not your turn'];
        }
        
        // Get piece type from board position
        // This is simplified - in a real implementation, you'd determine 
        // the piece type from the current board state
        $pieceType = 'P'; // Default to pawn
        
        // Build move notation (e.g., e2e4)
        $moveNotation = $fromPosition . $toPosition;
        
        // Determine move attributes
        $isCapture = false; // Simplified - would check if target square has opponent piece
        $isCheck = false; // Simplified - would check if move puts opponent in check
        $isCheckmate = false; // Simplified - would check if move is checkmate
        $isCastle = false; // Simplified - would check if move is castling
        $isPromotion = $promotionPiece !== null;
        
        // Insert the move
        $this->db->query(
            "INSERT INTO game_moves (game_id, user_id, move_notation, piece_type, from_position, to_position, 
            is_capture, is_check, is_checkmate, is_castle, is_promotion, promotion_piece) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $this->gameId, $userId, $moveNotation, $pieceType, $fromPosition, $toPosition,
                $isCapture, $isCheck, $isCheckmate, $isCastle, $isPromotion, $promotionPiece
            ]
        );
        
        // If checkmate, end the game
        if ($isCheckmate) {
            $this->endGame($userId);
        }
        
        $this->loadGame(); // Refresh game data
        return ['success' => true, 'game' => $this->getGameData()];
    }
    
    // End the game
    public function endGame($winnerId = null) {
        if (!$this->gameData) {
            return ['success' => false, 'message' => 'Game not loaded'];
        }
        
        if ($this->gameData['status'] !== 'active') {
            return ['success' => false, 'message' => 'Game not active'];
        }
        
        $this->db->query(
            "UPDATE games SET status = 'completed', winner_id = ?, end_time = NOW() WHERE game_id = ?",
            [$winnerId, $this->gameId]
        );
        
        // Update player stats
        if ($winnerId) {
            // Winner gets a win
            $this->updateUserStats($winnerId, 'win', 15);
            
            // Loser gets a loss
            $loserId = ($winnerId == $this->gameData['white_player_id']) ? 
                      $this->gameData['black_player_id'] : $this->gameData['white_player_id'];
            $this->updateUserStats($loserId, 'loss', -15);
        } else {
            // Draw
            $this->updateUserStats($this->gameData['white_player_id'], 'draw', 0);
            $this->updateUserStats($this->gameData['black_player_id'], 'draw', 0);
        }
        
        $this->loadGame(); // Refresh game data
        return ['success' => true, 'game' => $this->getGameData()];
    }
    
    // Resign game
    public function resign($userId) {
        if (!$this->gameData) {
            return ['success' => false, 'message' => 'Game not loaded'];
        }
        
        if ($this->gameData['status'] !== 'active') {
            return ['success' => false, 'message' => 'Game not active'];
        }
        
        // Make sure user is part of the game
        if ($userId != $this->gameData['white_player_id'] && $userId != $this->gameData['black_player_id']) {
            return ['success' => false, 'message' => 'User not in this game'];
        }
        
        // The other player wins
        $winnerId = ($userId == $this->gameData['white_player_id']) ? 
                   $this->gameData['black_player_id'] : $this->gameData['white_player_id'];
        
        return $this->endGame($winnerId);
    }
    
    // Offer draw
    public function offerDraw($userId) {
        if (!$this->gameData) {
            return ['success' => false, 'message' => 'Game not loaded'];
        }
        
        if ($this->gameData['status'] !== 'active') {
            return ['success' => false, 'message' => 'Game not active'];
        }
        
        // Make sure user is part of the game
        if ($userId != $this->gameData['white_player_id'] && $userId != $this->gameData['black_player_id']) {
            return ['success' => false, 'message' => 'User not in this game'];
        }
        
        // Set draw offer flag (would need to add this column to the games table)
        $this->db->query(
            "UPDATE games SET draw_offered_by = ? WHERE game_id = ?",
            [$userId, $this->gameId]
        );
        
        $this->loadGame(); // Refresh game data
        return ['success' => true, 'game' => $this->getGameData()];
    }
    
    // Accept draw offer
    public function acceptDraw($userId) {
        if (!$this->gameData) {
            return ['success' => false, 'message' => 'Game not loaded'];
        }
        
        if ($this->gameData['status'] !== 'active') {
            return ['success' => false, 'message' => 'Game not active'];
        }
        
        // Make sure user is part of the game
        if ($userId != $this->gameData['white_player_id'] && $userId != $this->gameData['black_player_id']) {
            return ['success' => false, 'message' => 'User not in this game'];
        }
        
        // Check if draw was offered by the other player
        $drawOfferedBy = isset($this->gameData['draw_offered_by']) ? $this->gameData['draw_offered_by'] : null;
        
        if (!$drawOfferedBy || $drawOfferedBy == $userId) {
            return ['success' => false, 'message' => 'No draw offer to accept'];
        }
        
        // End game with no winner (draw)
        return $this->endGame(null);
    }
    
    // Get game data with player information
    public function getGameData() {
        if (!$this->gameData) {
            return null;
        }
        
        // Get player info
        $whitePlayer = $this->db->fetchOne(
            "SELECT user_id, username, rating FROM users WHERE user_id = ?", 
            [$this->gameData['white_player_id']]
        );
        
        $blackPlayer = $this->db->fetchOne(
            "SELECT user_id, username, rating FROM users WHERE user_id = ?", 
            [$this->gameData['black_player_id']]
        );
        
        // Calculate current turn
        $currentTurn = count($this->gameData['moves']) % 2 === 0 ? 'white' : 'black';
        
        return [
            'game_id' => $this->gameData['game_id'],
            'status' => $this->gameData['status'],
            'current_turn' => $currentTurn,
            'white_player' => [
                'id' => $this->gameData['white_player_id'],
                'username' => $whitePlayer['username'],
                'rating' => $whitePlayer['rating']
            ],
            'black_player' => [
                'id' => $this->gameData['black_player_id'],
                'username' => $blackPlayer['username'],
                'rating' => $blackPlayer['rating']
            ],
            'moves' => $this->gameData['moves'],
            'winner_id' => $this->gameData['winner_id'] ?? null,
            'draw_offered_by' => $this->gameData['draw_offered_by'] ?? null
        ];
    }
    
    // Update user stats
    private function updateUserStats($userId, $result, $ratingChange) {
        // Update games played and rating
        $this->db->query(
            "UPDATE users SET games_played = games_played + 1, rating = rating + ? WHERE user_id = ?",
            [$ratingChange, $userId]
        );
        
        // Update specific stat counter
        $field = '';
        switch ($result) {
            case 'win':
                $field = 'wins';
                break;
            case 'loss': 
                $field = 'losses';
                break;
            case 'draw':
                $field = 'draws';
                break;
        }
        
        if ($field) {
            $this->db->query("UPDATE users SET $field = $field + 1 WHERE user_id = ?", [$userId]);
        }
    }
    
    // Get current FEN notation (simplified implementation)
    public function getCurrentFen() {
        // In a real implementation, you would reconstruct the board from moves
        // and return the actual FEN notation
        return self::$INITIAL_FEN;
    }
} 