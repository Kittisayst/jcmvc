<?php
class HasMany extends Relation
{
    /**
     * ດຶງຂໍ້ມູນທັງໝົດ
     * @return Model[]
     */
    public function get(): array
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = ?",
            $this->model->getTable(),
            $this->foreignKey
        );

        $results = $this->model->getDatabase()->query($sql, [
            $this->parent->getAttribute($this->localKey)
        ]);

        $className = get_class($this->model);  // ດຶງຊື່ class

        return array_map(function ($result) use ($className) {
            return new $className($result);  // ສ້າງ instance ດ້ວຍຊື່ class
        }, $results);
    }

    /**
     * ດຶງຂໍ້ມູນແບບ eager loading
     */
    public function with(string ...$relations): self
    {
        // TODO: implement eager loading
        return $this;
    }

    /**
     * ດຶງຂໍ້ມູນແບບ lazy
     */
    public function lazy(int $chunk = 100): Generator
    {
        $offset = 0;

        while (true) {
            $sql = sprintf(
                "SELECT * FROM %s WHERE %s = ? LIMIT %d OFFSET %d",
                $this->model->getTable(),
                $this->foreignKey,
                $chunk,
                $offset
            );

            $results = $this->model->getDatabase()->query($sql, [
                $this->parent->getAttribute($this->localKey)
            ]);

            if (empty($results)) {
                break;
            }

            $className = get_class($this->model);
            foreach ($results as $result) {
                yield new $className($result);
            }

            $offset += $chunk;
        }
    }
}
