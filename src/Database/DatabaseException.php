<?php

namespace DatabaseLibrary\Database;

use Exception;

class DatabaseException extends Exception
{
    public function __construct(string $message, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function connectionFailed(string $message): self
    {
        return new self("Connection failed: {$message}");
    }

    public static function queryFailed(string $query, string $message): self
    {
        return new self("Query failed '{$query}': {$message}");
    }

    public static function transactionFailed(string $message): self
    {
        return new self("Transaction failed: {$message}");
    }

    public static function procedureFailed(string $procedure, string $message): self
    {
        return new self("Procedure failed '{$procedure}': {$message}");
    }
}
