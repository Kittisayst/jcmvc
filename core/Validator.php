<?php

class Validator
{
    // ເກັບ error messages
    private array $errors = [];

    // ເກັບຂໍ້ມູນທີ່ຈະກວດສອບ
    private array $data = [];

    // ເກັບກົດເກນທີ່ກຳນົດ
    private array $rules = [];

    // ຮູບແບບກວດສອບທົ່ວໄປ
    private array $patterns = [
        'alpha' => '/^[a-zA-Z]+$/',
        'alpha_num' => '/^[a-zA-Z0-9]+$/',
        'alpha_dash' => '/^[a-zA-Z0-9_-]+$/',
        'alpha_space' => '/^[a-zA-Z\s]+$/',
        'phone_lao' => '/^(\+856|0)[0-9]{9,10}$/',
        'username' => '/^[a-zA-Z0-9_]{3,20}$/',
        'password' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/'
    ];

    // ຂໍ້ຄວາມ error ເປັນພາສາລາວ
    private array $messages = [
        'required' => ':field ບໍ່ສາມາດຫວ່າງໄດ້',
        'email' => ':field ຕ້ອງເປັນອີເມວທີ່ຖືກຕ້ອງ',
        'min' => ':field ຕ້ອງມີຄ່າຫຼາຍກວ່າ :min',
        'max' => ':field ຕ້ອງມີຄ່ານ້ອຍກວ່າ :max',
        'numeric' => ':field ຕ້ອງເປັນຕົວເລກເທົ່ານັ້ນ',
        'integer' => ':field ຕ້ອງເປັນຈຳນວນເຕັມເທົ່ານັ້ນ',
        'float' => ':field ຕ້ອງເປັນຕົວເລກທົດສະນິຍົມເທົ່ານັ້ນ',
        'date' => ':field ຕ້ອງເປັນວັນທີທີ່ຖືກຕ້ອງ',
        'datetime' => ':field ຕ້ອງເປັນວັນທີ ແລະ ເວລາທີ່ຖືກຕ້ອງ',
        'in' => ':field ຕ້ອງເປັນໜຶ່ງໃນ :values',
        'not_in' => ':field ຕ້ອງບໍ່ເປັນໜຶ່ງໃນ :values',
        'unique' => ':field ນີ້ມີໃນລະບົບແລ້ວ',
        'exists' => ':field ບໍ່ມີໃນລະບົບ',
        'min_length' => ':field ຕ້ອງມີຕົວອັກສອນຢ່າງໜ້ອຍ :min ຕົວ',
        'max_length' => ':field ຕ້ອງມີຕົວອັກສອນບໍ່ເກີນ :max ຕົວ',
        'between_length' => ':field ຕ້ອງມີຕົວອັກສອນລະຫວ່າງ :min ຫາ :max ຕົວ',
        'match' => ':field ບໍ່ກົງກັບ :other',
        'alpha' => ':field ຕ້ອງເປັນຕົວອັກສອນເທົ່ານັ້ນ',
        'alpha_num' => ':field ຕ້ອງເປັນຕົວອັກສອນ ຫຼື ຕົວເລກເທົ່ານັ້ນ',
        'alpha_dash' => ':field ຕ້ອງເປັນຕົວອັກສອນ, ຕົວເລກ, ຂີດ(-) ຫຼື ຂີດລຸ່ມ(_)ເທົ່ານັ້ນ',
        'alpha_space' => ':field ຕ້ອງເປັນຕົວອັກສອນ ແລະ ຊ່ອງວ່າງເທົ່ານັ້ນ',
        'phone_lao' => ':field ຕ້ອງເປັນເບີໂທລາວທີ່ຖືກຕ້ອງ',
        'username' => ':field ຕ້ອງມີ 3-20 ຕົວອັກສອນ, ເປັນຕົວອັກສອນພາສາອັງກິດ, ຕົວເລກ ຫຼື ຂີດລຸ່ມເທົ່ານັ້ນ',
        'password' => ':field ຕ້ອງມີຢ່າງໜ້ອຍ 8 ຕົວອັກສອນ, ມີຕົວພິມໃຫຍ່, ຕົວພິມນ້ອຍ ແລະ ຕົວເລກ',
        'url' => ':field ຕ້ອງເປັນ URL ທີ່ຖືກຕ້ອງ',
        'ip' => ':field ຕ້ອງເປັນ IP address ທີ່ຖືກຕ້ອງ',
        'file' => ':field ຕ້ອງເປັນໄຟລ໌',
        'uploaded' => ':field ຕ້ອງເປັນໄຟລ໌ທີ່ອັບໂຫຼດ',
        'image' => ':field ຕ້ອງເປັນຮູບພາບ',
        'size' => ':field ຕ້ອງມີຂະໜາດ :size KB',
        'between' => ':field ຕ້ອງມີຄ່າລະຫວ່າງ :min ຫາ :max',
        'before' => ':field ຕ້ອງເປັນວັນທີກ່ອນ :date',
        'after' => ':field ຕ້ອງເປັນວັນທີຫຼັງ :date'
    ];

    private string $dateFormat = 'Y-m-d';
    private string $dateTimeFormat = 'Y-m-d H:i:s';

    /**
     * Constructor
     * @param array $data ຂໍ້ມູນທີ່ຈະກວດສອບ
     * @param array $rules ກົດເກນການກວດສອບ
     * @param array $messages ຂໍ້ຄວາມ error ທີ່ກຳນົດເອງ
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = array_merge($this->messages, $messages);
    }

    /**
     * ກວດສອບຂໍ້ມູນ
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $rules = $this->parseRules($rules);
            $value = $this->getValue($field);
            $label = $this->getLabel($field);

            foreach ($rules as $rule => $parameter) {
                $method = 'validate' . ucfirst($rule);
                
                if (method_exists($this, $method)) {
                    if (!$this->$method($field, $value, $parameter, $label)) {
                        break;  // ຖ້າກວດພົບຂໍ້ຜິດພາດໃຫ້ຂ້າມໄປກວດ field ຕໍ່ໄປ
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * ແປງກົດເກນໃຫ້ເປັນ array
     */
    private function parseRules($rules): array
    {
        if (is_string($rules)) {
            $parsed = [];
            $rulesArray = explode('|', $rules);
            
            foreach ($rulesArray as $rule) {
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $parameter] = explode(':', $rule);
                    $parsed[$ruleName] = explode(',', $parameter);
                } else {
                    $parsed[$rule] = [];
                }
            }
            
            return $parsed;
        }
        
        return $rules;
    }

    /**
     * ດຶງຄ່າຈາກຂໍ້ມູນ
     */
    private function getValue($field)
    {
        return $this->data[$field] ?? null;
    }

    /**
     * ດຶງ label ຂອງ field
     */
    private function getLabel($field): string
    {
        // ປ່ຽນ snake_case ເປັນຄຳເວົ້າ
        return ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * ເພີ່ມຂໍ້ຜິດພາດ
     */
    private function addError($field, $rule, $parameters = []): void
    {
        $message = $this->messages[$rule] ?? "ຂໍ້ມູນ $field ບໍ່ຖືກຕ້ອງ";
        $label = $this->getLabel($field);

        // ແທນທີ່ :field ດ້ວຍ label
        $message = str_replace(':field', $label, $message);

        // ແທນທີ່ parameters
        foreach ($parameters as $key => $value) {
            $message = str_replace(':' . $key, $value, $message);
        }

        $this->errors[$field][] = $message;
    }

    /**
     * ກວດສອບຄ່າວ່າງ
     */
    private function validateRequired($field, $value): bool
    {
        $valid = $value !== null && $value !== '' && $value !== [];
        
        if (!$valid) {
            $this->addError($field, 'required');
        }
        
        return $valid;
    }

    /**
     * ກວດສອບອີເມວ
     */
    private function validateEmail($field, $value): bool
    {
        if (empty($value)) return true;

        $valid = filter_var($value, FILTER_VALIDATE_EMAIL);
        
        if (!$valid) {
            $this->addError($field, 'email');
        }
        
        return $valid;
    }

    // ເພີ່ມການກວດສອບອື່ນໆຕາມຕ້ອງການ...

    /**
     * ດຶງຂໍ້ຜິດພາດທັງໝົດ
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * ດຶງຂໍ້ຜິດພາດຂອງ field ໃດໜຶ່ງ
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * ດຶງຂໍ້ຜິດພາດທຳອິດຂອງ field
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * ດຶງຂໍ້ຜິດພາດທັງໝົດໃນຮູບແບບຂໍ້ຄວາມດຽວ
     */
    public function getErrorMessages(): array 
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return $messages;
    }

    /**
     * ຕັ້ງຄ່າຮູບແບບວັນທີ
     */
    public function setDateFormat(string $format): void 
    {
        $this->dateFormat = $format;
    }

    /**
     * ຕັ້ງຄ່າຮູບແບບວັນທີ ແລະ ເວລາ
     */
    public function setDateTimeFormat(string $format): void 
    {
        $this->dateTimeFormat = $format;
    }
}