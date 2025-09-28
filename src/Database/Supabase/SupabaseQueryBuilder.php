<?php

namespace DatabaseLibrary\Database\Supabase;

use DatabaseLibrary\Database\QueryBuilder;

class SupabaseQueryBuilder extends QueryBuilder
{
    /**
     * Build SELECT query with Supabase-specific filters
     */
    public function buildSelectWithSupabaseFilters(): string
    {
        $query = $this->buildSelect();
        
        // Add Supabase-specific filters if needed
        // For example, RLS (Row Level Security) filters
        
        return $query;
    }

    /**
     * Build INSERT query with RETURNING clause for Supabase
     */
    public function buildInsertWithReturning(array $data, array $returningColumns = ['*']): string
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
        
        $query = "INSERT INTO {$this->table} (" . implode(', ', $sanitizedColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        // Add RETURNING clause
        $sanitizedReturning = [];
        foreach ($returningColumns as $column) {
            if ($column === '*') {
                $sanitizedReturning[] = '*';
            } else {
                $sanitizedReturning[] = $this->sanitizeColumnName($column);
            }
        }
        $query .= " RETURNING " . implode(', ', $sanitizedReturning);
        
        return $query;
    }

    /**
     * Build UPDATE query with RETURNING clause for Supabase
     */
    public function buildUpdateWithReturning(array $data, array $returningColumns = ['*']): string
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

        // Add RETURNING clause
        $sanitizedReturning = [];
        foreach ($returningColumns as $column) {
            if ($column === '*') {
                $sanitizedReturning[] = '*';
            } else {
                $sanitizedReturning[] = $this->sanitizeColumnName($column);
            }
        }
        $query .= " RETURNING " . implode(', ', $sanitizedReturning);

        return $query;
    }

    /**
     * Build DELETE query with RETURNING clause for Supabase
     */
    public function buildDeleteWithReturning(array $returningColumns = ['*']): string
    {
        $query = "DELETE FROM {$this->table}";

        if (!empty($this->where)) {
            $query .= " WHERE " . implode(' AND ', $this->where);
        }

        // Add RETURNING clause
        $sanitizedReturning = [];
        foreach ($returningColumns as $column) {
            if ($column === '*') {
                $sanitizedReturning[] = '*';
            } else {
                $sanitizedReturning[] = $this->sanitizeColumnName($column);
            }
        }
        $query .= " RETURNING " . implode(', ', $sanitizedReturning);

        return $query;
    }

    /**
     * Add full-text search filter for Supabase
     */
    public function fullTextSearch(string $column, string $searchTerm): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $paramName = ':' . $sanitizedColumn . '_fts_' . count($this->params);
        
        // PostgreSQL full-text search with Spanish language
        $this->where[] = "to_tsvector('spanish', {$sanitizedColumn}) @@ plainto_tsquery('spanish', {$paramName})";
        $this->params[$paramName] = $searchTerm;
        
        return $this;
    }

    /**
     * Add geographic proximity filter for Supabase
     */
    public function withinRadius(string $latColumn, string $lngColumn, float $lat, float $lng, float $radiusKm): self
    {
        $sanitizedLatColumn = $this->sanitizeColumnName($latColumn);
        $sanitizedLngColumn = $this->sanitizeColumnName($lngColumn);
        
        $latParam = ':lat_' . count($this->params);
        $lngParam = ':lng_' . count($this->params);
        
        // PostgreSQL geographic distance calculation
        $this->where[] = "ST_DWithin(
            ST_Point({$sanitizedLngColumn}, {$sanitizedLatColumn})::geography,
            ST_Point({$lngParam}, {$latParam})::geography,
            {$radiusKm} * 1000
        )";
        
        $this->params[$latParam] = $lat;
        $this->params[$lngParam] = $lng;
        
        return $this;
    }

    /**
     * Add JSON field filter for Supabase
     */
    public function whereJsonContains(string $column, string $key, $value): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $paramName = ':' . $sanitizedColumn . '_json_' . count($this->params);
        
        $this->where[] = "{$sanitizedColumn}->>'{$key}' = {$paramName}";
        $this->params[$paramName] = $value;
        
        return $this;
    }

    /**
     * Add JSON array contains filter for Supabase
     */
    public function whereJsonArrayContains(string $column, $value): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $paramName = ':' . $sanitizedColumn . '_json_array_' . count($this->params);
        
        $this->where[] = "{$sanitizedColumn} @> {$paramName}";
        $this->params[$paramName] = json_encode($value);
        
        return $this;
    }

    /**
     * Add case-insensitive search filter for Supabase
     */
    public function whereILike(string $column, string $pattern): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $paramName = ':' . $sanitizedColumn . '_ilike_' . count($this->params);
        
        $this->where[] = "{$sanitizedColumn} ILIKE {$paramName}";
        $this->params[$paramName] = $pattern;
        
        return $this;
    }

    /**
     * Add array overlap filter for Supabase
     */
    public function whereArrayOverlaps(string $column, array $values): self
    {
        $sanitizedColumn = $this->sanitizeColumnName($column);
        $paramName = ':' . $sanitizedColumn . '_overlap_' . count($this->params);
        
        $this->where[] = "{$sanitizedColumn} && {$paramName}";
        $this->params[$paramName] = '{' . implode(',', array_map('json_encode', $values)) . '}';
        
        return $this;
    }
}
