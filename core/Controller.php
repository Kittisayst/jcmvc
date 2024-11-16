<?php

declare(strict_types=1);

abstract class Controller
{
    protected array $data = [];
    protected ?string $layout = 'default';
    protected ?View $view = null;
    protected ?Request $request = null;
    protected ?Response $response = null;
    protected ?Session $session = null;
    protected ?Database $db = null;
    protected array $middleware = [];

    public function __construct()
    {
        // ດຶງ instances ທີ່ຈຳເປັນ
        $app = App::getInstance();
        $this->request = new Request();
        $this->response = new Response();
        $this->session = $app->getSession();
        $this->db = $app->getDatabase();
        $this->view = new View();

        // ເອີ້ນໃຊ້ initialization method
        $this->initialize();
    }

    /**
     * Method ສຳລັບ initialization ທີ່ຈະຖືກ override ໂດຍ child classes
     */
    protected function initialize(): void
    {
        // Override ໃນ child classes ຖ້າຕ້ອງການ
    }

    /**
     * ສົ່ງຂໍ້ມູນໄປສະແດງຜົນທີ່ view
     */
    protected function render(string $view, array $data = []): void
    {
        try {
            // ລວມຂໍ້ມູນທີ່ສົ່ງມາກັບຂໍ້ມູນທີ່ມີຢູ່ແລ້ວ
            $this->data = array_merge($this->data, $data);

            // ເພີ່ມຂໍ້ມູນພື້ນຖານ
            $this->data['request'] = $this->request;
            $this->data['session'] = $this->session;
            // ດຶງ flash messages ທັງໝົດ
            $flashes = [];
            foreach(['success', 'error', 'warning', 'info'] as $type) {
                if ($message = $this->session->getFlash($type)) {
                    $flashes[$type] = $message;
                }
            }
            $this->data['flash'] = $flashes;

            // ແປງຂໍ້ມູນໃຫ້ເປັນຕົວແປແຍກ
            extract($this->data);

            // ກວດສອບວ່າມີໄຟລ໌ view ຫຼື ບໍ່
            $viewFile = $this->resolveViewPath($view);
            if (!file_exists($viewFile)) {
                throw new RuntimeException("View file not found: {$viewFile}");
            }

            // ເລີ່ມເກັບ output
            ob_start();

            // ໂຫຼດ view
            require $viewFile;

            // ເກັບເນື້ອຫາຈາກ view
            $content = ob_get_clean();

            // ໂຫຼດ layout ຖ້າມີ
            if ($this->layout !== null) {
                $layoutFile = $this->resolveLayoutPath($this->layout);
                if (!file_exists($layoutFile)) {
                    throw new RuntimeException("Layout file not found: {$layoutFile}");
                }
                require $layoutFile;
            } else {
                echo $content;
            }
        } catch (Throwable $e) {
            // ລຶບຂໍ້ມູນທີ່ຄ້າງຢູ່ໃນ buffer
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * ດຶງ path ຂອງ view file
     */
    protected function resolveViewPath(string $view): string
    {
        return dirname(__DIR__) . "/views/{$view}.php";
    }

    /**
     * ດຶງ path ຂອງ layout file
     */
    protected function resolveLayoutPath(string $layout): string
    {
        return dirname(__DIR__) . "/views/layouts/{$layout}.php";
    }

    /**
     * ສົ່ງຂໍ້ມູນກັບໄປໃນຮູບແບບ JSON
     */
    protected function json($data, int $status = 200): void
    {
        $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setStatusCode($status)
            ->setContent(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->send();
    }

    /**
     * Redirect ໄປຫາ URL ໃໝ່
     */
    protected function redirect(string $url, int $status = 302): void
    {
        // ຖ້າມີ flash messages, ບັນທຶກໄວ້ກ່ອນ redirect
        if (!empty($this->data['flash'])) {
            foreach ($this->data['flash'] as $key => $message) {
                $this->session->setFlash($key, $message);
            }
        }

        $this->response
            ->setHeader('Location', Helper::url($url))
            ->setStatusCode($status)
            ->send();
    }

    /**
     * ສົ່ງ flash message
     */
    protected function setFlash(string $type, string $message): void
    {
        $this->session->setFlash($type, $message);
        // ເກັບໄວ້ໃນ data ເພື່ອໃຊ້ໃນ view ປັດຈຸບັນ
        $this->data['flash'][$type] = $message;
    }

    protected function getFlash(string $type): ?string
    {
        return $this->session->getFlash($type);
    }

    protected function hasFlash(string $type): bool
    {
        return $this->session->hasFlash($type);
    }

    /**
     * ຕັ້ງຄ່າຂໍ້ມູນທີ່ຈະສົ່ງໄປ view
     */
    protected function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * ປ່ຽນ layout ທີ່ຈະໃຊ້
     */
    protected function setLayout(?string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * ສ້າງ URL
     */
    protected function url(string $path = '', array $params = []): string
    {
        return Helper::url($path, $params);
    }

    /**
     * ກວດສອບວ່າແມ່ນ AJAX request ຫຼື ບໍ່
     */
    protected function isAjax(): bool
    {
        return $this->request->isAjax();
    }

    /**
     * ດຶງຄ່າ POST
     */
    protected function getPost(string $key = null, $default = null)
    {
        return $key === null ?
            $this->request->getPost() :
            $this->request->getPost($key, $default);
    }

    /**
     * ດຶງຄ່າ GET
     */
    protected function getQuery(string $key = null, $default = null)
    {
        return $key === null ?
            $this->request->getQuery() :
            $this->request->getQuery($key, $default);
    }

    /**
     * ກວດສອບວ່າຜູ້ໃຊ້ login ແລ້ວຫຼືບໍ່
     */
    protected function isLoggedIn(): bool
    {
        return (bool)$this->session->get('user_id');
    }

    /**
     * ດຶງຂໍ້ມູນຜູ້ໃຊ້ປັດຈຸບັນ
     */
    protected function getCurrentUser(): ?array
    {
        return $this->session->get('user');
    }

    /**
     * ກວດສອບ CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->request->getPost('csrf_token');
        return Helper::validateCsrfToken($token);
    }

    /**
     * ສ້າງ CSRF input field
     */
    protected function csrfField(): string
    {
        return Helper::csrfField();
    }

    /**
     * ບັນທຶກ log
     */
    protected function log(string $message, string $level = 'info'): void
    {
        Helper::log($message, $level);
    }

    /**
     * Format ຂໍ້ຄວາມເພື່ອປ້ອງກັນ XSS
     */
    protected function escape(string $string): string
    {
        return Helper::escape($string);
    }
}
