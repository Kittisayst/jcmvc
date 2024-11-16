<?php

class Helper {
    /**
     * ຫຼີກລ່ຽງ XSS attacks
     * @param string $string
     * @return string
     */
    public static function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * ສ້າງ URL 
     * @param string $path
     * @param array $params Query parameters
     * @return string
     */
    public static function url($path = '', $params = []) {
        $basePath = '/jcmvc';  // ອ່ານຈາກ config ຫຼື .env ໃນອະນາຄົດ
        $url = $basePath . '/' . trim($path, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * ສ້າງ CSRF token
     * @return string
     */
    public static function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * ກວດສອບ CSRF token
     * @param string $token
     * @return bool
     */
    public static function validateCsrfToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * ສ້າງ CSRF input field
     * @return string
     */
    public static function csrfField() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Redirect ໄປຫາ URL ອື່ນ
     * @param string $url
     * @return void
     */
    public static function redirect($url) {
        header('Location: ' . self::url($url));
        exit();
    }

    /**
     * ເຊັກວ່າແມ່ນ AJAX request ຫຼື ບໍ່
     * @return bool
     */
    public static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * ສົ່ງ JSON response
     * @param mixed $data
     * @param int $status HTTP status code
     * @return void
     */
    public static function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit();
    }

    /**
     * ຂຽນ log
     * @param string $message
     * @param string $level
     * @return void
     */
    public static function log($message, $level = 'info') {
        $logFile = 'logs/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
        error_log($logMessage, 3, $logFile);
    }

    /**
     * Format ວັນທີເປັນພາສາລາວ
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function formatDate($date, $format = 'j F Y') {
        $thaiMonths = [
            'January' => 'ມັງກອນ',
            'February' => 'ກຸມພາ',
            'March' => 'ມີນາ',
            'April' => 'ເມສາ',
            'May' => 'ພຶດສະພາ',
            'June' => 'ມິຖຸນາ',
            'July' => 'ກໍລະກົດ',
            'August' => 'ສິງຫາ',
            'September' => 'ກັນຍາ',
            'October' => 'ຕຸລາ',
            'November' => 'ພະຈິກ',
            'December' => 'ທັນວາ'
        ];
        
        $englishDate = date($format, strtotime($date));
        return str_replace(array_keys($thaiMonths), array_values($thaiMonths), $englishDate);
    }

    /**
     * ກວດສອບການ login
     * @return bool
     */
    public static function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }

    /**
     * ດຶງຂໍ້ມູນຜູ້ໃຊ້ທີ່ login
     * @return array|null
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['user'] ?? null;
    }

    /**
     * ເຂົ້າລະຫັດ password
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * ກວດສອບ password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * ສຸ່ມ string
     * @param int $length
     * @return string
     */
    public static function randomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * ດຶງຊື່ class ໂດຍບໍ່ມີ namespace
     */
    public static function classBasename($class): string 
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Convert string to snake case
     */
    public static function snakeCase(string $string): string 
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * Convert string to camel case
     */
    public static function camelCase(string $string): string 
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

}