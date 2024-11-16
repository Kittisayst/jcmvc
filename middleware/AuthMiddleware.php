<?php
class AuthMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['flash']['warning'] = 'ກະລຸນາເຂົ້າສູ່ລະບົບກ່ອນ';
            header('Location: /jcmvc/login');
            exit;
        }
        return $next($request);
    }
}
