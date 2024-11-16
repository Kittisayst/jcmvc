<?php

interface SessionInterface
{
    public function start(): bool;
    public function get(string $key, $default = null);
    public function set(string $key, $value): void;
    public function has(string $key): bool;
    public function remove(string $key): void;
    public function clear(): void;
    public function setFlash(string $key, $value): void;
    public function getFlash(string $key, $default = null);
    public function hasFlash(string $key): bool;
    public function regenerate(bool $deleteOld = false): bool;
    public function destroy(): bool;
}

class Session implements SessionInterface
{
    private array $options = [
        'name' => 'JCSESSID',
        'lifetime' => 7200,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        if (session_status() === PHP_SESSION_NONE) {
            $this->configure();
        }
    }

    private function configure(): void
    {
        session_name($this->options['name']);

        session_set_cookie_params([
            'lifetime' => $this->options['lifetime'],
            'path' => $this->options['path'],
            'domain' => $this->options['domain'],
            'secure' => $this->options['secure'],
            'httponly' => $this->options['httponly'],
            'samesite' => $this->options['samesite']
        ]);
    }

    public function start(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        // ເລີ່ມ session
        if (!session_start()) {
            return false;
        }

        // ກວດສອບ session hijacking
        if (!isset($_SESSION['ip'])) {
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        } elseif (
            $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ||
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
        ) {
            $this->destroy();
            return $this->start(); // ເລີ່ມ session ໃໝ່
        }

        // ກວດສອບ session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) { // 30 ນາທີ
            // Regenerate session ID ແລະ ອັບເດດເວລາສ້າງ
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }

        // ກວດສອບ session expiration
        if (
            isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > $this->options['lifetime'])
        ) {
            $this->destroy();
            return $this->start();
        }

        $_SESSION['last_activity'] = time();

        // Regenerate session ID ທຸກໆ 5 ນາທີ
        if (
            !isset($_SESSION['_regenerated']) ||
            time() - $_SESSION['_regenerated'] > 300
        ) {
            $this->regenerate();
            $_SESSION['_regenerated'] = time();
        }

        return true;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $_SESSION = [];
    }

    public function setFlash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, $default = null)
    {
        if ($key === null) {
            $messages = $_SESSION['_flash'] ?? [];
            unset($_SESSION['_flash']);
            return $messages;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    public function getAllFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    public function regenerate(bool $deleteOld = false): bool
    {
        return session_regenerate_id($deleteOld);
    }

    public function destroy(): bool
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        return session_destroy();
    }

    /**
     * ກວດສອບວ່າມີ flash message ຫຼື ບໍ່
     */
    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function __get(string $key)
    {
        return $this->get($key);
    }

    public function __set(string $key, $value): void
    {
        $this->set($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __unset(string $key): void
    {
        $this->remove($key);
    }
}
