<?php

// Configuración específica para tu proyecto Supabase
return [
    'supabase' => [
        'host' => 'db.dpcrrmhfgedsoufmdcgq.supabase.co',
        'port' => '5432',
        'database' => 'postgres',
        'username' => 'postgres',
        'password' => 'Supabase.1184.1983',
        'ssl_mode' => 'require',
        'timeout' => 30,
        'connect_timeout' => 10,
        'application_name' => 'DatabaseLibrary',
    ],
    'project' => [
        'url' => 'https://dpcrrmhfgedsoufmdcgq.supabase.co',
        'name' => 'Moises Vilar Org',
    ],
    'logging' => [
        'file' => 'database.log',
        'level' => 'INFO',
        'enabled' => true,
        'max_size' => 10485760, // 10MB
        'max_files' => 5,
    ]
];
