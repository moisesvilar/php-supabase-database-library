<?php

namespace DatabaseLibrary\Utils;

class EnvLoader
{
    private static $loaded = false;
    
    public static function load(string $path = null): void
    {
        if (self::$loaded) {
            return;
        }
        
        $envFile = $path ?? dirname(__DIR__, 2) . '/.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Set environment variable if not already set
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get(string $key, $default = null)
    {
        self::load();
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    public static function required(string $key)
    {
        $value = self::get($key);
        
        if ($value === null || $value === '') {
            throw new \RuntimeException("Required environment variable '{$key}' is not set");
        }
        
        return $value;
    }
    
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        
        if ($value === null) {
            return $default;
        }
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        
        if ($value === null) {
            return $default;
        }
        
        return (int) $value;
    }
}
