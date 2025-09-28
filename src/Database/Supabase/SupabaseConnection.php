<?php

namespace DatabaseLibrary\Database\Supabase;

use DatabaseLibrary\Database\ConnectionInterface;
use DatabaseLibrary\Database\DatabaseException;
use DatabaseLibrary\Traits\TransactionTrait;
use PDO;
use PDOStatement;

class SupabaseConnection implements ConnectionInterface
{
    use TransactionTrait;

    private $pdo = null;
    private $host;
    private $port;
    private $database;
    private $username;
    private $password;
    private $options;

    public function __construct(
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
        array $options = []
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options + [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];
    }

    public function connect(): bool
    {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            $this->pdo = new PDO($dsn, $this->username, $this->password, $this->options);
            return true;
        } catch (\PDOException $e) {
            throw DatabaseException::connectionFailed($e->getMessage());
        }
    }

    public function disconnect(): bool
    {
        $this->pdo = null;
        $this->inTransaction = false;
        return true;
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    public function read(string $query, array $params = []): array
    {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }

    public function write(string $query, array $params = []): int
    {
        $stmt = $this->execute($query, $params);
        return $stmt->rowCount();
    }

    public function update(string $query, array $params = []): int
    {
        $stmt = $this->execute($query, $params);
        return $stmt->rowCount();
    }

    public function delete(string $query, array $params = []): int
    {
        $stmt = $this->execute($query, $params);
        return $stmt->rowCount();
    }

    public function execute(string $query, array $params = []): PDOStatement
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("No active connection");
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed($query, $e->getMessage());
        }
    }

    public function callProcedure(string $procedureName, array $params = []): array
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("No active connection");
        }

        try {
            // Build procedure call
            $placeholders = str_repeat('?,', count($params) - 1) . '?';
            $query = "CALL {$procedureName}({$placeholders})";
            
            $stmt = $this->execute($query, $params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw DatabaseException::procedureFailed($procedureName, $e->getMessage());
        }
    }

    public function getLastInsertId(): string
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("No active connection");
        }

        return $this->pdo->lastInsertId();
    }

    public function getAffectedRows(): int
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("No active connection");
        }

        // In PostgreSQL, row count is available through PDOStatement::rowCount()
        // This method is kept for compatibility but should use rowCount() from specific statements
        return 0;
    }
}

