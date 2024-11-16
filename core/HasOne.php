<?php
class HasOne extends Relation
{
    /**
     * ດຶງຂໍ້ມູນ
     */
    public function get(): ?Model
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = ? LIMIT 1",
            $this->model->getTable(),
            $this->foreignKey
        );

        $result = $this->model->getDatabase()->queryOne($sql, [
            $this->parent->getAttribute($this->localKey)
        ]);

        if ($result) {
            $className = get_class($this->model);
            return new $className($result);
        }

        return null;
    }
}
