<?php

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;
    private array $headers;
    private $rawBody;
    private array $parsedBody;
    private array $attributes = [];

    /**
     * Constructor
     * ສ້າງ instance ໃໝ່ຂອງ Request
     */
    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->getRequestHeaders();
        $this->rawBody = $this->getRawBody();
        $this->parsedBody = $this->parseBody();
    }

    /**
     * ດຶງ request headers ທັງໝົດ
     */
    private function getRequestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * ດຶງ raw request body
     */
    private function getRawBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Parse request body ຕາມ content type
     */
    private function parseBody(): array
    {
        $contentType = $this->getHeader('Content-Type');

        if (empty($this->rawBody)) {
            return [];
        }

        if (strpos($contentType, 'application/json') !== false) {
            return json_decode($this->rawBody, true) ?: [];
        }

        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($this->rawBody, $data);
            return $data;
        }

        return [];
    }

    /**
     * ດຶງ HTTP method
     */
    public function getMethod(): string
    {
        $method = $this->server['REQUEST_METHOD'];

        // ກວດ X-HTTP-Method-Override header
        if ($method === 'POST') {
            if ($override = $this->getHeader('X-HTTP-Method-Override')) {
                return strtoupper($override);
            }
            // ກວດ _method field ໃນ POST data
            if ($override = $this->post('_method')) {
                return strtoupper($override);
            }
        }

        return $method;
    }

    /**
     * ກວດວ່າແມ່ນ HTTPS request ຫຼື ບໍ່
     */
    public function isSecure(): bool
    {
        if (isset($this->server['HTTPS'])) {
            return $this->server['HTTPS'] !== 'off';
        }
        return $this->server['SERVER_PORT'] == 443;
    }

    /**
     * ກວດວ່າແມ່ນ AJAX request ຫຼື ບໍ່
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * ດຶງ request URI
     */
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'];
    }

    /**
     * ດຶງ request path (without query string)
     */
    public function getPath(): string
    {
        $uri = $this->getUri();
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return $uri;
    }

    /**
     * ດຶງ query string
     */
    public function getQueryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    /**
     * ດຶງ client IP address
     */
    public function getClientIp(): ?string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (isset($this->server[$key])) {
                foreach (explode(',', $this->server[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                    )) {
                        return $ip;
                    }
                }
            }
        }

        return null;
    }

    /**
     * ດຶງຄ່າຈາກ GET parameters
     */
    public function get(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * ດຶງຄ່າຈາກ POST data
     */
    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * ດຶງຄ່າຈາກ POST data
     * @param string|null $key Key to get from POST data
     * @param mixed $default Default value if key doesn't exist
     * @return mixed POST value or entire POST array if no key specified
     */
    public function getPost(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * ດຶງຄ່າຈາກ GET parameters
     * @param string|null $key Key to get from GET data
     * @param mixed $default Default value if key doesn't exist
     * @return mixed GET value or entire GET array if no key specified
     */
    public function getQuery(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    /**
     * ດຶງຄ່າຈາກ parsed request body
     */
    public function input(string $key, $default = null)
    {
        return $this->parsedBody[$key] ?? $default;
    }

    private function filterInput($value) 
{
    if (is_array($value)) {
        return array_map([$this, 'filterInput'], $value);
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

    /**
     * ດຶງຄ່າຈາກ uploaded files
     */
    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    /**
     * ກວດວ່າມີໄຟລ໌ upload ຫຼື ບໍ່
     */
    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return $file && $file['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * ດຶງຄ່າຈາກ cookies
     */
    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * ດຶງຄ່າຈາກ headers
     */
    public function getHeader(string $key, $default = null)
    {
        // ບໍ່ຕ້ອງກວດສອບ case
        $key = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $key))));
        return $this->headers[$key] ?? $default;
    }

    /**
     * ດຶງຄ່າ server parameter
     */
    public function server(string $key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * ດຶງ raw request body
     */
    public function getBody(): string
    {
        return $this->rawBody;
    }

    /**
     * ດຶງ parsed request body
     */
    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    /**
     * ຕັ້ງຄ່າ request attribute
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * ດຶງຄ່າ request attribute
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * ກວດວ່າມີ request attribute ຫຼື ບໍ່
     */
    public function hasAttribute(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * ລຶບ request attribute
     */
    public function removeAttribute(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * ດຶງ request host
     */
    public function getHost(): string
    {
        if (!empty($this->server['HTTP_HOST'])) {
            return $this->server['HTTP_HOST'];
        }

        if (!empty($this->server['SERVER_NAME'])) {
            return $this->server['SERVER_NAME'];
        }

        return $this->server['SERVER_ADDR'];
    }

    /**
     * ດຶງ user agent
     */
    public function getUserAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * ດຶງ request language
     */
    public function getLanguage(): ?string
    {
        return $this->server['HTTP_ACCEPT_LANGUAGE'] ?? null;
    }
}
