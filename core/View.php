<?php
declare(strict_types=1);

class View 
{
    /**
     * ເກັບຂໍ້ມູນທີ່ຈະສົ່ງໄປສະແດງຜົນ
     */
    private array $data = [];

    /**
     * ເກັບ path ຂອງ view files
     */
    private string $viewPath;
    
    /**
     * ເກັບ path ຂອງ layout files
     */
    private string $layoutPath;

    /**
     * Constructor
     */
    public function __construct() 
    {
        $this->viewPath = dirname(__DIR__) . '/views';
        $this->layoutPath = $this->viewPath . '/layouts';
    }

    /**
     * ຕັ້ງຄ່າຂໍ້ມູນທີ່ຈະສົ່ງໄປ view
     */
    public function set(string $key, $value): self 
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * ຕັ້ງຄ່າຂໍ້ມູນຫຼາຍອັນພ້ອມກັນ
     */
    public function setData(array $data): self 
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * ດຶງຂໍ້ມູນ
     */
    public function get(string $key, $default = null) 
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * ດຶງຂໍ້ມູນທັງໝົດ
     */
    public function getData(): array 
    {
        return $this->data;
    }

    /**
     * ສະແດງຜົນ view
     */
    public function render(string $view, ?string $layout = null): string 
    {
        try {
            // ເລີ່ມ output buffering
            ob_start();

            // Extract variables
            extract($this->data);

            // Load view file
            $viewFile = $this->resolveViewPath($view);
            if (!file_exists($viewFile)) {
                throw new RuntimeException("View file not found: {$viewFile}");
            }
            require $viewFile;

            // ດຶງເນື້ອຫາຈາກ view
            $content = ob_get_clean();

            // ຖ້າມີ layout, ໂຫຼດ layout ແລະ ສະແດງຜົນ
            if ($layout !== null) {
                ob_start();
                $layoutFile = $this->resolveLayoutPath($layout);
                if (!file_exists($layoutFile)) {
                    throw new RuntimeException("Layout file not found: {$layoutFile}");
                }
                require $layoutFile;
                return ob_get_clean();
            }

            return $content;

        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * ໂຫຼດແລະສະແດງຜົນ partial view
     */
    public function partial(string $name, array $data = []): string 
    {
        try {
            // ເລີ່ມ output buffering
            ob_start();

            // Merge data
            $originalData = $this->data;
            $this->data = array_merge($this->data, $data);

            // Extract variables
            extract($this->data);

            // Load partial file
            $partialFile = $this->viewPath . '/partials/' . $name . '.php';
            if (!file_exists($partialFile)) {
                throw new RuntimeException("Partial view not found: {$partialFile}");
            }
            require $partialFile;

            // ຄືນຄ່າຂໍ້ມູນເດີມ
            $this->data = $originalData;

            return ob_get_clean();

        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Escape HTML
     */
    public function escape(string $string): string 
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * ສ້າງ URL
     */
    public function url(string $path = '', array $params = []): string 
    {
        return Helper::url($path, $params);
    }

    /**
     * ສ້າງ HTML attributes
     */
    public function attributes(array $attributes): string 
    {
        $html = [];
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html[] = $key;
                }
            } else {
                $html[] = $key . '="' . $this->escape($value) . '"';
            }
        }
        return implode(' ', $html);
    }

    /**
     * Format ວັນທີ
     */
    public function formatDate(string $date, string $format = 'd/m/Y'): string 
    {
        return Helper::formatDate($date, $format);
    }

    /**
     * Format ເງິນ
     */
    public function formatMoney(float $amount, string $currency = 'LAK'): string 
    {
        return number_format($amount, 0, ',', '.') . ' ' . $currency;
    }

    /**
     * ສ້າງ CSRF field
     */
    public function csrf(): string 
    {
        return Helper::csrfField();
    }

    /**
     * ດຶງ view file path
     */
    private function resolveViewPath(string $view): string 
    {
        return $this->viewPath . '/' . $view . '.php';
    }

    /**
     * ດຶງ layout file path
     */
    private function resolveLayoutPath(string $layout): string 
    {
        return $this->layoutPath . '/' . $layout . '.php';
    }
}