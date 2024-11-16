<?php

class Router
{
    private string $defaultController;
    private string $defaultAction;
    private string $basePath;
    private array $globalMiddlewares = [];
    private array $routes = [];
    private array $namedRoutes = [];
    private array $routeCache = [];
    private string $currentGroupPrefix = '';
    private array $currentGroupMiddlewares = [];
    private array $patterns = [
        'int' => '\d+',
        'string' => '[a-zA-Z]+',
        'slug' => '[a-zA-Z0-9-]+',
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}',
        'any' => '[^/]+'
    ];
    private array $errorHandlers = [
        404 => null,
        500 => null
    ];

    public function __construct(
        string $defaultController = 'Home',
        string $defaultAction = 'index',
        string $basePath = 'jcmvc'
    ) {
        $this->defaultController = $defaultController;
        $this->defaultAction = $defaultAction;
        $this->basePath = '/' . trim($basePath, '/');
    }

    /**
     * ເພີ່ມ URL pattern ໃໝ່
     */
    public function pattern(string $name, string $pattern): self
    {
        $this->patterns[$name] = $pattern;
        return $this;
    }

    /**
     * ເພີ່ມ global middleware
     */
    public function addMiddleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->globalMiddlewares = array_merge($this->globalMiddlewares, $middleware);
        } else {
            $this->globalMiddlewares[] = $middleware;
        }
        return $this;
    }

    /**
     * ສ້າງ router group
     */
    public function group(
        string $prefix,
        array $middlewares,
        callable $callback,
        array $attributes = []
    ): void {
        // ບັນທຶກຄ່າ group ເກົ່າ
        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddlewares = $this->currentGroupMiddlewares;

        // ຕັ້ງຄ່າ group ໃໝ່
        $this->currentGroupPrefix = $previousPrefix . '/' . trim($prefix, '/');
        $this->currentGroupMiddlewares = array_merge($previousMiddlewares, $middlewares);

        // ເອີ້ນໃຊ້ callback
        $callback($this);

        // ກັບຄືນຄ່າ group ເກົ່າ
        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddlewares = $previousMiddlewares;
    }

    /**
     * ລົງທະບຽນເສັ້ນທາງ RESTful resource
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $actions = $options['only'] ?? ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        $middlewares = $options['middleware'] ?? [];

        if (in_array('index', $actions)) {
            $this->get("/{$name}", $controller, 'index', $middlewares);
        }
        if (in_array('create', $actions)) {
            $this->get("/{$name}/create", $controller, 'create', $middlewares);
        }
        if (in_array('store', $actions)) {
            $this->post("/{$name}", $controller, 'store', $middlewares);
        }
        if (in_array('show', $actions)) {
            $this->get("/{$name}/{id}", $controller, 'show', $middlewares)
                ->where(['id' => 'int']);
        }
        if (in_array('edit', $actions)) {
            $this->get("/{$name}/{id}/edit", $controller, 'edit', $middlewares)
                ->where(['id' => 'int']);
        }
        if (in_array('update', $actions)) {
            $this->put("/{$name}/{id}", $controller, 'update', $middlewares)
                ->where(['id' => 'int']);
        }
        if (in_array('destroy', $actions)) {
            $this->delete("/{$name}/{id}", $controller, 'destroy', $middlewares)
                ->where(['id' => 'int']);
        }
    }

    /**
     * ລົງທະບຽນເສັ້ນທາງພື້ນຖານ
     */
    public function route(
        string $method,
        string $path,
        string $controller,
        string $action,
        array $middlewares = [],
        array $attributes = []
    ): self {
        // ລວມ path ກັບ group prefix
        $fullPath = rtrim($this->currentGroupPrefix . '/' . trim($path, '/'), '/');
        if (empty($fullPath)) $fullPath = '/';

        // ລວມ middlewares ຂອງ group ແລະ route
        $allMiddlewares = array_merge($this->currentGroupMiddlewares, $middlewares);

        $route = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $allMiddlewares,
            'attributes' => $attributes,
            'constraints' => []
        ];

        $pathKey = $method . $fullPath;
        $this->routes[$pathKey] = $route;

        return $this;
    }

    /**
     * ເພີ່ມຊື່ໃຫ້ກັບເສັ້ນທາງ
     */
    public function name(string $name): self
    {
        $lastRoute = end($this->routes);
        $this->namedRoutes[$name] = $lastRoute;
        return $this;
    }

    /**
     * ເພີ່ມເງື່ອນໄຂ parameter
     */
    public function where(array $constraints): self
    {
        $lastKey = array_key_last($this->routes);
        $this->routes[$lastKey]['constraints'] = array_map(
            fn($constraint) => $this->patterns[$constraint] ?? $constraint,
            $constraints
        );
        return $this;
    }

    /**
     * ສ້າງ URL ຈາກຊື່ເສັ້ນທາງ
     */
    public function generateUrl(string $name, array $params = [], bool $absolute = false): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new RuntimeException("Route not found: {$name}");
        }

        $route = $this->namedRoutes[$name];
        $path = $route['path'];

        // ແທນທີ່ parameters
        foreach ($params as $key => $value) {
            $pattern = "{{$key}}";
            if (strpos($path, $pattern) !== false) {
                // ກວດສອບ constraints
                if (isset($route['constraints'][$key])) {
                    $constraint = $route['constraints'][$key];
                    if (!preg_match("/^{$constraint}$/", $value)) {
                        throw new InvalidArgumentException("Invalid parameter value for {$key}");
                    }
                }
                $path = str_replace($pattern, $value, $path);
            }
        }

        // ກວດສອບວ່າມີ required parameters ທີ່ບໍ່ໄດ້ໃສ່ຄ່າບໍ່
        if (preg_match('/\{([^?}]+)\}/', $path, $matches)) {
            throw new RuntimeException("Missing required parameter: {$matches[1]}");
        }

        // ລຶບ optional parameters ທີ່ບໍ່ໄດ້ໃສ່ຄ່າ
        $path = preg_replace('/\{[^}]+\?\}/', '', $path);

        // ສ້າງ absolute URL ຖ້າຕ້ອງການ
        if ($absolute) {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            return "{$scheme}://{$host}{$this->basePath}{$path}";
        }

        return $this->basePath . $path;
    }

    // HTTP method shortcuts
    public function get(string $path, string $controller, string $action, array $middlewares = []): self
    {
        return $this->route('GET', $path, $controller, $action, $middlewares);
    }

    public function post(string $path, string $controller, string $action, array $middlewares = []): self
    {
        return $this->route('POST', $path, $controller, $action, $middlewares);
    }

    public function put(string $path, string $controller, string $action, array $middlewares = []): self
    {
        return $this->route('PUT', $path, $controller, $action, $middlewares);
    }

    public function delete(string $path, string $controller, string $action, array $middlewares = []): self
    {
        return $this->route('DELETE', $path, $controller, $action, $middlewares);
    }

    public function patch(string $path, string $controller, string $action, array $middlewares = []): self
    {
        return $this->route('PATCH', $path, $controller, $action, $middlewares);
    }

    /**
     * ຊອກຫາເສັ້ນທາງທີ່ກົງກັບ method ແລະ path
     */
    private function findRoute(string $method, string $path): ?array
    {
        $cacheKey = "{$method}:{$path}";

        if (isset($this->routeCache[$cacheKey])) {
            return $this->routeCache[$cacheKey];
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method) {
                $pattern = $this->convertPathToRegex($route['path'], $route['constraints']);
                if (preg_match($pattern, $path, $matches)) {
                    $result = [
                        'route' => $route,
                        'matches' => $matches
                    ];
                    $this->routeCache[$cacheKey] = $result;
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * ປະມວນຜົນ middlewares
     */
    private function runMiddlewares(array $middlewares, Request $request): bool
    {
        $allMiddlewares = array_merge($this->globalMiddlewares, $middlewares);

        foreach ($allMiddlewares as $middleware) {
            if (!class_exists($middleware)) {
                throw new RuntimeException("Middleware class not found: {$middleware}");
            }

            $instance = new $middleware();
            if (!$instance->handle($request)) {
                return false;
            }
        }
        return true;
    }

    /**
     * ແປງ path pattern ເປັນ regex
     */
    private function convertPathToRegex(string $path, array $constraints = []): string
    {
        $pattern = $path;

        // ແທນທີ່ optional parameters
        $pattern = preg_replace_callback('/\{([a-zA-Z]+)\?\}/', function ($matches) use ($constraints) {
            $name = $matches[1];
            $constraint = $constraints[$name] ?? '[^/]+';
            return "({$constraint})?";
        }, $pattern);

        // ແທນທີ່ required parameters
        $pattern = preg_replace_callback('/\{([a-zA-Z]+)\}/', function ($matches) use ($constraints) {
            $name = $matches[1];
            $constraint = $constraints[$name] ?? '[^/]+';
            return "({$constraint})";
        }, $pattern);

        return '#^' . $pattern . '$#';
    }

    /**
     * ດຶງ parameters ຈາກ matches
     */
    private function extractParams(array $matches): array
    {
        array_shift($matches);
        return array_values(array_filter($matches, function ($value) {
            return $value !== '' && $value !== null;
        }));
    }

    /**
     * ຈັດການ error handlers
     */
    public function set404(callable $handler): void
    {
        $this->errorHandlers[404] = $handler;
    }

    public function set500(callable $handler): void
    {
        $this->errorHandlers[500] = $handler;
    }

    /**
     * ຈັດການ routing
     */
    public function handle(): void
    {
        try {
            $request = new Request();
            $path = $this->getPath();
            $method = $request->getMethod();

            // ກວດສອບ HTTP method
            $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
            if (!in_array($method, $allowedMethods)) {
                throw new RuntimeException('Method not allowed');
            }

            // ຈັດການ trailing slashes
            $path = rtrim($path, '/') ?: '/';

            $result = $this->findRoute($method, $path);

            if ($result) {
                $route = $result['route'];

                // ປະມວນຜົນ middlewares
                if (!$this->runMiddlewares($route['middlewares'], $request)) {
                    return;
                }

                // ດຶງ parameters
                $params = $this->extractParams($result['matches']);

                // ເອີ້ນໃຊ້ controller
                $controllerClass = $route['controller'];
                if (!class_exists($controllerClass)) {
                    $controllerFile = "controllers/{$controllerClass}.php";
                    if (!file_exists($controllerFile)) {
                        throw new RuntimeException("Controller file not found: {$controllerFile}");
                    }
                    require_once $controllerFile;
                }

                $controllerInstance = new $controllerClass();
                if (!method_exists($controllerInstance, $route['action'])) {
                    throw new RuntimeException("Action {$route['action']} not found in controller {$controllerClass}");
                }

                $response = call_user_func_array(
                    [$controllerInstance, $route['action']],
                    array_merge([$request], $params)
                );

                // ຈັດການ response
                if ($response instanceof Response) {
                    $response->send();
                } else if (is_array($response) || is_object($response)) {
                    (new Response())->json($response);
                } else {
                    (new Response())->setContent($response)->send();
                }
                return;
            }

            // 404 Not Found
            if (isset($this->errorHandlers[404])) {
                call_user_func($this->errorHandlers[404]);
            } else {
                $this->handle404();
            }
        } catch (Throwable $e) {
            // ບັນທຶກຂໍ້ຜິດພາດ
            $errorMessage = sprintf(
                "[%s] %s in %s:%d\nStack trace:\n%s",
                date('Y-m-d H:i:s'),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            error_log($errorMessage, 3, dirname(__DIR__) . '/logs/error.log');

            // 500 Internal Server Error
            if (isset($this->errorHandlers[500])) {
                call_user_func($this->errorHandlers[500], $e);
            } else {
                $this->handle500($e);
            }
        }
    }

    /**
     * ຈັດການ 404 Not Found
     */
    private function handle404(): void
    {
        $response = new Response();
        $debug = App::getInstance()->getConfig('debug');
        $path = $this->getPath();

        if ($debug) {
            $response->setStatusCode(404)
                ->setContent("404 Not Found: {$path}")
                ->send();
        } else {
            $response->setStatusCode(404)
                ->setContent('404 Not Found')
                ->send();
        }
    }

    /**
     * ຈັດການ 500 Internal Server Error
     */
    private function handle500(Throwable $e): void
    {
        $response = new Response();
        $debug = App::getInstance()->getConfig('debug');

        if ($debug) {
            $content = sprintf(
                "500 Internal Server Error\n%s\nin %s:%d\n\nStack trace:\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
        } else {
            $content = '500 Internal Server Error';
        }

        $response->setStatusCode(500)
            ->setContent($content)
            ->send();
    }

    /**
     * ດຶງ request path ຈາກ URL
     */
    private function getPath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        $uri = rtrim($uri, '/');

        if ($uri === $this->basePath || $uri === $this->basePath . '/') {
            return '/';
        }

        $basePathPattern = '#^' . preg_quote($this->basePath) . '#';
        $path = preg_replace($basePathPattern, '', $uri);

        return '/' . trim($path, '/');
    }

    /**
     * ເພີ່ມ API route group
     */
    public function api(string $prefix, callable $callback): void
    {
        $this->group($prefix, [
            'ApiMiddleware',
            'JsonResponseMiddleware'
        ], $callback);
    }

    /**
     * ເພີ່ມ admin route group
     */
    public function admin(string $prefix, callable $callback): void
    {
        $this->group($prefix, [
            'AuthMiddleware',
            'AdminMiddleware'
        ], $callback);
    }

    /**
     * ເພີ່ມຫຼາຍເສັ້ນທາງພ້ອມກັນ
     */
    public function routes(array $routes): void
    {
        foreach ($routes as $route) {
            $method = $route[0];
            $path = $route[1];
            $controller = $route[2];
            $action = $route[3];
            $middlewares = $route[4] ?? [];

            $this->route($method, $path, $controller, $action, $middlewares);
        }
    }

    /**
     * ລຶບເສັ້ນທາງທັງໝົດ
     */
    public function clearRoutes(): void
    {
        $this->routes = [];
        $this->namedRoutes = [];
        $this->routeCache = [];
    }

    /**
     * ດຶງເສັ້ນທາງທັງໝົດ
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * ດຶງເສັ້ນທາງທີ່ມີຊື່
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    /**
     * ກວດສອບວ່າມີເສັ້ນທາງຫຼືບໍ່
     */
    public function hasRoute(string $method, string $path): bool
    {
        return $this->findRoute($method, $path) !== null;
    }

    /**
     * ກວດສອບວ່າມີເສັ້ນທາງທີ່ມີຊື່ຫຼືບໍ່
     */
    public function hasNamedRoute(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    /**
     * ດຶງ current route
     */
    public function getCurrentRoute(): ?array
    {
        $path = $this->getPath();
        $method = $_SERVER['REQUEST_METHOD'];
        $result = $this->findRoute($method, $path);
        return $result ? $result['route'] : null;
    }

    /**
     * ດຶງ current route name
     */
    public function getCurrentRouteName(): ?string
    {
        $currentRoute = $this->getCurrentRoute();
        if (!$currentRoute) {
            return null;
        }

        foreach ($this->namedRoutes as $name => $route) {
            if ($route === $currentRoute) {
                return $name;
            }
        }

        return null;
    }

    /**
     * ກວດສອບວ່າແມ່ນ route ປັດຈຸບັນຫຼືບໍ່
     */
    public function isCurrentRoute(string $name): bool
    {
        return $this->getCurrentRouteName() === $name;
    }

    /**
     * Fallback route
     */
    public function fallback(string $controller, string $action): void
    {
        $this->errorHandlers[404] = function () use ($controller, $action) {
            if (!class_exists($controller)) {
                require_once "controllers/{$controller}.php";
            }
            $instance = new $controller();
            call_user_func([$instance, $action]);
        };
    }

    /**
     * Domain routing
     */
    public function domain(string $domain, callable $callback): void
    {
        if ($_SERVER['HTTP_HOST'] === $domain) {
            $callback($this);
        }
    }

    /**
     * ເພີ່ມ subdomain routing
     */
    public function subdomain(string $subdomain, callable $callback): void
    {
        $host = $_SERVER['HTTP_HOST'];
        $pattern = "#^{$subdomain}\.#";
        if (preg_match($pattern, $host)) {
            $callback($this);
        }
    }

    /**
     * Match multiple HTTP methods
     */
    public function match(array $methods, string $path, string $controller, string $action, array $middlewares = []): self
    {
        foreach ($methods as $method) {
            $this->route($method, $path, $controller, $action, $middlewares);
        }
        return $this;
    }

    /**
     * Any HTTP method
     */
    public function any(string $path, string $controller, string $action, array $middlewares = []): self
    {
        return $this->match(
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            $path,
            $controller,
            $action,
            $middlewares
        );
    }
}
