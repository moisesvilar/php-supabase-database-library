<?php

require_once 'vendor/autoload.php';

use DatabaseLibrary\DatabaseManager;
use DatabaseLibrary\Database\Supabase\SupabaseQueryBuilder;
use DatabaseLibrary\Utils\Config;
use DatabaseLibrary\Utils\Logger;

echo "=== Database Library Example - Tu Proyecto Supabase ===\n\n";

try {
    // Configuración específica para tu proyecto
    echo "1. Creando conexión a tu Supabase...\n";
    $supabaseUrl = 'postgresql://postgres:Supabase.1184.1983@db.dpcrrmhfgedsoufmdcgq.supabase.co:5432/postgres';
    $password = 'Supabase.1184.1983';


    $db = DatabaseManager::createSupabaseFromUrl($supabaseUrl, $password);
    echo "✓ Conexión creada exitosamente\n\n";

    // Conectar a la base de datos
    echo "2. Conectando a la base de datos...\n";
    $db->connect();
    echo "✓ Conectado exitosamente\n\n";

    // Verificar conexión con una consulta simple
    echo "3. Verificando conexión...\n";
    $result = $db->executeQuery('SELECT version() as version, current_database() as database, current_user as user');
    echo "✓ Conexión verificada\n";
    echo "Versión PostgreSQL: " . $result[0]['version'] . "\n";
    echo "Base de datos: " . $result[0]['database'] . "\n";
    echo "Usuario: " . $result[0]['user'] . "\n\n";

    // Crear tabla de ejemplo si no existe
    echo "4. Creando tabla de ejemplo...\n";
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
    echo "✓ Tabla 'test_users' creada/verificada\n\n";

    // Ejemplo con Query Builder básico
    echo "5. Ejemplo con Query Builder básico...\n";
    $queryBuilder = $db->queryBuilder('test_users');
    $query = $queryBuilder
        ->select(['id', 'name', 'email'])
        ->where('active', '=', true)
        ->orderBy('created_at', 'DESC')
        ->limit(5)
        ->buildSelect();

    echo "Consulta generada: {$query}\n";
    echo "Parámetros: " . json_encode($queryBuilder->getParams()) . "\n\n";

    // Insertar datos de ejemplo
    echo "6. Insertando datos de ejemplo...\n";
    $insertBuilder = $db->queryBuilder('test_users');
    $insertQuery = $insertBuilder->buildInsert([
        'name' => 'Juan Pérez',
        'email' => 'juan_' . time() . '@example.com',
        'active' => true,
        'metadata' => json_encode(['role' => 'admin', 'department' => 'IT']),
        'tags' => '{' . implode(',', ['developer', 'php', 'supabase']) . '}'
    ]);

    $db->executeInsert($insertQuery, $insertBuilder->getParams());
    echo "✓ Usuario insertado\n\n";

    // Ejemplo con Supabase Query Builder - características avanzadas
    echo "7. Ejemplo con Supabase Query Builder...\n";
    
    // Búsqueda case-insensitive
    $supabaseBuilder = $db->supabaseQueryBuilder('test_users');
    $ilikeQuery = $supabaseBuilder
        ->select(['*'])
        ->whereILike('name', '%juan%')
        ->buildSelectWithSupabaseFilters();
    
    echo "Consulta case-insensitive: {$ilikeQuery}\n";
    echo "Parámetros: " . json_encode($supabaseBuilder->getParams()) . "\n\n";

    // Búsqueda en JSON
    $supabaseBuilder->reset();
    $jsonQuery = $supabaseBuilder
        ->select(['*'])
        ->whereJsonContains('metadata', 'role', 'admin')
        ->buildSelectWithSupabaseFilters();
    
    echo "Consulta JSON: {$jsonQuery}\n";
    echo "Parámetros: " . json_encode($supabaseBuilder->getParams()) . "\n\n";

    // Búsqueda en arrays
    $supabaseBuilder->reset();
    $arrayQuery = $supabaseBuilder
        ->select(['*'])
        ->whereArrayOverlaps('tags', ['developer', 'php'])
        ->buildSelectWithSupabaseFilters();
    
    echo "Consulta arrays: {$arrayQuery}\n";
    echo "Parámetros: " . json_encode($supabaseBuilder->getParams()) . "\n\n";

    // Ejemplo de transacción
    echo "8. Ejemplo de transacción...\n";
    $db->beginTransaction();
    try {
        // Insertar usuario con RETURNING
        $insertWithReturningBuilder = $db->supabaseQueryBuilder('test_users');
        $insertQuery = $insertWithReturningBuilder->buildInsertWithReturning(
            [
                'name' => 'María García',
                'email' => 'maria_' . time() . '@example.com',
                'active' => true,
                'metadata' => json_encode(['role' => 'user', 'department' => 'Sales']),
                'tags' => '{' . implode(',', ['sales', 'marketing']) . '}'
            ],
            ['id', 'created_at']
        );
        
        $result = $db->getConnection()->execute($insertQuery, $insertWithReturningBuilder->getParams());
        $userData = $result->fetchAll();
        $userId = $userData[0]['id'] ?? null;
        
        echo "✓ Usuario insertado con ID: {$userId}\n";

        // Actualizar usuario
        $updateBuilder = $db->supabaseQueryBuilder('test_users');
        $updateQuery = $updateBuilder
            ->where('id', '=', $userId)
            ->buildUpdateWithReturning(['name' => 'María García Actualizada'], ['id', 'name', 'created_at']);
        
        $db->executeUpdate($updateQuery, $updateBuilder->getParams());
        echo "✓ Usuario actualizado\n";

        $db->commit();
        echo "✓ Transacción completada exitosamente\n\n";

    } catch (\Exception $e) {
        $db->rollback();
        echo "✗ Transacción revertida: " . $e->getMessage() . "\n\n";
    }

    // Leer datos insertados
    echo "9. Leyendo datos insertados...\n";
    $users = $db->executeQuery('SELECT * FROM test_users ORDER BY created_at DESC LIMIT 5');
    echo "✓ Usuarios encontrados: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "  - ID: {$user['id']}, Nombre: {$user['name']}, Email: {$user['email']}\n";
    }
    echo "\n";

    // Health check
    echo "10. Health check...\n";
    $health = $db->healthCheck();
    echo "Estado de salud:\n";
    foreach ($health as $key => $value) {
        echo "  {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }
    echo "\n";

    // Información del logger
    echo "11. Información del logger...\n";
    $logger = $db->getLogger();
    echo "Log habilitado: " . ($logger->isEnabled() ? 'true' : 'false') . "\n";
    echo "Nivel de log: " . $logger->getLogLevel() . "\n";
    echo "Archivo de log: " . $logger->getLogFile() . "\n";
    echo "Tamaño del log: " . $logger->getLogSize() . " bytes\n";
    echo "Entradas recientes del log:\n";
    
    $recentLogs = $logger->getLogContents(5);
    foreach ($recentLogs as $logEntry) {
        echo "  " . trim($logEntry) . "\n";
    }
    echo "\n";

    // Información de configuración
    echo "12. Información de configuración...\n";
    $config = $db->getConfig();
    echo "Host: " . $config->get('host') . "\n";
    echo "Puerto: " . $config->get('port') . "\n";
    echo "Base de datos: " . $config->get('database') . "\n";
    echo "Modo SSL: " . $config->get('ssl_mode') . "\n";
    echo "Es Supabase: " . ($config->isSupabase() ? 'true' : 'false') . "\n\n";

    // Desconectar
    echo "13. Desconectando...\n";
    $db->disconnect();
    echo "✓ Desconectado exitosamente\n\n";

    echo "=== ¡Ejemplo completado exitosamente! ===\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    
    if (isset($db) && $db->isConnected()) {
        $db->disconnect();
    }
}

echo "\n=== Información adicional ===\n";
echo "Tu proyecto Supabase: https://dpcrrmhfgedsoufmdcgq.supabase.co\n";
echo "Base de datos: postgres\n";
echo "Host: db.dpcrrmhfgedsoufmdcgq.supabase.co\n";
echo "Puerto: 5432\n";
echo "Usuario: postgres\n";
echo "Tabla de ejemplo creada: test_users\n";
echo "Archivo de log: database.log\n";
echo "\n=== ¡Todo listo! ===\n";
