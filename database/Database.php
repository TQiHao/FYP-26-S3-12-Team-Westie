<?php

class Database
{
    private $host = "localhost";
    private $dbName = "Unibee";
    private $username = "root";
    private $password = "";

    public function connect()
    {
        try {
            $connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4",
                $this->username,
                $this->password
            );

            $connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            return $connection;

        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}