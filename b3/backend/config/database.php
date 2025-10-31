<?php
class Database {
    private $host = "localhost";
    private $db_name = "b3";
    private $username = "root";
    private $password = "";
    private $conn;
    private $connection_attempts = 0;
    private $max_attempts = 3;
    private $retry_delay = 1; // seconds
    
    public function getConnection() {
        if ($this->conn !== null) {
            // Return existing connection if available
            try {
                $this->conn->query('SELECT 1');
                return $this->conn;
            } catch (PDOException $e) {
                // Connection lost, reset and reconnect
                $this->conn = null;
            }
        }
        
        return $this->createConnection();
    }
    
    private function createConnection() {
        $this->connection_attempts = 0;
        
        while ($this->connection_attempts < $this->max_attempts) {
            try {
                $this->connection_attempts++;
                
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
                // Test connection
                $this->conn->query('SELECT 1');
                
                return $this->conn;
                
            } catch(PDOException $exception) {
                $error_message = "خطا در اتصال به دیتابیس (تلاش {$this->connection_attempts}/{$this->max_attempts}): " . $exception->getMessage();
                
                if ($this->connection_attempts >= $this->max_attempts) {
                    // Log error and return null for fallback handling
                    error_log($error_message);
                    http_response_code(503);
                    echo json_encode([
                        'error' => true,
                        'message' => 'خطا در اتصال به دیتابیس. لطفاً بعداً تلاش کنید.',
                        'fallback_available' => true
                    ]);
                    return null;
                }
                
                // Wait before retry
                sleep($this->retry_delay);
            }
        }
        
        return null;
    }
    
    public function isConnected() {
        try {
            return $this->conn !== null && $this->conn->query('SELECT 1') !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getConnectionStatus() {
        return [
            'connected' => $this->isConnected(),
            'host' => $this->host,
            'database' => $this->db_name,
            'attempts' => $this->connection_attempts
        ];
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
}
?>