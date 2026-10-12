<?php

class Database
{
    private $host = 'localhost:3306';
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
}
