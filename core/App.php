<?php
declare(strict_types=1);

class App 
{
    private Router $router;
    private static App $instance;
    private array $config;
    private ?Database $db = null;
    private ?Session $session = null;
    private array $loadedComponents = [];

    /**
     * Constructor
     * ໂຫຼດການຕັ້ງຄ່າ ແລະ ກຳນົດຄ່າເລີ່ມຕົ້ນ
     */
    private function __construct() 
    {
        try {
            // ໂຫຼດ .env
            $this->loadDotEnv();

            // ໂຫຼດການຕັ້ງຄ່າ
            $this->loadConfig();

            // ເລີ່ມຕົ້ນ components ຫຼັກ
            $this->initializeCore();

            // ສ້າງ Router
            $this->initializeRouter();

            // ຕັ້ງຄ່າ error handling
            $this->setupErrorHandling();

        } catch (Throwable $e) {
            $this->handleFatalError($e);
        }
    }

    /**
     * ໂຫຼດການຕັ້ງຄ່າຈາກໄຟລ໌ .env
     */
    private function loadDotEnv(): void 
    {
        $envFile = dirname(__DIR__) . '/.env';
        if (!file_exists($envFile)) {
            throw new RuntimeException('.env file not found');
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
                }
                
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * ໂຫຼດການຕັ້ງຄ່າຈາກໄຟລ໌ config
     */
    private function loadConfig(): void 
    {
        $configFile = dirname(__DIR__) . '/config/app.php';
        if (!file_exists($configFile)) {
            throw new RuntimeException('Configuration file not found');
        }

        $config = require $configFile;
        
        // ກຳນົດຄ່າເລີ່ມຕົ້ນ
        $this->config = array_merge([
            'app' => [
                'name' => $this->env('APP_NAME', 'My Application'),
                'env' => $this->env('APP_ENV', 'production'),
                'debug' => $this->env('APP_DEBUG', false),
                'url' => $this->env('APP_URL', 'http://localhost'),
                'timezone' => $this->env('APP_TIMEZONE', 'UTC'),
                'locale' => $this->env('APP_LOCALE', 'en'),
                'charset' => 'UTF-8'
            ],
            'defaultController' => 'Home',
            'defaultAction' => 'index',
            'basePath' => trim($this->env('APP_BASE_PATH', ''), '/'),
            'middlewares' => []
        ], $config);
    }

    /**
     * ເລີ່ມຕົ້ນ components ຫຼັກຂອງລະບົບ
     */
    private function initializeCore(): void 
    {
        // ຕັ້ງຄ່າ timezone
        date_default_timezone_set($this->config['app']['timezone']);
        
        // ຕັ້ງຄ່າ encoding
        mb_internal_encoding($this->config['app']['charset']);
        
        // ເລີ່ມຕົ້ນ Database
        if (!isset($this->db)) {
            $this->db = Database::getInstance();
        }
        
        // ເລີ່ມຕົ້ນ Session
        if (!isset($this->session)) {
            $this->session = new Session([
                'name' => $this->env('SESSION_NAME', 'PHPSESSID'),
                'lifetime' => (int)$this->env('SESSION_LIFETIME', 120),
                'path' => $this->env('SESSION_PATH', '/'),
                'domain' => $this->env('SESSION_DOMAIN', ''),
                'secure' => $this->env('SESSION_SECURE', true),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            $this->session->start();
        }
    }

    /**
     * ເລີ່ມຕົ້ນ Router
     */
    private function initializeRouter(): void 
    {
        $this->router = new Router(
            $this->config['defaultController'],
            $this->config['defaultAction'],
            $this->config['basePath']
        );

        // ເພີ່ມ global middlewares
        if (!empty($this->config['middlewares'])) {
            $this->router->addMiddleware($this->config['middlewares']);
        }
    }

    /**
     * ຕັ້ງຄ່າ error handling
     */
    private function setupErrorHandling(): void 
    {
        // ຕັ້ງຄ່າ error reporting ຕາມໂໝດ debug
        if ($this->config['app']['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        // ກຳນົດ handlers
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * ຈັດການ errors
     */
    public function handleError($errno, $errstr, $errfile, $errline): bool 
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $message = sprintf(
            "[%s] Error [%s]: %s\nFile: %s\nLine: %s",
            date('Y-m-d H:i:s'),
            $errno,
            $errstr,
            $errfile,
            $errline
        );

        // ບັນທຶກ error
        error_log($message, 3, dirname(__DIR__) . '/logs/error.log');

        if ($this->config['app']['debug']) {
            throw new ErrorException($message, $errno, 1, $errfile, $errline);
        } else {
            $this->showErrorPage(500);
        }

        return true;
    }

    /**
     * ຈັດການ exceptions
     */
    public function handleException(Throwable $exception): void 
    {
        $message = sprintf(
            "[%s] Exception: %s\nFile: %s\nLine: %s\nTrace:\n%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        // ບັນທຶກ exception
        error_log($message, 3, dirname(__DIR__) . '/logs/error.log');

        if ($this->config['app']['debug']) {
            // ສະແດງໜ້າ debug
            require dirname(__DIR__) . '/views/error/debug.php';
        } else {
            $this->showErrorPage(500);
        }

        exit(1);
    }

    /**
     * ຈັດການ fatal errors
     */
    private function handleFatalError(Throwable $error): void 
    {
        $message = sprintf(
            "[%s] Fatal Error: %s\nFile: %s\nLine: %s",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );

        error_log($message, 3, dirname(__DIR__) . '/logs/error.log');

        // ຕັດການເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
        if ($this->db) {
            $this->db = null;
        }

        // ສະແດງໜ້າ error
        http_response_code(500);
        if ($this->config['app']['debug']) {
            echo "<h1>Fatal Error</h1>";
            echo "<pre>" . htmlspecialchars($message) . "</pre>";
        } else {
            require dirname(__DIR__) . '/views/error/500.php';
        }

        exit(1);
    }

    /**
     * ຈັດການ shutdown
     */
    public function handleShutdown(): void 
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleFatalError(new ErrorException(
                $error['message'], 
                0, 
                $error['type'], 
                $error['file'], 
                $error['line']
            ));
        }
    }

    /**
     * ສະແດງໜ້າ error
     */
    private function showErrorPage(int $code): void 
    {
        http_response_code($code);
        require dirname(__DIR__) . "/views/error/{$code}.php";
    }

    /**
     * ດຶງຄ່າຈາກ environment
     */
    public function env(string $key, $default = null) 
    {
        return $_ENV[$key] ?? $default;
    }

    /**
     * ດຶງການຕັ້ງຄ່າ
     */
    public function getConfig(string $key = null) 
    {
        if ($key === null) {
            return $this->config;
        }

        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * ດຶງ Database instance
     */
    public function getDatabase(): ?Database 
    {
        return $this->db;
    }

    /**
     * ດຶງ Session instance
     */
    public function getSession(): ?Session 
    {
        return $this->session;
    }

    /**
     * ດຶງ Router instance
     */
    public function getRouter(): Router 
    {
        return $this->router;
    }

    /**
     * ດຶງ instance ດຽວຂອງ App (Singleton pattern)
     */
    public static function getInstance(): self 
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ເລີ່ມການເຮັດວຽກຂອງແອັບ
     */
    public function run(): void 
    {
        try {
            // ເລີ່ມ output buffering
            ob_start();
            
            // ຈັດການ routing
            $this->router->handle();
            
            // ສົ່ງຂໍ້ມູນອອກ
            ob_end_flush();
        } catch (Throwable $e) {
            // ລຶບຂໍ້ມູນທີ່ຄ້າງຢູ່ໃນ buffer
            ob_end_clean();
            
            $this->handleException($e);
        }
    }

    /**
     * ປິດການເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
     */
    public function closeDatabase(): void 
    {
        if ($this->db !== null) {
            $this->db = null;
        }
    }

    /**
     * ເຊື່ອມຕໍ່ຖານຂໍ້ມູນໃໝ່
     */
    public function reconnectDatabase(): void 
    {
        $this->db = Database::getInstance();
    }

    /**
     * Cleanup resources
     */
    public function __destruct() 
    {
        // ປິດການເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
        $this->closeDatabase();
        
        // ບັນທຶກ session
        if ($this->session) {
            $this->session = null;
        }
    }

    // ປ້ອງກັນການ clone
    private function __clone() {}

    // ປ້ອງກັນການ unserialize
    public function __wakeup() 
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }
}