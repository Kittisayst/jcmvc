<?php

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';
    private array $cookies = [];
    private bool $sent = false;

    // HTTP status codes ທີ່ໃຊ້ເລື້ອຍໆ
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_SERVICE_UNAVAILABLE = 503;

    // Content types ທີ່ໃຊ້ເລື້ອຍໆ
    public const CONTENT_TYPE_HTML = 'text/html';
    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_XML = 'application/xml';
    public const CONTENT_TYPE_TEXT = 'text/plain';
    public const CONTENT_TYPE_PDF = 'application/pdf';

    /**
     * ຕັ້ງຄ່າ HTTP status code
     */
    public function setStatusCode(int $code): self
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException('Invalid HTTP status code');
        }
        $this->statusCode = $code;
        return $this;
    }

    /**
     * ດຶງ HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * ຕັ້ງຄ່າ response header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * ຕັ້ງຄ່າຫຼາຍ headers ພ້ອມກັນ
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * ລຶບ header
     */
    public function removeHeader(string $name): self
    {
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * ຕັ້ງຄ່າ content type
     */
    public function setContentType(string $contentType, string $charset = 'UTF-8'): self
    {
        return $this->setHeader('Content-Type', $contentType . '; charset=' . $charset);
    }

    /**
     * ຕັ້ງຄ່າ response content
     */
    public function setContent($content): self
    {
        if (is_array($content) || is_object($content)) {
            $this->content = json_encode($content);
            $this->setContentType(self::CONTENT_TYPE_HTML);
        } else {
            $this->content = (string) $content;
        }
        return $this;
    }

    /**
     * ດຶງ response content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * ຕັ້ງຄ່າ cookie
     */
    public function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): self {
        $this->cookies[$name] = [
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite
        ];
        return $this;
    }

    /**
     * ລຶບ cookie
     */
    public function removeCookie(string $name, string $path = '/', string $domain = ''): self
    {
        return $this->setCookie($name, '', time() - 3600, $path, $domain);
    }

    /**
     * ສົ່ງ redirect response
     */
    public function redirect(string $url, int $statusCode = self::HTTP_FOUND): void
    {
        $this->setHeader('Location', $url);
        $this->setStatusCode($statusCode);
        $this->send();
    }

    /**
     * ສົ່ງ JSON response
     */
    public function json($data, int $statusCode = self::HTTP_OK): void
    {
        $this->setContentType(self::CONTENT_TYPE_JSON)
            ->setStatusCode($statusCode)
            ->setContent(json_encode($data));
        $this->send();
    }

    /**
     * ສົ່ງ file download response
     */
    public function download(
        string $filePath,
        string $fileName = null,
        bool $inline = false
    ): void {
        if (!is_readable($filePath)) {
            throw new RuntimeException("File not found or not readable: $filePath");
        }

        $fileName = $fileName ?? basename($filePath);
        $disposition = $inline ? 'inline' : 'attachment';
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', "$disposition; filename=\"$fileName\"")
            ->setHeader('Content-Length', filesize($filePath));

        $this->sendHeaders();
        readfile($filePath);
        exit;
    }

    /**
     * ສົ່ງ error response
     */
    public function error(
        string $message,
        int $statusCode = self::HTTP_INTERNAL_SERVER_ERROR,
        array $details = []
    ): void {
        $data = [
            'error' => true,
            'message' => $message,
            'code' => $statusCode
        ];

        if (!empty($details)) {
            $data['details'] = $details;
        }

        $this->json($data, $statusCode);
    }

    /**
     * ສົ່ງ success response
     */
    public function success($data, string $message = 'Success'): void
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        $this->json($response);
    }

    /**
     * ສົ່ງ response headers
     */
    private function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        // ສົ່ງ status code
        http_response_code($this->statusCode);

        // ສົ່ງ headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // ສົ່ງ cookies
        foreach ($this->cookies as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                [
                    'expires' => $cookie['expire'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httpOnly'],
                    'samesite' => $cookie['sameSite']
                ]
            );
        }
    }

    /**
     * ສົ່ງ response
     */
    public function send(): void
    {
        if ($this->sent) {
            throw new RuntimeException('Response has already been sent');
        }

        // ເພີ່ມ security headers
        $this->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->setHeader('X-XSS-Protection', '1; mode=block');
        $this->setHeader('X-Content-Type-Options', 'nosniff');

        $this->sendHeaders();
        echo $this->content;
        $this->sent = true;
    }

    /**
     * ກວດສອບວ່າ response ຖືກສົ່ງແລ້ວຫຼືຍັງ
     */
    public function isSent(): bool
    {
        return $this->sent;
    }
}
