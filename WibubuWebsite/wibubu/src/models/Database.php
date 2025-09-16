<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $pdo;

    public function __construct() {
        // Parse DATABASE_URL environment variable
        $database_url = getenv('DATABASE_URL');
        if ($database_url) {
            $url = parse_url($database_url);
            $this->host = $url['host'];
            $this->port = isset($url['port']) ? $url['port'] : '5432';
            $this->db_name = ltrim($url['path'], '/');
            $this->username = $url['user'];
            $this->password = $url['pass'];
        } else {
            // Fallback values
            $this->host = getenv('PGHOST') ?: 'localhost';
            $this->port = getenv('PGPORT') ?: '5432';
            $this->db_name = getenv('PGDATABASE') ?: 'wibubu';
            $this->username = getenv('PGUSER') ?: 'postgres';
            $this->password = getenv('PGPASSWORD') ?: '';
        }
    }

    public function getConnection() {
        $this->pdo = null;
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->pdo = new PDO($dsn, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->pdo;
    }
}