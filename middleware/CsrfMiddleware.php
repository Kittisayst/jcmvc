<?php
class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->getMethod() === 'POST') {
            $token = $request->post('csrf_token');
            if (!$token || !Helper::validateCsrfToken($token)) {
                throw new Exception('ຄຳຂໍບໍ່ຖືກຕ້ອງ, ກະລຸນາລອງໃໝ່');
            }
        }
        return $next($request);
    }
}
