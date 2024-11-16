<?php
class MaintenanceMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if (getenv('MAINTENANCE_MODE') === 'true') {
            $allowedIps = explode(',', getenv('MAINTENANCE_IPS'));
            if (!in_array($request->getClientIp(), $allowedIps)) {
                require_once 'views/maintenance.php';
                exit;
            }
        }
        return $next($request);
    }
}
