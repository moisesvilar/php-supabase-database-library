<?php

namespace DatabaseLibrary\Database;

class QueryBuilder
{
    protected $table;
    protected $select = ['*'];
    protected $where = [];
    protected $join = [];
    protected $orderBy = [];
    protected $groupBy = [];
    protected $limit = null;
    protected $offset = null;
    protected $params = [];

    private static $allowedOperators = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'ILIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'];
    private static $allowedJoinTypes = ['INNER', 'LEFT', 'RIGHT', 'FULL OUTER'];
    private static $allowedOrderDirections = ['ASC', 'DESC'];

    public function __construct(string $table)
    {
        $this->table = $this->sanitizeIdentifier($table);
    }

    private function sanitizeIdentifier(string $identifier): string
    {
        // Remove any characters that aren't alphanumeric, underscore, or dot
        $sanitized = preg_replace('/[^a-zA-Z0-9_\.]/', '', $identifier);
        
        // Ensure it's not empty and doesn't start with a number
        if (empty($sanitized) || is_numeric($sanitized[0])) {
            throw new \InvalidArgumentException("Invalid identifier: {$identifier}");
        }
        
        return $sanitized;
    }

    protected function sanitizeColumnName(string $column): string
    {
        return $this->sanitizeIdentifier($column);
    }

    private function validateOperator(string $operator): string
    {
        if (!in_array(strtoupper($operator), self::$allowedOperators)) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }
        return strtoupper($operator);
    }

    public function select(array $columns): self
    {
        $sanitizedColumns = [];
        foreach ($columns as $column) {
            if ($column === '*') {
                $sanitizedColumns[] = '*';
            } else {
                $sanitizedColumns[] = $this->sanitizeColumnName($column);
            }
        }
        $this->select = $sanitizedColumns;
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $validatedOperator = $this->validateOperator($operator);
        
        $paramName = ':' . $sanitizedColumn . '_' . count($this->params);
        $this->where[] = "{$sanitizedColumn} {$validatedOperator} {$paramName}";
        $this->params[$paramName] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $placeholders = [];
        foreach ($values as $i => $value) {
            $paramName = ':' . $sanitizedColumn . '_in_' . $i;
            $placeholders[] = $paramName;
            $this->params[$paramName] = $value;
        }
        $this->where[] = "{$sanitizedColumn} IN (" . implode(',', $placeholders) . ")";
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $sanitizedTable = $this->sanitizeIdentifier($table);
        $validatedType = strtoupper($type);
        
        if (!in_array($validatedType, self::$allowedJoinTypes)) {
            throw new \InvalidArgumentException("Invalid join type: {$type}");
        }
        
        // Basic validation for condition - should contain column names and operators
        if (!preg_match('/^[a-zA-Z0-9_\.]+\s*[=<>]+\s*[a-zA-Z0-9_\.]+$/', $condition)) {
            throw new \InvalidArgumentException("Invalid join condition: {$condition}");
        }
        
        $this->join[] = "{$validatedType} JOIN {$sanitizedTable} ON {$condition}";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $validatedDirection = strtoupper($direction);
        
        if (!in_array($validatedDirection, self::$allowedOrderDirections)) {
            throw new \InvalidArgumentException("Invalid order direction: {$direction}");
        }
        
        $this->orderBy[] = "{$sanitizedColumn} {$validatedDirection}";
        return $this;
    }

    public function groupBy(string $column): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $this->groupBy[] = $sanitizedColumn;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function buildSelect(): string
    {
        $query = "SELECT " . implode(', ', $this->select) . " FROM {$this->table}";

        if (!empty($this->join)) {
            $query .= " " . implode(' ', $this->join);
        }

        if (!empty($this->where)) {
            $query .= " WHERE " . implode(' AND ', $this->where);
        }

        if (!empty($this->groupBy)) {
            $query .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $query .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $query .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $query .= " OFFSET {$this->offset}";
        }

        return $query;
    }

    public function buildInsert(array $data): string
    {
        $sanitizedColumns = [];
        $placeholders = [];
        
        foreach ($data as $column => $value) {
            $sanitizedColumn = $this->sanitizeColumnName($column);
            $sanitizedColumns[] = $sanitizedColumn;
            $placeholder = ':' . $sanitizedColumn;
            $placeholders[] = $placeholder;
            $this->params[$placeholder] = $value;
        }
        
        return "INSERT INTO {$this->table} (" . implode(', ', $sanitizedColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    }

    public function buildUpdate(array $data): string
    {
        $setClause = [];
        foreach ($data as $column => $value) {
            $sanitizedColumn = $this->sanitizeColumnName($column);
            $paramName = ':' . $sanitizedColumn;
            $setClause[] = "{$sanitizedColumn} = {$paramName}";
            $this->params[$paramName] = $value;
        }

        $query = "UPDATE {$this->table} SET " . implode(', ', $setClause);

        if (!empty($this->where)) {
            $query .= " WHERE " . implode(' AND ', $this->where);
        }

        return $query;
    }

    public function buildDelete(): string
    {
        $query = "DELETE FROM {$this->table}";

        if (!empty($this->where)) {
            $query .= " WHERE " . implode(' AND ', $this->where);
        }

        return $query;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function reset(): self
    {
        $this->select = ['*'];
        $this->where = [];
        $this->join = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->params = [];
        return $this;
    }
}
