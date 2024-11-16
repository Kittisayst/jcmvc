<?php

abstract class Relation 
{
    protected Model $model;
    protected Model $parent;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(string $model, Model $parent, string $foreignKey, string $localKey) 
    {
        $this->model = new $model();
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    abstract public function get(): mixed;
}