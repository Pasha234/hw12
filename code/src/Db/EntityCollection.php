<?php

namespace Pasha234\Hw12\Db;

use RuntimeException;
use Pasha234\Hw12\Db\EntityInterface;

class EntityCollection
{
    protected $collection = [];

    public function __construct(array $values = [])
    {
        foreach ($values as $key => $value) {
            if (!$value instanceof EntityInterface) {
                throw new RuntimeException("Non-entity value is given in EntityCollection");
            }
        }
        $this->collection = $values;
    }

    public function add(EntityInterface $value): void
    {
        $this->collection[] = $value;
    }

    public function remove(string|int $key): bool
    {
        if (array_key_exists($key, $this->collection)) {
            unset($this->collection[$key]);
            return true;
        }
        return false;
    }

    public function get(string|int $key): ?EntityInterface
    {
        return $this->collection[$key] ?? null;
    }

    public function toArray()
    {
        return $this->collection;
    }
}