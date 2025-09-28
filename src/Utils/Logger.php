<?php

namespace DatabaseLibrary\Utils;

class Logger
{
    private $logFile;
    private $enabled;
    private $logLevel;
    private $maxFileSize;
    private $maxFiles;

    private static $logLevels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4,
    ];

    public function __construct(
        string $logFile = 'database.log',
        bool $enabled = true,
        string $logLevel = 'INFO',
        int $maxFileSize = 10485760, // 10MB
        int $maxFiles = 5
    ) {
        $this->logFile = $logFile;
        $this->enabled = $enabled;
        $this->logLevel = strtoupper($logLevel);
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;

        if (!in_array($this->logLevel, array_keys(self::$logLevels))) {
            throw new \InvalidArgumentException("Invalid log level: {$logLevel}");
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled || !$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

        $this->writeToFile($logEntry);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function logQuery(string $query, array $params = [], ?float $executionTime = null): void
    {
        $context = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime ? $executionTime . 'ms' : null,
        ];

        $this->info('Database query executed', $context);
    }

    public function logConnection(string $action, array $context = []): void
    {
        $this->info("Database connection {$action}", $context);
    }

    public function logTransaction(string $action, array $context = []): void
    {
        $this->info("Transaction {$action}", $context);
    }

    public function logError(string $message, ?\Exception $exception = null, array $context = []): void
    {
        $errorContext = array_merge($context, [
            'error_message' => $exception ? $exception->getMessage() : $message,
            'error_code' => $exception ? $exception->getCode() : null,
            'error_file' => $exception ? $exception->getFile() : null,
            'error_line' => $exception ? $exception->getLine() : null,
        ]);

        $this->error($message, $errorContext);
    }

    private function shouldLog(string $level): bool
    {
        $level = strtoupper($level);
        return isset(self::$logLevels[$level]) && 
               self::$logLevels[$level] >= self::$logLevels[$this->logLevel];
    }

    private function writeToFile(string $logEntry): void
    {
        $directory = dirname($this->logFile);
        
        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Check if file rotation is needed
        if (file_exists($this->logFile) && filesize($this->logFile) >= $this->maxFileSize) {
            $this->rotateLogFile();
        }

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function rotateLogFile(): void
    {
        // Rotate existing files
        for ($i = $this->maxFiles - 1; $i > 0; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i === $this->maxFiles - 1) {
                    // Delete the oldest file
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }

        // Move current log to .1
        if (file_exists($this->logFile)) {
            rename($this->logFile, $this->logFile . '.1');
        }
    }

    public function setLogLevel(string $level): self
    {
        $level = strtoupper($level);
        if (!in_array($level, array_keys(self::$logLevels))) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }
        
        $this->logLevel = $level;
        return $this;
    }

    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getLogFile(): string
    {
        return $this->logFile;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function clearLog(): bool
    {
        if (file_exists($this->logFile)) {
            return unlink($this->logFile);
        }
        return true;
    }

    public function getLogSize(): int
    {
        return file_exists($this->logFile) ? filesize($this->logFile) : 0;
    }

    public function getLogContents(int $lines = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $file = file($this->logFile);
        return array_slice($file, -$lines);
    }
}
