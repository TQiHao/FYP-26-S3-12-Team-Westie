<?php

class Database
{
    private $host = 'sql310.infinityfree.com';
    private $dbname = 'if0_43226272_unibee';
    private $username = 'if0_43226272';
    private $password = 'unibee1234';
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
            'host' => $host,
            'port' => $parts[1] ?? '3306',
            'database' => $this->dbname,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}