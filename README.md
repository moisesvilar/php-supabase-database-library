# Database Library - PHP 8.4

A vanilla PHP library for SQL database management with Supabase implementation. This library provides a clean interface for database operations, complete transaction support, stored procedure execution, and advanced query building capabilities.

## Features

- ✅ **Clean Interface** - Simple and intuitive API for CRUD operations
- ✅ **Complete Transaction Support** - Begin, commit, and rollback transactions
- ✅ **Stored Procedure Execution** - Execute stored procedures with parameters
- ✅ **Advanced Query Builder** - Fluent interface for building complex queries
- ✅ **Supabase-Specific Features** - Full-text search, geographic filters, JSON operations
- ✅ **Security First** - SQL injection protection with prepared statements
- ✅ **Integrated Logging** - Automatic query logging with execution times
- ✅ **Flexible Configuration** - Multiple configuration methods
- ✅ **PHP 8.4+ Compatible** - Modern PHP features and syntax

## Installation

```bash
composer install
```

## Quick Start

### Basic Connection

```php
use DatabaseLibrary\DatabaseManager;

// Create Supabase connection
$db = DatabaseManager::createSupabaseConnection(
    'your-supabase-host',
    '5432',
    'postgres',
    'postgres',
    'your-password'
);

// Connect
$db->connect();

// Execute query
$users = $db->executeQuery('SELECT * FROM users WHERE active = ?', [true]);

// Disconnect
$db->disconnect();
```

### From Supabase URL

```php
$supabaseUrl = 'postgresql://postgres:[password]@db.example.supabase.co:5432/postgres';
$db = DatabaseManager::createSupabaseFromUrl($supabaseUrl, 'your-password');
$db->connect();
```

### From Environment Variables

```php
// Set environment variables
$_ENV['DB_HOST'] = 'your-host';
$_ENV['DB_PORT'] = '5432';
$_ENV['DB_DATABASE'] = 'postgres';
$_ENV['DB_USERNAME'] = 'postgres';
$_ENV['DB_PASSWORD'] = 'your-password';

$db = DatabaseManager::createFromEnvironment();
$db->connect();
```

## CRUD Operations

### Read (SELECT)

```php
// Basic query
$users = $db->executeQuery('SELECT * FROM users WHERE active = ?', [true]);

// With Query Builder
$queryBuilder = $db->queryBuilder('users');
$query = $queryBuilder
    ->select(['id', 'name', 'email'])
    ->where('active', '=', true)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->buildSelect();

$users = $db->executeQuery($query, $queryBuilder->getParams());
```

### Create (INSERT)

```php
// Basic insert
$userId = $db->executeInsert(
    'INSERT INTO users (name, email) VALUES (?, ?)',
    ['John Doe', 'john@example.com']
);

// With Query Builder
$queryBuilder = $db->queryBuilder('users');
$query = $queryBuilder->buildInsert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'active' => true
]);

$userId = $db->executeInsert($query, $queryBuilder->getParams());
```

### Update (UPDATE)

```php
// Basic update
$affected = $db->executeUpdate(
    'UPDATE users SET name = ? WHERE id = ?',
    ['John Smith', 123]
);

// With Query Builder
$queryBuilder = $db->queryBuilder('users');
$query = $queryBuilder
    ->where('id', '=', 123)
    ->buildUpdate(['name' => 'John Smith']);

$affected = $db->executeUpdate($query, $queryBuilder->getParams());
```

### Delete (DELETE)

```php
// Basic delete
$deleted = $db->executeDelete('DELETE FROM users WHERE id = ?', [123]);

// With Query Builder
$queryBuilder = $db->queryBuilder('users');
$query = $queryBuilder
    ->where('id', '=', 123)
    ->buildDelete();

$deleted = $db->executeDelete($query, $queryBuilder->getParams());
```

## Supabase-Specific Features

### Full-Text Search

```php
$supabaseBuilder = $db->supabaseQueryBuilder('products');
$query = $supabaseBuilder
    ->select(['*'])
    ->fullTextSearch('description', 'laptop gaming')
    ->buildSelectWithSupabaseFilters();

$products = $db->executeQuery($query, $supabaseBuilder->getParams());
```

### Geographic Proximity Search

```php
$supabaseBuilder = $db->supabaseQueryBuilder('locations');
$query = $supabaseBuilder
    ->select(['*'])
    ->withinRadius('lat', 'lng', 40.4168, -3.7038, 10) // Madrid, 10km radius
    ->buildSelectWithSupabaseFilters();

$locations = $db->executeQuery($query, $supabaseBuilder->getParams());
```

### JSON Field Operations

```php
// Search in JSON fields
$supabaseBuilder = $db->supabaseQueryBuilder('products');
$query = $supabaseBuilder
    ->select(['*'])
    ->whereJsonContains('metadata', 'category', 'electronics')
    ->buildSelectWithSupabaseFilters();

$products = $db->executeQuery($query, $supabaseBuilder->getParams());

// Search in JSON arrays
$query = $supabaseBuilder
    ->whereJsonArrayContains('tags', ['electronics', 'gaming'])
    ->buildSelectWithSupabaseFilters();
```

### Case-Insensitive Search

```php
$supabaseBuilder = $db->supabaseQueryBuilder('users');
$query = $supabaseBuilder
    ->select(['*'])
    ->whereILike('name', '%juan%')
    ->buildSelectWithSupabaseFilters();

$users = $db->executeQuery($query, $supabaseBuilder->getParams());
```

### Array Operations

```php
$supabaseBuilder = $db->supabaseQueryBuilder('products');
$query = $supabaseBuilder
    ->select(['*'])
    ->whereArrayOverlaps('tags', ['electronics', 'gaming'])
    ->buildSelectWithSupabaseFilters();

$products = $db->executeQuery($query, $supabaseBuilder->getParams());
```

## Transactions

```php
$db->beginTransaction();

try {
    // Insert user
    $insertQuery = $db->queryBuilder('users')
        ->buildInsert(['name' => 'John', 'email' => 'john@example.com']);
    
    $db->executeInsert($insertQuery, $db->queryBuilder('users')->getParams());
    
    // Insert profile
    $profileQuery = $db->queryBuilder('profiles')
        ->buildInsert(['user_id' => $db->getLastInsertId(), 'bio' => 'Developer']);
    
    $db->executeInsert($profileQuery, $db->queryBuilder('profiles')->getParams());
    
    $db->commit();
    echo "Transaction completed successfully";
    
} catch (\Exception $e) {
    $db->rollback();
    echo "Transaction rolled back: " . $e->getMessage();
}
```

## Stored Procedures

```php
// Execute stored procedure
$result = $db->executeProcedure('get_user_stats', [123]);

// With parameters
$result = $db->executeProcedure('calculate_total', [100, 0.15, 'USD']);
```

## Advanced Query Builder

### Complex Queries

```php
$queryBuilder = $db->queryBuilder('users');
$query = $queryBuilder
    ->select(['u.id', 'u.name', 'p.bio'])
    ->join('profiles p', 'u.id = p.user_id', 'LEFT')
    ->where('u.active', '=', true)
    ->whereIn('u.role', ['admin', 'user'])
    ->groupBy('u.id')
    ->orderBy('u.created_at', 'DESC')
    ->limit(20)
    ->offset(0)
    ->buildSelect();

$users = $db->executeQuery($query, $queryBuilder->getParams());
```

### Supabase Query Builder with RETURNING

```php
// INSERT with RETURNING
$supabaseBuilder = $db->supabaseQueryBuilder('users');
$query = $supabaseBuilder->buildInsertWithReturning(
    ['name' => 'John', 'email' => 'john@example.com'],
    ['id', 'created_at']
);

$result = $db->getConnection()->execute($query, $supabaseBuilder->getParams());
$userData = $result->fetchAll();

// UPDATE with RETURNING
$query = $supabaseBuilder
    ->where('id', '=', 123)
    ->buildUpdateWithReturning(['name' => 'John Updated'], ['id', 'name', 'updated_at']);

$result = $db->getConnection()->execute($query, $supabaseBuilder->getParams());
```

## Configuration

### Custom Configuration

```php
use DatabaseLibrary\Utils\Config;

$config = new Config([
    'host' => 'localhost',
    'port' => '5432',
    'database' => 'myapp',
    'username' => 'myuser',
    'password' => 'mypass',
    'ssl_mode' => 'require',
    'timeout' => 60,
    'application_name' => 'MyApp'
]);

$db = DatabaseManager::createSupabaseFromConfig($config);
```

### Custom Logger

```php
use DatabaseLibrary\Utils\Logger;

$logger = new Logger(
    'custom.log',    // Log file
    true,           // Enabled
    'DEBUG',        // Log level
    5242880,        // Max file size (5MB)
    3               // Max files
);

$db = DatabaseManager::createSupabaseConnection(
    'host', 'port', 'db', 'user', 'pass'
)->setLogger($logger);
```

## Logging

The library automatically logs all database operations:

```php
// Check log information
$logger = $db->getLogger();
echo "Log enabled: " . ($logger->isEnabled() ? 'true' : 'false') . "\n";
echo "Log level: " . $logger->getLogLevel() . "\n";
echo "Log file: " . $logger->getLogFile() . "\n";
echo "Log size: " . $logger->getLogSize() . " bytes\n";

// Get recent log entries
$recentLogs = $logger->getLogContents(10);
foreach ($recentLogs as $logEntry) {
    echo trim($logEntry) . "\n";
}
```

## Health Check

```php
$health = $db->healthCheck();
echo "Connected: " . ($health['connected'] ? 'true' : 'false') . "\n";
echo "In transaction: " . ($health['in_transaction'] ? 'true' : 'false') . "\n";
echo "Ping time: " . $health['ping_time'] . "ms\n";
```

## Security Features

### SQL Injection Protection

The library uses prepared statements and input validation:

```php
// ✅ Safe - uses prepared statements
$queryBuilder = $db->queryBuilder('users');
$query = $queryBuilder
    ->where('name', '=', $userInput)  // Automatically sanitized
    ->buildSelect();

// ✅ Safe - parameterized queries
$users = $db->executeQuery('SELECT * FROM users WHERE name = ?', [$userInput]);

// ❌ Never do this - direct string concatenation
$unsafeQuery = "SELECT * FROM users WHERE name = '{$userInput}'";
```

### Input Validation

All identifiers (table names, column names) are automatically sanitized:

```php
// These will throw InvalidArgumentException for invalid input
$queryBuilder = $db->queryBuilder("users; DROP TABLE users; --"); // ❌ Invalid
$queryBuilder->where("id; DROP TABLE users; --", "=", 1); // ❌ Invalid
```

## API Reference

### DatabaseManager

#### Factory Methods

- `createSupabaseConnection($host, $port, $database, $username, $password, $options = [])`
- `createSupabaseFromUrl($supabaseUrl, $password)`
- `createSupabaseFromConfig(Config $config)`
- `createFromEnvironment()`

#### Connection Methods

- `connect(): bool` - Connect to database
- `disconnect(): bool` - Disconnect from database
- `isConnected(): bool` - Check connection status

#### Query Execution

- `executeQuery($query, $params = []): array` - Execute SELECT query
- `executeInsert($query, $params = []): int` - Execute INSERT query
- `executeUpdate($query, $params = []): int` - Execute UPDATE query
- `executeDelete($query, $params = []): int` - Execute DELETE query
- `executeProcedure($procedureName, $params = []): array` - Execute stored procedure

#### Transaction Methods

- `beginTransaction(): bool` - Start transaction
- `commit(): bool` - Commit transaction
- `rollback(): bool` - Rollback transaction
- `inTransaction(): bool` - Check transaction status

#### Utility Methods

- `getLastInsertId(): string` - Get last insert ID
- `getAffectedRows(): int` - Get affected rows count
- `healthCheck(): array` - Get system health status
- `queryBuilder($table): QueryBuilder` - Get query builder
- `supabaseQueryBuilder($table): SupabaseQueryBuilder` - Get Supabase query builder

### QueryBuilder

#### Selection

- `select($columns): self` - Set columns to select
- `where($column, $operator, $value): self` - Add WHERE condition
- `whereIn($column, $values): self` - Add WHERE IN condition
- `join($table, $condition, $type = 'INNER'): self` - Add JOIN
- `orderBy($column, $direction = 'ASC'): self` - Add ORDER BY
- `groupBy($column): self` - Add GROUP BY
- `limit($limit): self` - Add LIMIT
- `offset($offset): self` - Add OFFSET

#### Query Building

- `buildSelect(): string` - Build SELECT query
- `buildInsert($data): string` - Build INSERT query
- `buildUpdate($data): string` - Build UPDATE query
- `buildDelete(): string` - Build DELETE query
- `getParams(): array` - Get query parameters
- `reset(): self` - Reset builder

### SupabaseQueryBuilder

Extends QueryBuilder with Supabase-specific features:

#### Supabase Features

- `buildSelectWithSupabaseFilters(): string` - Build SELECT with Supabase filters
- `buildInsertWithReturning($data, $returningColumns = ['*']): string` - INSERT with RETURNING
- `buildUpdateWithReturning($data, $returningColumns = ['*']): string` - UPDATE with RETURNING
- `buildDeleteWithReturning($returningColumns = ['*']): string` - DELETE with RETURNING

#### Advanced Search

- `fullTextSearch($column, $searchTerm): self` - Full-text search
- `withinRadius($latColumn, $lngColumn, $lat, $lng, $radiusKm): self` - Geographic search
- `whereJsonContains($column, $key, $value): self` - JSON field search
- `whereJsonArrayContains($column, $value): self` - JSON array search
- `whereILike($column, $pattern): self` - Case-insensitive search
- `whereArrayOverlaps($column, $values): self` - Array overlap search

## Error Handling

The library provides comprehensive error handling:

```php
use DatabaseLibrary\Database\DatabaseException;

try {
    $db->connect();
    $users = $db->executeQuery('SELECT * FROM users');
} catch (DatabaseException $e) {
    echo "Database error: " . $e->getMessage();
} catch (\Exception $e) {
    echo "General error: " . $e->getMessage();
}
```

## Examples

See `example.php` for comprehensive usage examples including:

- Multiple connection methods
- Query builder usage
- Supabase-specific features
- Transaction handling
- Error handling
- Logging configuration
- Health monitoring

## Requirements

- PHP 8.4 or higher
- PDO PostgreSQL extension
- Composer (for autoloading)

## License

MIT License - see LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## Support

For issues and questions, please open an issue on GitHub.
