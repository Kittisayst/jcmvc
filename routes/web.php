<?php
function createWebRoutes(Router $router)
{
    // Public routes
    $router->get('/', HomeController::class, 'index');
    $router->get('/show/{id}', HomeController::class, 'show');
    $router->get('/contact', IndexController::class, 'contact');
    $router->post('/contact', IndexController::class, 'submitContact');

    // Domain specific routes
    $router->domain('admin.example.com', function ($router) {
        $router->get('/', 'Admin\DashboardController', 'index');
    });

    // Fallback route (404)
    $router->fallback('ErrorController', 'notFound');

    // Custom error handlers
    $router->set404(function () {
        $controller = new ErrorController();
        $controller->notFound();
    });

    $router->set500(function ($e) {
        $controller = new ErrorController();
        $controller->serverError($e);
    });

    return $router;
}
