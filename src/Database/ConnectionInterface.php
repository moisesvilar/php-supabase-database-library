<?php

namespace DatabaseLibrary\Database;

use PDO;
use PDOStatement;

interface ConnectionInterface
{
    /**
     * Connect to the database
     */
    public function connect(): bool;

    /**
     * Disconnect from the database
     */
    public function disconnect(): bool;

    /**
     * Check if connected
     */
    public function isConnected(): bool;

    /**
     * Get PDO instance
     */
    public function getPdo(): ?PDO;

    /**
     * Read records (SELECT)
     */
    public function read(string $query, array $params = []): array;

    /**
     * Write record (INSERT)
     */
    public function write(string $query, array $params = []): int;

    /**
     * Update records (UPDATE)
     */
    public function update(string $query, array $params = []): int;

    /**
     * Delete records (DELETE)
     */
    public function delete(string $query, array $params = []): int;

    /**
     * Execute custom query
     */
    public function execute(string $query, array $params = []): PDOStatement;

    /**
     * Execute stored procedure
     */
    public function callProcedure(string $procedureName, array $params = []): array;

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool;

    /**
     * Commit transaction
     */
    public function commit(): bool;

    /**
     * Rollback transaction
     */
    public function rollback(): bool;

    /**
     * Check if in transaction
     */
    public function inTransaction(): bool;

    /**
     * Get last insert ID
     */
    public function getLastInsertId(): string;

    /**
     * Get number of affected rows
     */
    public function getAffectedRows(): int;
}
