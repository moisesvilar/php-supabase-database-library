<?php

namespace DatabaseLibrary\Utils;

class Config
{
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => '5432',
            'database' => '',
            'username' => '',
            'password' => '',
            'options' => [],
            'timeout' => 30,
            'charset' => 'utf8',
            'ssl_mode' => 'prefer',
            'application_name' => 'DatabaseLibrary',
            'connect_timeout' => 10,
            'statement_timeout' => 0,
            'idle_in_transaction_session_timeout' => 0,
        ], $config);
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    public function getAll(): array
    {
        return $this->config;
    }

    public function getDatabaseConfig(): array
    {
        return [
            'host' => $this->get('host'),
            'port' => $this->get('port'),
            'database' => $this->get('database'),
            'username' => $this->get('username'),
            'password' => $this->get('password'),
            'options' => $this->get('options'),
        ];
    }

    public function getPdoOptions(): array
    {
        $baseOptions = $this->get('options', []);
        
        return $baseOptions + [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_PERSISTENT => false,
            \PDO::ATTR_TIMEOUT => $this->get('timeout'),
        ];
    }

    public function getSupabaseOptions(): array
    {
        return $this->getPdoOptions();
    }

    public static function fromSupabaseUrl(string $supabaseUrl, string $password): self
    {
        // Parse Supabase URL: postgresql://postgres:[password]@[host]:[port]/postgres
        $parsed = parse_url($supabaseUrl);
        
        if (!$parsed || !isset($parsed['host'])) {
            throw new \InvalidArgumentException('Invalid Supabase URL format');
        }

        return new self([
            'host' => $parsed['host'],
            'port' => $parsed['port'] ?? '5432',
            'database' => 'postgres',
            'username' => 'postgres',
            'password' => $password,
            'ssl_mode' => 'require',
            'application_name' => 'DatabaseLibrary-Supabase',
        ]);
    }

    public static function fromEnvironment(): self
    {
        return new self([
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '5432',
            'database' => $_ENV['DB_DATABASE'] ?? '',
            'username' => $_ENV['DB_USERNAME'] ?? '',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'ssl_mode' => $_ENV['DB_SSL_MODE'] ?? 'prefer',
            'timeout' => (int)($_ENV['DB_TIMEOUT'] ?? 30),
            'connect_timeout' => (int)($_ENV['DB_CONNECT_TIMEOUT'] ?? 10),
            'application_name' => $_ENV['DB_APP_NAME'] ?? 'DatabaseLibrary',
        ]);
    }

    public static function fromArray(array $config): self
    {
        return new self($config);
    }

    public function validate(): bool
    {
        $required = ['host', 'database', 'username', 'password'];
        
        foreach ($required as $key) {
            if (empty($this->get($key))) {
                throw new \InvalidArgumentException("Required configuration key '{$key}' is missing or empty");
            }
        }

        // Validate port
        $port = (int)$this->get('port');
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException("Invalid port number: {$port}");
        }

        // Validate timeout values
        $timeout = (int)$this->get('timeout');
        if ($timeout < 0) {
            throw new \InvalidArgumentException("Timeout must be non-negative");
        }

        return true;
    }

    public function toDsn(): string
    {
        $this->validate();
        
        $host = $this->get('host');
        $port = $this->get('port');
        $database = $this->get('database');
        
        return "pgsql:host={$host};port={$port};dbname={$database}";
    }

    public function toSupabaseDsn(): string
    {
        $this->validate();
        
        $host = $this->get('host');
        $port = $this->get('port');
        $database = $this->get('database');
        $sslMode = $this->get('ssl_mode');
        
        return "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslMode}";
    }

    public function isSupabase(): bool
    {
        return strpos($this->get('host'), 'supabase') !== false || 
               $this->get('ssl_mode') === 'require';
    }

    public function getConnectionString(): string
    {
        $config = $this->getDatabaseConfig();
        return "host={$config['host']} port={$config['port']} dbname={$config['database']} user={$config['username']} password={$config['password']}";
    }
}
