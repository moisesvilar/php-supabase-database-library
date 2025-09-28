<?php

namespace DatabaseLibrary;

use DatabaseLibrary\Database\ConnectionInterface;
use DatabaseLibrary\Database\Supabase\SupabaseConnection;
use DatabaseLibrary\Database\Supabase\SupabaseQueryBuilder;
use DatabaseLibrary\Database\QueryBuilder;
use DatabaseLibrary\Utils\Config;
use DatabaseLibrary\Utils\Logger;

class DatabaseManager
{
    private $connection;
    private $logger;
    private $config;

    public function __construct(ConnectionInterface $connection, ?Logger $logger = null)
    {
        $this->connection = $connection;
        $this->logger = $logger ?? new Logger();
        $this->config = new Config();
    }

    public static function createSupabaseConnection(
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
        array $options = []
    ): self {
        $connection = new SupabaseConnection($host, $port, $database, $username, $password, $options);
        return new self($connection);
    }

    public static function createSupabaseFromUrl(string $supabaseUrl, string $password): self
    {
        $config = Config::fromSupabaseUrl($supabaseUrl, $password);
        $connection = new SupabaseConnection(
            $config->get('host'),
            $config->get('port'),
            $config->get('database'),
            $config->get('username'),
            $config->get('password'),
            $config->getSupabaseOptions()
        );
        return new self($connection);
    }

    public static function createSupabaseFromConfig(Config $config): self
    {
        $connection = new SupabaseConnection(
            $config->get('host'),
            $config->get('port'),
            $config->get('database'),
            $config->get('username'),
            $config->get('password'),
            $config->getSupabaseOptions()
        );
        return new self($connection);
    }

    public static function createFromEnvironment(): self
    {
        $config = Config::fromEnvironment();
        return self::createSupabaseFromConfig($config);
    }

    public function connect(): bool
    {
        try {
            $startTime = microtime(true);
            $result = $this->connection->connect();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->logger->logConnection('established', [
                'execution_time' => $executionTime . 'ms',
                'host' => $this->config->get('host', 'unknown'),
                'database' => $this->config->get('database', 'unknown')
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Connection failed', $e, [
                'host' => $this->config->get('host', 'unknown'),
                'database' => $this->config->get('database', 'unknown')
            ]);
            throw $e;
        }
    }

    public function disconnect(): bool
    {
        try {
            $result = $this->connection->disconnect();
            $this->logger->logConnection('closed');
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Disconnection failed', $e);
            throw $e;
        }
    }

    public function queryBuilder(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }

    public function supabaseQueryBuilder(string $table): SupabaseQueryBuilder
    {
        return new SupabaseQueryBuilder($table);
    }

    public function executeQuery(string $query, array $params = []): array
    {
        try {
            $startTime = microtime(true);
            $result = $this->connection->read($query, $params);
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->logger->logQuery($query, $params, $executionTime);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Query execution failed', $e, [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    public function executeInsert(string $query, array $params = []): int
    {
        try {
            $startTime = microtime(true);
            $result = $this->connection->write($query, $params);
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->logger->logQuery($query, $params, $executionTime);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Insert execution failed', $e, [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    public function executeUpdate(string $query, array $params = []): int
    {
        try {
            $startTime = microtime(true);
            $result = $this->connection->update($query, $params);
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->logger->logQuery($query, $params, $executionTime);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Update execution failed', $e, [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    public function executeDelete(string $query, array $params = []): int
    {
        try {
            $startTime = microtime(true);
            $result = $this->connection->delete($query, $params);
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->logger->logQuery($query, $params, $executionTime);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Delete execution failed', $e, [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    public function executeProcedure(string $procedureName, array $params = []): array
    {
        try {
            $startTime = microtime(true);
            $result = $this->connection->callProcedure($procedureName, $params);
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->logger->logQuery("CALL {$procedureName}", $params, $executionTime);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Procedure execution failed', $e, [
                'procedure' => $procedureName,
                'params' => $params
            ]);
            throw $e;
        }
    }

    public function beginTransaction(): bool
    {
        try {
            $result = $this->connection->beginTransaction();
            $this->logger->logTransaction('started');
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Transaction start failed', $e);
            throw $e;
        }
    }

    public function commit(): bool
    {
        try {
            $result = $this->connection->commit();
            $this->logger->logTransaction('committed');
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Transaction commit failed', $e);
            throw $e;
        }
    }

    public function rollback(): bool
    {
        try {
            $result = $this->connection->rollback();
            $this->logger->logTransaction('rolled back');
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Transaction rollback failed', $e);
            throw $e;
        }
    }

    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    public function getLastInsertId(): string
    {
        return $this->connection->getLastInsertId();
    }

    public function getAffectedRows(): int
    {
        return $this->connection->getAffectedRows();
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function setConfig(Config $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function healthCheck(): array
    {
        $health = [
            'connected' => $this->isConnected(),
            'in_transaction' => $this->inTransaction(),
            'log_enabled' => $this->logger->isEnabled(),
            'log_level' => $this->logger->getLogLevel(),
            'log_file' => $this->logger->getLogFile(),
            'log_size' => $this->logger->getLogSize(),
        ];

        if ($this->isConnected()) {
            try {
                $startTime = microtime(true);
                $this->connection->read('SELECT 1', []);
                $health['ping_time'] = (microtime(true) - $startTime) * 1000;
            } catch (\Exception $e) {
                $health['ping_error'] = $e->getMessage();
            }
        }

        return $health;
    }

    public function __destruct()
    {
        if ($this->isConnected()) {
            $this->disconnect();
        }
    }
}
