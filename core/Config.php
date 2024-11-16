<?php
declare(strict_types=1);

class Config 
{
    private static ?Config $instance = null;
    private array $config = [];
    private array $requiredVars = [
        // App
        'APP_NAME',
        'APP_ENV',
        'APP_DEBUG',
        'APP_URL',
        'APP_TIMEZONE',
        'APP_LOCALE',
        'APP_BASEPATH',

        // Database
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'DB_CHARSET',

        // Session
        'SESSION_DRIVER',
        'SESSION_LIFETIME',
        'SESSION_SECURE',

        // Cache
        'CACHE_DRIVER',
        'CACHE_PREFIX',
        'CACHE_TTL',

        // Mail
        'MAIL_DRIVER',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',

        // Security
        'ENCRYPTION_KEY',
        'CSRF_LIFETIME',
        'PASSWORD_TIMEOUT'
    ];

    private array $typeMap = [
        'APP_DEBUG' => 'boolean',
        'APP_PORT' => 'integer',
        'DB_PORT' => 'integer',
        'SESSION_LIFETIME' => 'integer',
        'SESSION_SECURE' => 'boolean',
        'CACHE_TTL' => 'integer',
        'MAIL_PORT' => 'integer',
        'CSRF_LIFETIME' => 'integer',
        'PASSWORD_TIMEOUT' => 'integer',
        'RATE_LIMIT_ENABLED' => 'boolean',
        'RATE_LIMIT_ATTEMPTS' => 'integer',
        'RATE_LIMIT_DECAY_MINUTES' => 'integer',
        'MAINTENANCE_MODE' => 'boolean'
    ];

    /**
     * Constructor - Load .env file
     */
    private function __construct() 
    {
        $this->loadEnvFile();
        $this->validateRequiredVars();
        $this->setEnvironmentVariables();
    }

    /**
     * ໂຫຼດໄຟລ໌ .env
     */
    private function loadEnvFile(): void 
    {
        $envFile = dirname(__DIR__) . '/.env';
        if (!file_exists($envFile)) {
            throw new ConfigException(".env file not found");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // ລຶບ quotes ຖ້າມີ
                if (preg_match('/^"(.+)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.+)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                // Cast ຄ່າໃຫ້ເປັນປະເພດທີ່ຖືກຕ້ອງ
                $value = $this->castValue($key, $value);

                $this->config[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Cast ຄ່າໃຫ້ເປັນປະເພດທີ່ຖືກຕ້ອງ
     */
    private function castValue(string $key, $value) 
    {
        if (!isset($this->typeMap[$key])) {
            return $value;
        }

        switch ($this->typeMap[$key]) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
                return explode(',', $value);
            default:
                return $value;
        }
    }

    /**
     * ກວດສອບຕົວແປທີ່ຈຳເປັນ
     */
    private function validateRequiredVars(): void 
    {
        $missing = [];
        foreach ($this->requiredVars as $var) {
            if (!isset($this->config[$var])) {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            throw new ConfigException(
                "Missing required environment variables:\n" . 
                implode("\n", $missing)
            );
        }
    }

    /**
     * ຕັ້ງຄ່າ PHP environment
     */
    private function setEnvironmentVariables(): void 
    {
        // ຕັ້ງຄ່າ timezone
        date_default_timezone_set($this->config['APP_TIMEZONE']);

        // ຕັ້ງຄ່າ error reporting
        if ($this->config['APP_DEBUG']) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        // ຕັ້ງຄ່າ encoding
        ini_set('default_charset', 'UTF-8');
        mb_internal_encoding('UTF-8');
    }

    /**
     * ດຶງຄ່າ config
     * ຮອງຮັບການດຶງແບບ dot notation (e.g., 'database.host' -> DB_HOST)
     */
    public function get(string $key = null, $default = null) 
    {
        if ($key === null) {
            return $this->config;
        }

        // Convert dot notation to env var format
        $key = $this->convertKeyToEnvFormat($key);
        
        return $this->config[$key] ?? $default;
    }

    /**
     * ແປງ key ຈາກ dot notation ເປັນຮູບແບບ ENV
     */
    private function convertKeyToEnvFormat(string $key): string 
    {
        $segments = explode('.', $key);
        $envKey = '';

        foreach ($segments as $segment) {
            $envKey .= strtoupper($segment) . '_';
        }

        return rtrim($envKey, '_');
    }

    /**
     * ກວດສອບວ່າມີການຕັ້ງຄ່າຫຼືບໍ່
     */
    public function has(string $key): bool 
    {
        $key = $this->convertKeyToEnvFormat($key);
        return isset($this->config[$key]);
    }

    /**
     * ດຶງຄ່າ boolean
     */
    public function bool(string $key): bool 
    {
        return (bool) $this->get($key);
    }

    /**
     * ດຶງຄ່າ integer
     */
    public function int(string $key): int 
    {
        return (int) $this->get($key);
    }

    /**
     * ດຶງຄ່າ array (ແຍກດ້ວຍ comma)
     */
    public function array(string $key): array 
    {
        $value = $this->get($key);
        return is_array($value) ? $value : explode(',', $value);
    }

    /**
     * Singleton instance
     */
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ດຶງຄ່າໃນຮູບແບບ associative array ສຳລັບ section
     */
    public function getSection(string $section): array 
    {
        $section = strtoupper($section);
        $result = [];

        foreach ($this->config as $key => $value) {
            if (strpos($key, $section . '_') === 0) {
                $newKey = strtolower(substr($key, strlen($section) + 1));
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    private function __clone() {}

    public function __wakeup() 
    {
        throw new ConfigException("Cannot unserialize singleton");
    }
}

/**
 * Configuration Exception class
 */
class ConfigException extends Exception {}