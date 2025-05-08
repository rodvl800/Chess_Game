<?php
require_once __DIR__ . '/../includes/database.php';

class User {
    private $db;
    private $id;
    private $username;
    private $email;
    private $rating;
    private $wins;
    private $losses;
    private $draws;
    
    // Constructor
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        
        if ($userId) {
            $this->loadUser($userId);
        }
    }
    
    // Load user data from ID
    private function loadUser($userId) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);
        
        if ($user) {
            $this->id = $user['user_id'];
            $this->username = $user['username'];
            $this->email = $user['email'];
            $this->rating = $user['rating'];
            $this->wins = $user['wins'];
            $this->losses = $user['losses'];
            $this->draws = $user['draws'];
            return true;
        }
        
        return false;
    }
    
    // Register new user
    public function register($username, $email, $password) {
        // Check if username or email already exists
        $existing = $this->db->fetchOne("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $email]);
        
        if ($existing) {
            if ($existing['username'] === $username) {
                return ['success' => false, 'message' => 'Username already exists'];
            } else {
                return ['success' => false, 'message' => 'Email already exists'];
            }
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $this->db->query(
            "INSERT INTO users (username, email, password) VALUES (?, ?, ?)",
            [$username, $email, $hashedPassword]
        );
        
        $userId = $this->db->lastInsertId();
        
        if ($userId) {
            $this->loadUser($userId);
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Failed to create user'];
    }
    
    // Login user
    public function login($username, $password) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Update last login time
        $this->db->query("UPDATE users SET last_login = NOW() WHERE user_id = ?", [$user['user_id']]);
        
        // Load user data
        $this->loadUser($user['user_id']);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        
        return ['success' => true, 'user_id' => $user['user_id']];
    }
    
    // Logout user
    public function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        session_destroy();
        return true;
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Get current user ID
    public static function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    // Update user profile
    public function updateProfile($data) {
        if (!$this->id) {
            return ['success' => false, 'message' => 'User not loaded'];
        }
        
        $fields = [];
        $params = [];
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
            $this->email = $data['email'];
        }
        
        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }
        
        $params[] = $this->id;
        
        $this->db->query(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?",
            $params
        );
        
        return ['success' => true];
    }
    
    // Update user stats after game
    public function updateStats($result, $ratingChange = 0) {
        if (!$this->id) {
            return false;
        }
        
        // Update user rating
        $this->db->query(
            "UPDATE users SET rating = rating + ? WHERE user_id = ?",
            [$ratingChange, $this->id]
        );
        
        // Update appropriate counter based on result
        switch ($result) {
            case 'win':
                $this->db->query("UPDATE users SET wins = wins + 1 WHERE user_id = ?", [$this->id]);
                $this->wins += 1;
                break;
            case 'loss':
                $this->db->query("UPDATE users SET losses = losses + 1 WHERE user_id = ?", [$this->id]);
                $this->losses += 1;
                break;
            case 'draw':
                $this->db->query("UPDATE users SET draws = draws + 1 WHERE user_id = ?", [$this->id]);
                $this->draws += 1;
                break;
            default:
                return false;
        }
        
        // Update local rating property
        $this->rating += $ratingChange;
        
        return true;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getRating() {
        return $this->rating;
    }
    
    public function getStats() {
        return [
            'wins' => $this->wins,
            'losses' => $this->losses,
            'draws' => $this->draws,
            'rating' => $this->rating
        ];
    }
} 