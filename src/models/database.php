<?php
require_once 'config.php';

class Database {
    private $connection;
    private static $instance = null;

    // Private constructor - Singleton pattern
    private function __construct() {
        $this->connect();
    }

    // Get singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Connect to database
    private function connect() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database error: " . $e->getMessage());
        }
    }

    // Execute query
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Query preparation failed: " . $this->connection->error);
            }
            
            if (!empty($params)) {
                $types = '';
                $bindParams = [];
                
                // Build types string and reference array
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                    $bindParams[] = $param;
                }
                
                // Create reference array for call_user_func_array
                $bindValues = array_merge([$types], $bindParams);
                $refValues = [];
                
                foreach ($bindValues as $key => $value) {
                    $refValues[$key] = &$bindValues[$key];
                }
                
                call_user_func_array([$stmt, 'bind_param'], $refValues);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Query execution failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $stmt->close();
            
            if ($result) {
                return $result;
            }
            
            return true;
        } catch (Exception $e) {
            die("Query error: " . $e->getMessage());
        }
    }

    // Fetch all records
    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch single record
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result->fetch_assoc();
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    // Escape string
    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }

    // Close connection
    public function close() {
        $this->connection->close();
    }
} 