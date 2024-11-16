<?php

declare(strict_types=1);

abstract class Model implements ArrayAccess
{
    protected Database $db;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['id'];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $dates = ['created_at', 'updated_at'];
    protected array $attributes = [];
    protected array $original = [];
    protected array $changes = [];
    protected array $relations = [];
    protected bool $timestamps = true;
    protected static array $booted = [];

    /**
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        $this->bootIfNotBooted();
        $this->fill($attributes);
    }

    /**
     * Boot ຖ້າຍັງບໍ່ໄດ້ boot
     */
    protected function bootIfNotBooted(): void
    {
        $class = get_class($this);
        if (!isset(static::$booted[$class])) {
            static::$booted[$class] = true;
            $this->boot();
        }
    }

    /**
     * Boot method ທີ່ຈະຖືກ override ໂດຍ child classes
     */
    protected function boot(): void
    {
        // Override ໃນ child classes ຖ້າຕ້ອງການ
    }

    /**
     * ດຶງ Database instance
     */
    public function getDatabase(): Database
    {
        return $this->db;
    }

    /**
     * ຕັ້ງຊື່ຕາຕະລາງອັດຕະໂນມັດ
     */
    public  function getTable(): string
    {
        if (empty($this->table)) {
            // ຕັ້ງຊື່ຕາຕະລາງອັດຕະໂນມັດ
            $this->table = Helper::snakeCase(Helper::classBasename($this)) . 's';
        }
        return $this->table;
    }

    /**
     * ຕັ້ງຊື່ຕາຕະລາງ
     */
    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * ເຕີມຂໍ້ມູນ
     */
    public function fill(array $attributes): self
    {
        foreach ($this->filterFillable($attributes) as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * ກັ່ນຕອງ attributes ທີ່ອະນຸຍາດ
     */
    protected function filterFillable(array $attributes): array
    {
        if (empty($this->fillable)) {
            return array_diff_key($attributes, array_flip($this->guarded));
        }
        return array_intersect_key($attributes, array_flip($this->fillable));
    }

    /**
     * ຕັ້ງຄ່າ attribute
     */
    public function setAttribute(string $key, $value): void
    {
        // ເກັບຄ່າເກົ່າ
        if (!array_key_exists($key, $this->original)) {
            $this->original[$key] = $value;
        }

        // ກວດຫາ mutator
        $mutator = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $value = $this->{$mutator}($value);
        } elseif (in_array($key, $this->dates)) {
            $value = $this->asDateTime($value);
        } elseif (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }

        $this->attributes[$key] = $value;

        // ບັນທຶກການປ່ຽນແປງ
        if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
            $this->changes[$key] = $value;
        }
    }

    /**
     * ດຶງຄ່າ attribute
     */
    public function getAttribute(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            $value = $this->attributes[$key];

            // ກວດຫາ accessor
            $accessor = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
            if (method_exists($this, $accessor)) {
                return $this->{$accessor}($value);
            }

            return $value;
        }

        // ກວດຫາ relation
        if (method_exists($this, $key)) {
            return $this->getRelationValue($key);
        }

        return null;
    }

    /**
     * ແປງວັນທີເປັນ DateTime object
     */
    protected function asDateTime($value): ?DateTime
    {
        if ($value instanceof DateTime) {
            return $value;
        }
        if (is_string($value)) {
            return new DateTime($value);
        }
        return null;
    }

    /**
     * Cast attribute
     */
    protected function castAttribute(string $key, $value)
    {
        if ($value === null) {
            return null;
        }

        switch ($this->casts[$key]) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
                return is_string($value) ? json_decode($value, true) : (array) $value;
            case 'json':
                return json_encode($value);
            case 'date':
                return $this->asDateTime($value)->format('Y-m-d');
            case 'datetime':
                return $this->asDateTime($value);
            default:
                return $value;
        }
    }

    /**
     * ບັນທຶກໂມເດວ
     */
    public function save(): bool
    {
        // ຕັ້ງເວລາ timestamps
        if ($this->timestamps) {
            $now = new DateTime();
            if (empty($this->attributes[$this->primaryKey])) {
                $this->setAttribute('created_at', $now);
            }
            $this->setAttribute('updated_at', $now);
        }

        if (isset($this->attributes[$this->primaryKey])) {
            return $this->update();
        }

        return $this->insert();
    }

    /**
     * ບັນທຶກຂໍ້ມູນໃໝ່
     */
    protected function insert(): bool
    {
        try {
            $this->db->beginTransaction();

            $attributes = $this->getAttributes();
            $fields = array_keys($attributes);
            $values = array_values($attributes);
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->getTable(),
                implode(',', $fields),
                $placeholders
            );

            if ($this->db->execute($sql, $values)) {
                $this->attributes[$this->primaryKey] = $this->db->lastInsertId();
                $this->db->commit();
                $this->syncOriginal();
                return true;
            }

            $this->db->rollback();
            return false;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new ModelException("Failed to insert record: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * ອັບເດດຂໍ້ມູນ
     */
    protected function update(): bool
    {
        if (empty($this->changes)) {
            return true;
        }

        try {
            $this->db->beginTransaction();

            $fields = [];
            $values = [];
            foreach ($this->changes as $field => $value) {
                $fields[] = "{$field} = ?";
                $values[] = $value;
            }

            // ເພີ່ມ primary key ສຳລັບ WHERE clause
            $values[] = $this->attributes[$this->primaryKey];

            $sql = sprintf(
                "UPDATE %s SET %s WHERE %s = ?",
                $this->getTable(),
                implode(',', $fields),
                $this->primaryKey
            );

            if ($this->db->execute($sql, $values)) {
                $this->db->commit();
                $this->syncOriginal();
                return true;
            }

            $this->db->rollback();
            return false;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new ModelException("Failed to update record: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * ລຶບຂໍ້ມູນ
     */
    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $sql = sprintf(
                "DELETE FROM %s WHERE %s = ?",
                $this->getTable(),
                $this->primaryKey
            );

            if ($this->db->execute($sql, [$this->attributes[$this->primaryKey]])) {
                $this->db->commit();
                return true;
            }

            $this->db->rollback();
            return false;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new ModelException("Failed to delete record: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Sync original attributes
     */
    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
        $this->changes = [];
    }

    /**
     * ດຶງຂໍ້ມູນດ້ວຍ primary key
     * @param mixed $id
     * @return static|null
     */
    public static function find($id)  // ເອົາ ?static ອອກ
    {
        $instance = new static();
        $result = $instance->db->queryOne(
            sprintf(
                "SELECT * FROM %s WHERE %s = ?",
                $instance->getTable(),
                $instance->primaryKey
            ),
            [$id]
        );

        if ($result) {
            return new static($result);
        }

        return null;
    }

    /**
     * ດຶງຂໍ້ມູນທັງໝົດ
     * @return static[]
     */
    public static function all(): array
    {
        $instance = new static();
        $results = $instance->db->query("SELECT * FROM " . $instance->getTable());

        return array_map(function ($result) {
            return new static($result);
        }, $results);
    }

    /**
     * Query builder: WHERE clause
     */
    public static function where(string $column, $value, string $operator = '='): QueryBuilder
    {
        $instance = new static();
        return (new QueryBuilder($instance))
            ->where($column, $value, $operator);
    }

    /**
     * ດຶງ attributes ທັງໝົດ
     */
    public function getAttributes(): array
    {
        return array_diff_key(
            $this->attributes,
            array_flip($this->hidden)
        );
    }

    /**
     * ດຶງຄ່າທີ່ປ່ຽນແປງ
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * ArrayAccess implementation
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * ແປງເປັນ array
     */
    public function toArray(): array
    {
        $attributes = $this->getAttributes();

        foreach ($this->relations as $key => $value) {
            if ($value instanceof self) {
                $attributes[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $attributes[$key] = array_map(function ($item) {
                    return $item instanceof self ? $item->toArray() : $item;
                }, $value);
            }
        }

        return $attributes;
    }

    /**
     * ດຶງ primary key
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * ກຳນົດການເຊື່ອມຕໍ່ database
     */
    public function setDatabase(Database $database): void
    {
        $this->db = $database;
    }

    /**
     * ດຶງຂໍ້ມູນ relation
     */
    protected function getRelationValue(string $key)
    {
        // ກວດສອບວ່າມີ relation ແລ້ວບໍ່
        if (isset($this->relations[$key])) {
            return $this->relations[$key];
        }

        // ເອີ້ນໃຊ້ relation method
        if (method_exists($this, $key)) {
            return $this->relations[$key] = $this->{$key}()->get();
        }

        return null;
    }

    /**
     * HasMany relationship
     */
    protected function hasMany(string $relatedClass, string $foreignKey = null, string $localKey = null): HasMany
    {
        $foreignKey = $foreignKey ?? Helper::snakeCase(Helper::classBasename($this)) . '_id';
        $localKey = $localKey ?? $this->primaryKey;

        return new HasMany($relatedClass, $this, $foreignKey, $localKey);
    }

    /**
     * HasOne relationship
     */
    protected function hasOne(string $relatedClass, string $foreignKey = null, string $localKey = null): HasOne
    {
        $foreignKey = $foreignKey ?? Helper::snakeCase(Helper::classBasename($this)) . '_id';
        $localKey = $localKey ?? $this->primaryKey;

        return new HasOne($relatedClass, $this, $foreignKey, $localKey);
    }

    /**
     * BelongsTo relationship
     */
    protected function belongsTo(string $relatedClass, string $foreignKey = null, string $ownerKey = null): BelongsTo
    {
        $foreignKey = $foreignKey ?? Helper::snakeCase(Helper::classBasename($relatedClass)) . '_id';
        $ownerKey = $ownerKey ?? (new $relatedClass)->getPrimaryKey();

        return new BelongsTo($relatedClass, $this, $foreignKey, $ownerKey);
    }
}

/**
 * Model Exception class
 */
class ModelException extends Exception {}
