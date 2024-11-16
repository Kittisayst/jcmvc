<?php

declare(strict_types=1);

class QueryBuilder
{
    protected Model $model;
    protected ?string $table = null;
    protected array $wheres = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $params = [];
    protected array $joins = [];
    protected array $groups = [];
    protected array $havings = [];
    protected array $selects = ['*'];

    /**
     * Constructor
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->table = $model->getTable();
    }

    /**
     * Select clause
     */
    public function select($columns = ['*']): self
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Where clause
     */
    public function where(string $column, $value, string $operator = '='): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * OrWhere clause
     */
    public function orWhere(string $column, $value, string $operator = '='): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        return $this;
    }

    /**
     * WhereIn clause
     */
    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * WhereBetween clause
     */
    public function whereBetween(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * WhereNull clause
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Join clause
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'inner',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        return $this;
    }

    /**
     * Left Join clause
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'left',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        return $this;
    }

    /**
     * Group By clause
     */
    public function groupBy(string ...$groups): self
    {
        $this->groups = array_merge($this->groups, $groups);
        return $this;
    }

    /**
     * Having clause
     */
    public function having(string $column, $value, string $operator = '='): self
    {
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Order By clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    /**
     * Limit clause
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Offset clause
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * ດຶງຊື່ຕາຕະລາງ
     */
    protected function getTable(): string
    {
        return $this->table ?? $this->model->getTable();
    }

    /**
     * ສ້າງ SELECT query
     */
    protected function buildSelectQuery(): string
    {
        $sql = [];
        $sql[] = "SELECT " . implode(', ', $this->selects);
        $sql[] = "FROM " . $this->getTable();  // ໃຊ້ getTable() method

        // Joins
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $sql[] = strtoupper($join['type']) . " JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        // Where
        if (!empty($this->wheres)) {
            $sql[] = "WHERE " . $this->buildWhereClause();
        }

        // Group By
        if (!empty($this->groups)) {
            $sql[] = "GROUP BY " . implode(', ', $this->groups);
        }

        // Having
        if (!empty($this->havings)) {
            $sql[] = "HAVING " . $this->buildHavingClause();
        }

        // Order By
        if (!empty($this->orders)) {
            $parts = [];
            foreach ($this->orders as $order) {
                $parts[] = "{$order['column']} {$order['direction']}";
            }
            $sql[] = "ORDER BY " . implode(', ', $parts);
        }

        // Limit & Offset
        if ($this->limit !== null) {
            $sql[] = "LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $sql[] = "OFFSET {$this->offset}";
            }
        }

        return implode(' ', $sql);
    }

    /**
     * ສ້າງ WHERE clause
     */
    protected function buildWhereClause(): string
    {
        $parts = [];
        foreach ($this->wheres as $where) {
            $boolean = $where['boolean'];

            if (!empty($parts)) {
                $parts[] = $boolean;
            }

            switch ($where['type']) {
                case 'basic':
                    $parts[] = "{$where['column']} {$where['operator']} ?";
                    $this->params[] = $where['value'];
                    break;

                case 'in':
                    $placeholders = str_repeat('?,', count($where['values']) - 1) . '?';
                    $parts[] = "{$where['column']} IN ($placeholders)";
                    $this->params = array_merge($this->params, $where['values']);
                    break;

                case 'between':
                    $parts[] = "{$where['column']} BETWEEN ? AND ?";
                    $this->params = array_merge($this->params, $where['values']);
                    break;

                case 'null':
                    $parts[] = "{$where['column']} IS NULL";
                    break;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * ສ້າງ HAVING clause
     */
    protected function buildHavingClause(): string
    {
        $parts = [];
        foreach ($this->havings as $having) {
            if (!empty($parts)) {
                $parts[] = $having['boolean'];
            }
            $parts[] = "{$having['column']} {$having['operator']} ?";
            $this->params[] = $having['value'];
        }
        return implode(' ', $parts);
    }

    /**
     * ດຶງຜົນລັບທັງໝົດ
     * @return Model[]
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $results = $this->model->getDatabase()->query($sql, $this->params);

        $className = get_class($this->model);
        return array_map(function ($result) use ($className) {
            return new $className($result);
        }, $results);
    }

    /**
     * ດຶງຜົນລັບດຽວ
     * @return Model|null
     */
    public function first(): ?Model
    {
        $this->limit(1);
        $sql = $this->buildSelectQuery();
        $result = $this->model->getDatabase()->queryOne($sql, $this->params);

        if ($result) {
            $className = get_class($this->model);
            return new $className($result);
        }

        return null;
    }

    /**
     * ດຶງຜົນລັບແບບ paginate
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function paginate(int $perPage = 15, int $page = 1): array 
    {
        $offset = ($page - 1) * $perPage;
        
        // ດຶງຈຳນວນທັງໝົດ
        $total = $this->count();
        
        // ດຶງຂໍ້ມູນໜ້າປັດຈຸບັນ
        $this->limit($perPage)->offset($offset);
        $items = $this->get();
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'items' => $items
        ];
    }

    /**
     * ດຶງຂໍ້ມູນແບບ chunks
     * @param int $size
     * @param callable $callback
     */
    public function chunk(int $size, callable $callback): void 
    {
        $page = 1;
        
        do {
            $results = $this->paginate($size, $page);
            $items = $results['items'];
            
            if (count($items) === 0) {
                break;
            }
            
            if ($callback($items, $page) === false) {
                break;
            }
            
            $page++;
            
        } while (true);
    }

    /**
     * ດຶງຂໍ້ມູນແບບ lazy loading
     * @param int $size
     * @return Generator
     */
    public function lazy(int $size = 1000): Generator 
    {
        $page = 1;
        
        do {
            $results = $this->paginate($size, $page);
            $items = $results['items'];
            
            if (count($items) === 0) {
                break;
            }
            
            foreach ($items as $item) {
                yield $item;
            }
            
            $page++;
            
        } while (true);
    }

    /**
     * ນັບຈຳນວນຜົນລັບ
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM ({$this->buildSelectQuery()}) as sub";
        $result = $this->model->getDatabase()->queryOne($sql, $this->params);
        return (int)$result['count'];
    }

    /**
     * ປ່ຽນຕາຕະລາງຊົ່ວຄາວ
     */
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }
}
