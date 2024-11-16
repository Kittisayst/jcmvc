<?php

class ErrorController extends Controller
{
    /**
     * ຈັດການກໍລະນີບໍ່ພົບໜ້າທີ່ຕ້ອງການ (404)
     */
    public function notFound(?string $message = null)
    {
        $this->setLayout(null);  // ບໍ່ໃຊ້ layout

        if (Helper::isAjax()) {
            return $this->json([
                'error' => 'Not Found',
                'message' => $message ?? 'ບໍ່ພົບໜ້າທີ່ທ່ານຕ້ອງການ'
            ], 404);
        }

        return $this->render('error/404', [
            'message' => $message,
            'backtrace' => $this->isDebugMode() ? debug_backtrace() : null,
            'requestInfo' => $this->isDebugMode() ? $this->getRequestInfo() : null
        ]);
    }

    /**
     * ຈັດການຂໍ້ຜິດພາດຈາກເຊີບເວີ (500)
     */
    public function serverError(?Throwable $error = null)
    {
        $this->setLayout(null);

        // ບັນທຶກຂໍ້ຜິດພາດ
        if ($error) {
            $this->logError($error);
        }

        if (Helper::isAjax()) {
            return $this->json([
                'error' => 'Internal Server Error',
                'message' => $this->isDebugMode() ? $error->getMessage() : 'ເກີດຂໍ້ຜິດພາດຈາກເຊີບເວີ'
            ], 500);
        }

        return $this->render('error/500', [
            'error' => $error,
            'debug' => $this->isDebugMode(),
            'requestInfo' => $this->isDebugMode() ? $this->getRequestInfo() : null
        ]);
    }

    /**
     * ຈັດການຂໍ້ຜິດພາດດ້ານການອະນຸຍາດ (403)
     */
    public function forbidden(?string $message = null)
    {
        $this->setLayout(null);

        if (Helper::isAjax()) {
            return $this->json([
                'error' => 'Forbidden',
                'message' => $message ?? 'ທ່ານບໍ່ໄດ້ຮັບອະນຸຍາດໃຫ້ເຂົ້າເຖິງໜ້ານີ້'
            ], 403);
        }

        return $this->render('error/403', [
            'message' => $message,
            'requestInfo' => $this->isDebugMode() ? $this->getRequestInfo() : null
        ]);
    }

    /**
     * ຈັດການຂໍ້ຜິດພາດດ້ານການພິສູດຕົວຕົນ (401)
     */
    public function unauthorized(?string $message = null)
    {
        $this->setLayout(null);

        if (Helper::isAjax()) {
            return $this->json([
                'error' => 'Unauthorized',
                'message' => $message ?? 'ກະລຸນາເຂົ້າສູ່ລະບົບ'
            ], 401);
        }

        return $this->render('error/401', [
            'message' => $message,
            'requestInfo' => $this->isDebugMode() ? $this->getRequestInfo() : null
        ]);
    }

    /**
     * ຈັດການຂໍ້ຜິດພາດດ້ານການຮ້ອງຂໍ (400)
     */
    public function badRequest(?string $message = null, array $errors = [])
    {
        $this->setLayout(null);

        if (Helper::isAjax()) {
            return $this->json([
                'error' => 'Bad Request',
                'message' => $message ?? 'ຄຳຮ້ອງຂໍບໍ່ຖືກຕ້ອງ',
                'errors' => $errors
            ], 400);
        }

        return $this->render('error/400', [
            'message' => $message,
            'errors' => $errors,
            'requestInfo' => $this->isDebugMode() ? $this->getRequestInfo() : null
        ]);
    }

    /**
     * ຈັດການຂໍ້ຜິດພາດ CSRF (419)
     */
    public function csrfTokenMismatch()
    {
        $this->setLayout(null);

        if (Helper::isAjax()) {
            return $this->json([
                'error' => 'CSRF Token Mismatch',
                'message' => 'ກະລຸນາໂຫຼດໜ້າໃໝ່ແລ້ວລອງອີກຄັ້ງ'
            ], 419);
        }

        return $this->render('error/419', [
            'requestInfo' => $this->isDebugMode() ? $this->getRequestInfo() : null
        ]);
    }

    /**
     * ຈັດການຂໍ້ຜິດພາດເວລາປິດບຳລຸງຮັກສາ (503)
     */
    public function maintenance(?string $message = null)
    {
        $this->setLayout(null);

        if (Helper::isAjax()) {
            return $this->json([
                'error' => 'Service Unavailable',
                'message' => $message ?? 'ລະບົບກຳລັງປິດປັບປຸງ'
            ], 503);
        }

        return $this->render('error/503', [
            'message' => $message
        ]);
    }

    /**
     * ກວດວ່າຢູ່ໃນໂໝດ debug ຫຼືບໍ່
     */
    private function isDebugMode(): bool
    {
        return App::getInstance()->getConfig('debug') === true;
    }

    /**
     * ດຶງຂໍ້ມູນ request ສຳລັບການ debug
     */
    private function getRequestInfo(): array
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'time' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'get' => $_GET,
            'post' => $this->filterSensitiveData($_POST),
            'headers' => getallheaders(),
            'session' => $this->filterSensitiveData($_SESSION ?? [])
        ];
    }

    /**
     * ກັ່ນຕອງຂໍ້ມູນທີ່ອ່ອນໄຫວອອກ
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'key', 'secret', 'card'];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            } else {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($key, $sensitiveKey) !== false) {
                        $data[$key] = '******';
                        break;
                    }
                }
            }
        }
        
        return $data;
    }

    /**
     * ບັນທຶກຂໍ້ຜິດພາດ
     */
    private function logError(Throwable $error): void
    {
        $message = sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            $error->getTraceAsString()
        );

        error_log($message, 3, 'logs/error.log');

        if ($this->isDebugMode()) {
            error_log($message);
        }
    }
}