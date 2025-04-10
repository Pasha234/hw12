<?php

namespace Pasha234\Hw12\Db;

use PDO;
use PDOStatement;

abstract class DataMapper
{
    protected PDO $pdo;
    protected PDOStatement $selectStatement;
    protected PDOStatement $insertStatement;
    protected PDOStatement $updateStatement;
    protected PDOStatement $deleteStatement;
    protected array $fillable = [];
    protected string $entityClass;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $table = $this->getTable();
        $insertFillableFields = implode(", ", $this->fillable);
        $insertFillableValues = implode(", ", array_map(function(string $item) {
            return ":{$item}";
        }, $this->fillable));

        $this->selectStatement = $pdo->prepare(
            "SELECT * FROM {$table} WHERE id = :id"
        );
        $this->insertStatement = $pdo->prepare(
            "INSERT INTO {$table} ($insertFillableFields) VALUES ($insertFillableValues)"
        );
        $this->deleteStatement = $pdo->prepare(
            "DELETE FROM {$table} WHERE id = :id"
        );
    }

    public function find(int $id): ?EntityInterface
    {
        $this->selectStatement->setFetchMode(PDO::FETCH_ASSOC);
        $this->selectStatement->execute([$id]);
        
        $result = $this->selectStatement->fetch();

        return new $this->entityClass($result);
    }

    public function findAll(): EntityCollection
    {
        $selectAllStatement = $this->pdo->prepare(
            "SELECT * FROM {$this->getTable()}"
        );

        $selectAllStatement->execute();

        $values = array_map(function(array $item) {
            return new $this->entityClass($item);
        }, $selectAllStatement->fetchAll(PDO::FETCH_ASSOC));

        return new EntityCollection($values);
    }

    public function save(EntityInterface $entity): bool
    {
        if ($entity->getId()) {
            $values = $entity->toArray();
            $updateStatement = $this->createUpdateStatement(array_keys($values));
            $updateStatement->bindValue('id', $entity->getId());
            foreach ($values as $key => $value) {
                if (in_array($key, $this->fillable))
                    $updateStatement->bindValue($key, $value);
            }
            return $updateStatement->execute();
        } else {
            $values = $entity->toArray();
            foreach ($values as $key => $value) {
                if (in_array($key, $this->fillable))
                    $this->insertStatement->bindValue($key, $value);
            }
            return $this->insertStatement->execute();
        }
    }

    public function delete(EntityInterface $entity): bool
    {
        if ($entity->getId()) {
            return $this->deleteStatement->execute([$entity->getId()]);
        }
        return false;
    }

    protected function createUpdateStatement(array $keys): PDOStatement
    {
        $keys = array_intersect($keys, $this->fillable);
        $updateFillableString = implode(", ", array_map(function(string $item) {
            return "{$item} = :{$item}";
        }, $keys));

        return $this->pdo->prepare(
            "UPDATE {$this->getTable()} SET {$updateFillableString} WHERE id = :id"
        );
    }

    abstract public function getTable(): string;
}