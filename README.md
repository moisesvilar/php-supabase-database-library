# Database Library - PHP 8.4

A vanilla PHP library for SQL database management with Supabase implementation. This library provides a clean interface for database operations, complete transaction support, stored procedure execution, and advanced query building capabilities.

## Features

- ✅ **Clean Interface** - Simple and intuitive API for CRUD operations
- ✅ **Complete Transaction Support** - Begin, commit, and rollback transactions
- ✅ **Stored Procedure Execution** - Execute stored procedures with parameters
- ✅ **Advanced Query Builder** - Fluent interface for building complex queries
- ✅ **Supabase-Specific Features** - Full-text search, geographic filters, JSON operations
- ✅ **Security First** - SQL injection protection with prepared statements
- ✅ **Environment Variables** - Secure credential management
- ✅ **Integrated Logging** - Automatic query logging with execution times
- ✅ **Flexible Configuration** - Multiple configuration methods
- ✅ **PHP 8.4+ Compatible** - Modern PHP features and syntax

## Installation

### Step 1: Install Dependencies

```bash
composer install
```

### Step 2: Configure Environment

```bash
# Copy environment template
cp env.example .env

# Edit with your Supabase credentials
nano .env
```

### Step 3: Set Your Supabase Credentials

Edit `.env` file with your real credentials:

```env
SUPABASE_HOST=db.your-project-ref.supabase.co
SUPABASE_PASSWORD=your-database-password
SUPABASE_URL=postgresql://postgres:your-password@db.your-project-ref.supabase.co:5432/postgres
```

### Step 4: Test Installation

```bash
php example_supabase.php
```

📖 **For detailed installation instructions, see [INSTALLATION.md](INSTALLATION.md)**

## Quick Start

### Basic Connection with Environment Variables

```php
<?php
require_once 'vendor/autoload.php';

use DatabaseLibrary\DatabaseManager;
use DatabaseLibrary\Utils\EnvLoader;

// Load environment variables
EnvLoader::load();

// Create connection from environment
$db = DatabaseManager::createSupabaseFromUrl(
    EnvLoader::required('SUPABASE_URL'),
    EnvLoader::required('SUPABASE_PASSWORD')
);

// Connect and use
$db->connect();
$users = $db->executeQuery('SELECT * FROM users WHERE active = ?', [true]);
$db->disconnect();
```

### Alternative Connection Methods

```php
// Method 1: Direct connection
$db = DatabaseManager::createSupabaseConnection(
    EnvLoader::get('SUPABASE_HOST'),
    EnvLoader::get('SUPABASE_PORT', '5432'),
    EnvLoader::get('SUPABASE_DATABASE', 'postgres'),
    EnvLoader::get('SUPABASE_USERNAME', 'postgres'),
    EnvLoader::required('SUPABASE_PASSWORD')
);

// Method 2: From configuration file
$config = require 'config.php'; // Uses environment variables internally
$db = DatabaseManager::createSupabaseFromConfig(Config::fromArray($config['supabase']));

// Method 3: From system environment
$db = DatabaseManager::createFromEnvironment();
```

## CRUD Operations

### Read (SELECT)

```php
// Basic query with environment-based connection
$db = DatabaseManager::createSupabaseFromUrl(
    EnvLoader::required('SUPABASE_URL'),
    EnvLoader::required('SUPABASE_PASSWORD')
);
$db->connect();

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

## Environment Variables

### Required Variables

```env
# Required for connection
SUPABASE_HOST=db.your-project-ref.supabase.co
SUPABASE_PASSWORD=your-database-password
SUPABASE_URL=postgresql://postgres:your-password@db.your-project-ref.supabase.co:5432/postgres
```

### Optional Variables

```env
# Database configuration
SUPABASE_PORT=5432
SUPABASE_DATABASE=postgres
SUPABASE_USERNAME=postgres
SUPABASE_SSL_MODE=require

# Connection timeouts
SUPABASE_TIMEOUT=30
SUPABASE_CONNECT_TIMEOUT=10

# Logging configuration
LOG_ENABLED=true
LOG_LEVEL=INFO
LOG_FILE=database.log
LOG_MAX_SIZE=10485760
LOG_MAX_FILES=5

# Application settings
APP_NAME=DatabaseLibrary
APP_ENV=development
```

### Environment Helper Methods

```php
use DatabaseLibrary\Utils\EnvLoader;

// Load environment variables
EnvLoader::load();

// Get required variable (throws exception if not set)
$password = EnvLoader::required('SUPABASE_PASSWORD');

// Get optional variable with default
$host = EnvLoader::get('SUPABASE_HOST', 'localhost');

// Get boolean value
$logEnabled = EnvLoader::bool('LOG_ENABLED', true);

// Get integer value
$timeout = EnvLoader::int('SUPABASE_TIMEOUT', 30);
```

## Transactions

```php
$db->beginTransaction();

try {
    // Insert user
    $insertBuilder = $db->queryBuilder('users');
    $insertQuery = $insertBuilder->buildInsert(['name' => 'John', 'email' => 'john@example.com']);
    $db->executeInsert($insertQuery, $insertBuilder->getParams());
    
    // Insert profile
    $profileBuilder = $db->queryBuilder('profiles');
    $profileQuery = $profileBuilder->buildInsert(['user_id' => $db->getLastInsertId(), 'bio' => 'Developer']);
    $db->executeInsert($profileQuery, $profileBuilder->getParams());
    
    $db->commit();
    echo "Transaction completed successfully";
    
} catch (\Exception $e) {
    $db->rollback();
    echo "Transaction rolled back: " . $e->getMessage();
}
```

## Security Features

### Environment-Based Configuration

```php
// ✅ Secure - credentials from environment
$db = DatabaseManager::createSupabaseFromUrl(
    EnvLoader::required('SUPABASE_URL'),
    EnvLoader::required('SUPABASE_PASSWORD')
);

// ❌ Insecure - hardcoded credentials
$db = DatabaseManager::createSupabaseFromUrl(
    'postgresql://postgres:password123@db.example.supabase.co:5432/postgres',
    'password123'
);
```

### SQL Injection Protection

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

## Production Deployment

### Docker

```dockerfile
FROM php:8.1-cli

# Install required extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Set environment variables
ENV SUPABASE_HOST=db.your-project.supabase.co
ENV SUPABASE_PASSWORD=your-secure-password
ENV LOG_LEVEL=ERROR
ENV APP_ENV=production

COPY . /app
WORKDIR /app

RUN composer install --no-dev --optimize-autoloader

CMD ["php", "your-app.php"]
```

### System Environment Variables

```bash
# Linux/macOS
export SUPABASE_HOST="db.your-project.supabase.co"
export SUPABASE_PASSWORD="your-secure-password"
export LOG_LEVEL="ERROR"
export APP_ENV="production"
```

## Examples

See `example_supabase.php` for comprehensive usage examples including:

- Environment-based configuration
- Multiple connection methods
- Query builder usage
- Supabase-specific features
- Transaction handling
- Error handling
- Logging configuration
- Health monitoring

## Requirements

- PHP 8.1 or higher
- PDO PostgreSQL extension
- Composer (for autoloading)

## Security Best Practices

1. **Never commit `.env` files** - Always use `.env.example` templates
2. **Use environment variables** in production
3. **Rotate credentials regularly**
4. **Use prepared statements** (automatic with this library)
5. **Validate all inputs** (automatic identifier sanitization)
6. **Monitor logs** for suspicious activity
7. **Use SSL connections** (enabled by default for Supabase)

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

---

⚠️ **Important**: Always keep your `.env` file secure and never commit it to version control!