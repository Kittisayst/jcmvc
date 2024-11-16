<?php
// ຄວນເພີ່ມ error reporting ໃນໂໝດ development
if (getenv('APP_DEBUG') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ຄວນເພີ່ມການກວດສອບ .env file
if (!file_exists(__DIR__ . '/.env')) {
    die('.env file not found');
}
spl_autoload_register(function ($class) {
    static $classMap = null;

    if ($classMap === null) {
        $classMap = [];
        $baseDir = __DIR__;

        // Core classes
        $coreClasses = [
            'Config' => '/core/Config.php',
            'App' => '/core/App.php',
            'Controller' => '/core/Controller.php',
            'Database' => '/core/Database.php',
            'View' => '/core/View.php',
            'Helper' => '/core/Helper.php',
            'Model' => '/core/Model.php',
            'Request' => '/core/Request.php',
            'Response' => '/core/Response.php',
            'Session' => '/core/Session.php',
            'Router' => '/core/Router.php',
            'Validator' => '/core/Validator.php'
        ];

        // Add core classes to map
        foreach ($coreClasses as $className => $path) {
            $classMap[$className] = $baseDir . $path;
        }

        // Scan controllers directory
        foreach (glob($baseDir . '/controllers/*.php') as $file) {
            $className = basename($file, '.php');
            $classMap[$className] = $file;
        }

        // Scan models directory
        foreach (glob($baseDir . '/models/*.php') as $file) {
            $className = basename($file, '.php');
            $classMap[$className] = $file;
        }
    }

    if (isset($classMap[$class])) {
        require $classMap[$class];
        return true;
    }

    return false;
});

$app = App::getInstance();

require_once "routes/web.php";
createWebRoutes($app->getRouter());

$app->run();
