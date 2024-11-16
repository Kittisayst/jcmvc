<?php
class GuestMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if (isset($_SESSION['user'])) {
            header('Location: /jcmvc/');
            exit;
        }
        return $next($request);
    }
}
