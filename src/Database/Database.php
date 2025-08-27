<?php
// src/Database/Database.php - Version corrigée

namespace src\Database;

use PDO;
use PDOException;

class Database
{
    private static $instance;
    private $connection;

    private function __construct() {
        $config = include __DIR__ . '/../../config/database.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function query($sql, array $params = [])
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public function fetch($sql, array $params = [])
    {
        $statement = $this->query($sql, $params);
        return $statement->fetch();
    }

    public function fetchAll($sql, array $params = [])
    {
        $statement = $this->query($sql, $params);
        return $statement->fetchAll();
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }
}