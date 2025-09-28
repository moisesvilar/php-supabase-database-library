<?php

use DatabaseLibrary\Utils\EnvLoader;

// Load environment variables
EnvLoader::load();

// Configuration using environment variables
return [
    'supabase' => [
        'host' => EnvLoader::get('SUPABASE_HOST', 'localhost'),
        'port' => EnvLoader::get('SUPABASE_PORT', '5432'),
        'database' => EnvLoader::get('SUPABASE_DATABASE', 'postgres'),
        'username' => EnvLoader::get('SUPABASE_USERNAME', 'postgres'),
        'password' => EnvLoader::required('SUPABASE_PASSWORD'),
        'ssl_mode' => EnvLoader::get('SUPABASE_SSL_MODE', 'require'),
        'timeout' => EnvLoader::int('SUPABASE_TIMEOUT', 30),
        'connect_timeout' => EnvLoader::int('SUPABASE_CONNECT_TIMEOUT', 10),
        'application_name' => EnvLoader::get('APP_NAME', 'DatabaseLibrary'),
    ],
    'project' => [
        'url' => EnvLoader::get('SUPABASE_PROJECT_URL'),
        'name' => EnvLoader::get('SUPABASE_PROJECT_NAME', 'Database Project'),
    ],
    'logging' => [
        'file' => EnvLoader::get('LOG_FILE', 'database.log'),
        'level' => EnvLoader::get('LOG_LEVEL', 'INFO'),
        'enabled' => EnvLoader::bool('LOG_ENABLED', true),
        'max_size' => EnvLoader::int('LOG_MAX_SIZE', 10485760), // 10MB
        'max_files' => EnvLoader::int('LOG_MAX_FILES', 5),
    ]
];