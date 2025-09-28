<?php

require_once 'vendor/autoload.php';

use DatabaseLibrary\DatabaseManager;
use DatabaseLibrary\Database\Supabase\SupabaseQueryBuilder;
use DatabaseLibrary\Utils\Config;
use DatabaseLibrary\Utils\Logger;
use DatabaseLibrary\Utils\EnvLoader;

echo "=== Database Library Example - Supabase Project ===\n\n";

try {
    // Load environment variables
    EnvLoader::load();
    
    // Configuration using environment variables
    echo "1. Creating Supabase connection...\n";
    
    // Method 1: Using URL from environment variable
    $supabaseUrl = EnvLoader::required('SUPABASE_URL');
    $password = EnvLoader::required('SUPABASE_PASSWORD');
    
    $db = DatabaseManager::createSupabaseFromUrl($supabaseUrl, $password);
    echo "✓ Connection created successfully\n\n";

    // Connect to database
    echo "2. Connecting to database...\n";
    $db->connect();
    echo "✓ Connected successfully\n\n";

    // Verify connection with a simple query
    echo "3. Verifying connection...\n";
    $result = $db->executeQuery('SELECT version() as version, current_database() as database, current_user as user');
    echo "✓ Connection verified\n";
    echo "PostgreSQL Version: " . $result[0]['version'] . "\n";
    echo "Database: " . $result[0]['database'] . "\n";
    echo "User: " . $result[0]['user'] . "\n\n";

    // Create example table if it doesn't exist
    echo "4. Creating example table...\n";
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS test_users (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            metadata JSONB DEFAULT '{}'::jsonb,
            tags TEXT[] DEFAULT '{}',
            location POINT
        )
    ";
    
    $db->getConnection()->execute($createTableQuery);
    echo "✓ Table 'test_users' created/verified\n\n";

    // Example with basic Query Builder
    echo "5. Basic Query Builder example...\n";
    $queryBuilder = $db->queryBuilder('test_users');
    $query = $queryBuilder
        ->select(['id', 'name', 'email'])
        ->where('active', '=', true)
        ->orderBy('created_at', 'DESC')
        ->limit(5)
        ->buildSelect();

    echo "Generated query: {$query}\n";
    echo "Parameters: " . json_encode($queryBuilder->getParams()) . "\n\n";

    // Insert example data
    echo "6. Inserting example data...\n";
    $insertBuilder = $db->queryBuilder('test_users');
    $insertQuery = $insertBuilder->buildInsert([
        'name' => 'John Doe',
        'email' => 'john_' . time() . '@example.com',
        'active' => true,
        'metadata' => json_encode(['role' => 'admin', 'department' => 'IT']),
        'tags' => '{' . implode(',', ['developer', 'php', 'supabase']) . '}'
    ]);

    $db->executeInsert($insertQuery, $insertBuilder->getParams());
    echo "✓ User inserted\n\n";

    // Example with Supabase Query Builder - advanced features
    echo "7. Supabase Query Builder example...\n";
    
    // Case-insensitive search
    $supabaseBuilder = $db->supabaseQueryBuilder('test_users');
    $ilikeQuery = $supabaseBuilder
        ->select(['*'])
        ->whereILike('name', '%john%')
        ->buildSelectWithSupabaseFilters();
    
    echo "Case-insensitive query: {$ilikeQuery}\n";
    echo "Parameters: " . json_encode($supabaseBuilder->getParams()) . "\n\n";

    // JSON search
    $supabaseBuilder->reset();
    $jsonQuery = $supabaseBuilder
        ->select(['*'])
        ->whereJsonContains('metadata', 'role', 'admin')
        ->buildSelectWithSupabaseFilters();
    
    echo "JSON query: {$jsonQuery}\n";
    echo "Parameters: " . json_encode($supabaseBuilder->getParams()) . "\n\n";

    // Array search
    $supabaseBuilder->reset();
    $arrayQuery = $supabaseBuilder
        ->select(['*'])
        ->whereArrayOverlaps('tags', ['developer', 'php'])
        ->buildSelectWithSupabaseFilters();
    
    echo "Array query: {$arrayQuery}\n";
    echo "Parameters: " . json_encode($supabaseBuilder->getParams()) . "\n\n";

    // Transaction example
    echo "8. Transaction example...\n";
    $db->beginTransaction();
    try {
        // Insert user with RETURNING
        $insertWithReturningBuilder = $db->supabaseQueryBuilder('test_users');
        $insertQuery = $insertWithReturningBuilder->buildInsertWithReturning(
            [
                'name' => 'Jane Smith',
                'email' => 'jane_' . time() . '@example.com',
                'active' => true,
                'metadata' => json_encode(['role' => 'user', 'department' => 'Sales']),
                'tags' => '{' . implode(',', ['sales', 'marketing']) . '}'
            ],
            ['id', 'created_at']
        );
        
        $result = $db->getConnection()->execute($insertQuery, $insertWithReturningBuilder->getParams());
        $userData = $result->fetchAll();
        $userId = $userData[0]['id'] ?? null;
        
        echo "✓ User inserted with ID: {$userId}\n";

        // Update user
        $updateBuilder = $db->supabaseQueryBuilder('test_users');
        $updateQuery = $updateBuilder
            ->where('id', '=', $userId)
            ->buildUpdateWithReturning(['name' => 'Jane Smith Updated'], ['id', 'name', 'created_at']);
        
        $db->executeUpdate($updateQuery, $updateBuilder->getParams());
        echo "✓ User updated\n";

        $db->commit();
        echo "✓ Transaction completed successfully\n\n";

    } catch (\Exception $e) {
        $db->rollback();
        echo "✗ Transaction rolled back: " . $e->getMessage() . "\n\n";
    }

    // Read inserted data
    echo "9. Reading inserted data...\n";
    $users = $db->executeQuery('SELECT * FROM test_users ORDER BY created_at DESC LIMIT 5');
    echo "✓ Users found: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "  - ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}\n";
    }
    echo "\n";

    // Health check
    echo "10. Health check...\n";
    $health = $db->healthCheck();
    echo "Health status:\n";
    foreach ($health as $key => $value) {
        echo "  {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }
    echo "\n";

    // Logger information
    echo "11. Logger information...\n";
    $logger = $db->getLogger();
    echo "Log enabled: " . ($logger->isEnabled() ? 'true' : 'false') . "\n";
    echo "Log level: " . $logger->getLogLevel() . "\n";
    echo "Log file: " . $logger->getLogFile() . "\n";
    echo "Log size: " . $logger->getLogSize() . " bytes\n";
    echo "Recent log entries:\n";
    
    $recentLogs = $logger->getLogContents(5);
    foreach ($recentLogs as $logEntry) {
        echo "  " . trim($logEntry) . "\n";
    }
    echo "\n";

    // Configuration information
    echo "12. Configuration information...\n";
    $config = $db->getConfig();
    echo "Host: " . EnvLoader::get('SUPABASE_HOST', 'not-set') . "\n";
    echo "Port: " . EnvLoader::get('SUPABASE_PORT', 'not-set') . "\n";
    echo "Database: " . EnvLoader::get('SUPABASE_DATABASE', 'not-set') . "\n";
    echo "SSL Mode: " . EnvLoader::get('SUPABASE_SSL_MODE', 'not-set') . "\n";
    echo "Is Supabase: " . (strpos(EnvLoader::get('SUPABASE_HOST', ''), 'supabase') !== false ? 'true' : 'false') . "\n\n";

    // Disconnect
    echo "13. Disconnecting...\n";
    $db->disconnect();
    echo "✓ Disconnected successfully\n\n";

    echo "=== Example completed successfully! ===\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    if (isset($db) && $db->isConnected()) {
        $db->disconnect();
    }
}

echo "\n=== Additional Information ===\n";
echo "To use this example:\n";
echo "1. Copy env.example to .env\n";
echo "2. Configure your Supabase credentials in .env\n";
echo "3. Run: php example_supabase.php\n";
echo "\n=== Required Environment Variables ===\n";
echo "SUPABASE_HOST - Your Supabase project host\n";
echo "SUPABASE_PASSWORD - Your database password\n";
echo "SUPABASE_URL - Complete connection URL\n";
echo "\n=== All set! ===\n";