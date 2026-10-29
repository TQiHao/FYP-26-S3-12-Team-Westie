<?php

class Database
{
    private $host = 'localhost:3307';
    private $dbname = 'unibee';
    private $username = 'root';
    private $password = '';
    private $pdo;

    public function connect()
    {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname}",
                $this->username,
                $this->password
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->pdo;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConfig()
    {
        $parts = explode(':', $this->host, 2);
        $host = $parts[0] === 'localhost' ? '127.0.0.1' : $parts[0];

        return [
            'host'     => $host,
            'port'     => $parts[1] ?? '3306',
            'database' => $this->dbname,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}