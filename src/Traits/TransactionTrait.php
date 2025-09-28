<?php

namespace DatabaseLibrary\Traits;

use DatabaseLibrary\Database\DatabaseException;

trait TransactionTrait
{
    protected $inTransaction = false;

    public function beginTransaction(): bool
    {
        if ($this->inTransaction) {
            throw new DatabaseException("Transaction already active");
        }

        if (!$this->isConnected()) {
            throw new DatabaseException("No active connection");
        }

        $this->inTransaction = $this->getPdo()->beginTransaction();
        return $this->inTransaction;
    }

    public function commit(): bool
    {
        if (!$this->inTransaction) {
            throw new DatabaseException("No active transaction to commit");
        }

        $result = $this->getPdo()->commit();
        $this->inTransaction = false;
        return $result;
    }

    public function rollback(): bool
    {
        if (!$this->inTransaction) {
            throw new DatabaseException("No active transaction to rollback");
        }

        $result = $this->getPdo()->rollBack();
        $this->inTransaction = false;
        return $result;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }
}
