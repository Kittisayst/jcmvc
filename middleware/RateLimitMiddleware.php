<?php
class RateLimitMiddleware implements Middleware
{
    private $maxAttempts = 60;
    private $decayMinutes = 1;

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->getClientIp();
        $key = 'rate_limit_' . md5($ip);

        if (isset($_SESSION[$key])) {
            $attempts = $_SESSION[$key]['attempts'];
            $lastAttempt = $_SESSION[$key]['last_attempt'];

            // ກວດສອບວ່າໝົດເວລາແລ້ວບໍ່
            if (time() - $lastAttempt > $this->decayMinutes * 60) {
                $attempts = 0;
            }

            if ($attempts >= $this->maxAttempts) {
                header('HTTP/1.1 429 Too Many Requests');
                exit('ຄຳຂໍຫຼາຍເກີນໄປ, ກະລຸນາລໍຖ້າ 1 ນາທີ');
            }

            $_SESSION[$key] = [
                'attempts' => $attempts + 1,
                'last_attempt' => time()
            ];
        } else {
            $_SESSION[$key] = [
                'attempts' => 1,
                'last_attempt' => time()
            ];
        }

        return $next($request);
    }
}
