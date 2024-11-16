<?php
declare(strict_types=1);

class Database 
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;
    private array $queryCache = [];
    private array $connectionPool = [];
    private int $maxConnections = 10;
    private int $cacheTTL = 3600; // Cache time to live (1 hour)
    private array $activeTransactions = [];
    private int $queryCount = 0;

    /**
     * Constructor
     */
    private function __construct() 
    {
        $this->loadConfig();
        $this->initializeConnectionPool();
    }

    /**
     * ໂຫຼດການຕັ້ງຄ່າຖານຂໍ້ມູນ
     */
    private function loadConfig(): void 
    {
        $configFile = dirname(__DIR__) . '/config/database.php';
        if (!is_readable($configFile)) {
            throw new DatabaseException("Database configuration file not found");
        }

        $this->config = require $configFile;

        // ກວດສອບການຕັ້ງຄ່າທີ່ຈຳເປັນ
        if (!isset($this->config['default'])) {
            throw new DatabaseException("Default database configuration not found");
        }

        // ໂຫຼດຄ່າຈາກ .env
        $this->config['default'] = array_merge($this->config['default'], [
            'host' => getenv('DB_HOST') ?: $this->config['default']['host'],
            'port' => getenv('DB_PORT') ?: $this->config['default']['port'],
            'database' => getenv('DB_DATABASE') ?: $this->config['default']['database'],
            'username' => getenv('DB_USERNAME') ?: $this->config['default']['username'],
            'password' => getenv('DB_PASSWORD') ?: $this->config['default']['password'],
        ]);
    }

    /**
     * ສ້າງ connection pool
     */
    private function initializeConnectionPool(): void 
    {
        for ($i = 0; $i < $this->maxConnections; $i++) {
            $this->connectionPool[] = null;
        }
    }

    /**
     * ສ້າງການເຊື່ອມຕໍ່ໃໝ່
     */
    private function createConnection(string $name = 'default'): PDO 
    {
        $config = $this->config[$name];

        // ສ້າງ DSN string
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=%s",
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        // ຕັ້ງຄ່າ PDO options
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']} COLLATE {$config['collation']}",
            PDO::ATTR_PERSISTENT => $config['persistent'] ?? false
        ];

        try {
            $connection = new PDO($dsn, $config['username'], $config['password'], $options);
            
            // ເພີ່ມ custom functions
            $this->registerCustomFunctions($connection);
            
            return $connection;
        } catch (PDOException $e) {
            throw new DatabaseException("Connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * ລົງທະບຽນ custom SQL functions
     */
    private function registerCustomFunctions(PDO $connection): void 
    {
        // ຕົວຢ່າງການເພີ່ມ custom function
        // $connection->sqliteCreateFunction('NOW', function() {
        //     return date('Y-m-d H:i:s');
        // });
    }

    /**
     * ດຶງການເຊື່ອມຕໍ່ທີ່ພ້ອມໃຊ້ງານ
     */
    private function getConnection(): PDO 
    {
        foreach ($this->connectionPool as &$conn) {
            if ($conn === null || !$this->isConnectionAlive($conn)) {
                $conn = $this->createConnection();
                return $conn;
            }
            if (!$this->isConnectionBusy($conn)) {
                return $conn;
            }
        }
        throw new DatabaseException("Connection pool exhausted");
    }

    /**
     * ກວດສອບວ່າການເຊື່ອມຕໍ່ຍັງໃຊ້ງານໄດ້ບໍ່
     */
    private function isConnectionAlive(PDO $connection): bool 
    {
        try {
            $connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * ກວດສອບວ່າການເຊື່ອມຕໍ່ກຳລັງຖືກໃຊ້ງານຢູ່ບໍ່
     */
    private function isConnectionBusy(PDO $connection): bool 
    {
        try {
            $statement = $connection->query('SELECT 1');
            return $statement->fetchColumn() !== '1';
        } catch (PDOException $e) {
            return true;
        }
    }

    /**
     * ສ້າງ cache key
     */
    private function generateCacheKey(string $sql, array $params = []): string 
    {
        return md5($sql . serialize($params));
    }

    /**
     * ກວດສອບວ່າຄຳສັ່ງ SQL ສາມາດ cache ໄດ້ບໍ່
     */
    private function isCacheable(string $sql): bool 
    {
        $sql = trim(strtoupper($sql));
        return strpos($sql, 'SELECT') === 0 && 
               strpos($sql, 'RAND()') === false &&
               strpos($sql, 'NOW()') === false;
    }

    /**
     * ດຳເນີນການ query ແລະ cache ຜົນລັບ
     */
    public function query(string $sql, array $params = [], bool $useCache = false): array 
    {
        $this->queryCount++;
        $cacheKey = $this->generateCacheKey($sql, $params);
        
        // ກວດສອບ cache
        if ($useCache && $this->isCacheable($sql)) {
            if (isset($this->queryCache[$cacheKey])) {
                if (time() < $this->queryCache[$cacheKey]['expires']) {
                    return $this->queryCache[$cacheKey]['data'];
                }
                unset($this->queryCache[$cacheKey]);
            }
        }

        try {
            $connection = $this->getConnection();
            $statement = $connection->prepare($sql);
            $statement->execute($params);
            $result = $statement->fetchAll();

            // ບັນທຶກ cache
            if ($useCache && $this->isCacheable($sql)) {
                $this->queryCache[$cacheKey] = [
                    'data' => $result,
                    'expires' => time() + $this->cacheTTL
                ];
            }

            return $result;
        } catch (PDOException $e) {
            throw new DatabaseException("Query failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * ດຳເນີນການ query ແລະ ດຶງຜົນລັບດຽວ
     */
    public function queryOne(string $sql, array $params = []): ?array 
    {
        $result = $this->query($sql, $params);
        return $result[0] ?? null;
    }

    /**
     * ດຳເນີນການ query ແລະ ດຶງຄ່າດຽວ
     */
    public function queryScalar(string $sql, array $params = []): mixed 
    {
        try {
            $connection = $this->getConnection();
            $statement = $connection->prepare($sql);
            $statement->execute($params);
            return $statement->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException("Query failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * ປະຕິບັດຄຳສັ່ງ SQL (INSERT, UPDATE, DELETE)
     */
    public function execute(string $sql, array $params = []): int 
    {
        try {
            $connection = $this->getConnection();
            $statement = $connection->prepare($sql);
            $statement->execute($params);
            return $statement->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException("Execute failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * ເລີ່ມ transaction
     */
    public function beginTransaction(): bool 
    {
        $connection = $this->getConnection();
        if ($connection->beginTransaction()) {
            $this->activeTransactions[] = $connection;
            return true;
        }
        return false;
    }

    /**
     * Commit transaction
     */
    public function commit(): bool 
    {
        if (empty($this->activeTransactions)) {
            return false;
        }
        
        $connection = array_pop($this->activeTransactions);
        return $connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool 
    {
        if (empty($this->activeTransactions)) {
            return false;
        }
        
        $connection = array_pop($this->activeTransactions);
        return $connection->rollBack();
    }

    /**
     * ດຶງ ID ຫຼ້າສຸດທີ່ເພີ່ມເຂົ້າ
     */
    public function lastInsertId(): string 
    {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * ລຶບ cache
     */
    public function clearCache(): void 
    {
        $this->queryCache = [];
    }

    /**
     * ດຶງຈຳນວນ queries ທີ່ປະຕິບັດ
     */
    public function getQueryCount(): int 
    {
        return $this->queryCount;
    }

    /**
     * ດຶງ instance ດຽວຂອງ Database
     */
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cleanup
     */
    public function __destruct() 
    {
        foreach ($this->connectionPool as &$connection) {
            $connection = null;
        }
    }

    // ປ້ອງກັນການ clone
    private function __clone() {}

    // ປ້ອງກັນການ unserialize
    public function __wakeup() 
    {
        throw new DatabaseException("Cannot unserialize singleton");
    }
}

/**
 * Database Exception class
 */
class DatabaseException extends Exception {}