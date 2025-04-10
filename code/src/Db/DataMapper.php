<?php

namespace Pasha234\Hw12\Db;

use PDO;
use PDOStatement;

abstract class DataMapper
{
    protected PDO $pdo;
    protected PDOStatement $selectStatement;
    protected PDOStatement $insertStatement;
    protected PDOStatement $deleteStatement;
    protected array $fillable = [];
    protected string $entityClass;
    protected IdentityMap $identityMap;

    public function __construct(PDO $pdo, IdentityMap $identityMap)
    {
        $this->pdo = $pdo;
        $this->identityMap = $identityMap;

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
        $entity = $this->identityMap->get($this->entityClass, $id);
        if ($entity !== null) {
            return $entity;
        }

        $this->selectStatement->setFetchMode(PDO::FETCH_ASSOC);
        $this->selectStatement->execute(['id' => $id]);

        $result = $this->selectStatement->fetch();

        if ($result) {
            $entity = new $this->entityClass($result);
            $this->identityMap->set($this->entityClass, $id, $entity); // Добавляем в карту
            return $entity;
        }

        return null;
    }

    public function findAll(): EntityCollection
    {
        $selectAllStatement = $this->pdo->prepare(
            "SELECT * FROM {$this->getTable()}"
        );

        $selectAllStatement->execute();
        $results = $selectAllStatement->fetchAll(PDO::FETCH_ASSOC);
        $entities = [];

        foreach ($results as $item) {
            $id = $item['id'] ?? null;
            if ($id === null) {
                continue;
            }

            $entity = $this->identityMap->get($this->entityClass, $id);
            if ($entity === null) {
                $entity = new $this->entityClass($item);
                $this->identityMap->set($this->entityClass, $id, $entity);
            }
            $entities[] = $entity;
        }

        return new EntityCollection($entities);
    }

    public function save(EntityInterface $entity): bool
    {
        $id = $entity->getId();
        $values = $entity->toArray();
        $result = false;

        if ($id) {
            unset($values['id']);
            $updateKeys = array_intersect(array_keys($values), $this->fillable);
            if (empty($updateKeys)) {
                 return true;
            }

            $updateStatement = $this->createUpdateStatement($updateKeys);
            $updateStatement->bindValue('id', $id);
            foreach ($updateKeys as $key) {
                $updateStatement->bindValue($key, $values[$key]);
            }

            $result = $updateStatement->execute();
            if ($result) {
                $this->identityMap->set($this->entityClass, $id, $entity);
            }

        } else {
            $insertValues = array_intersect_key($values, array_flip($this->fillable));
            foreach ($this->fillable as $key) {
                $this->insertStatement->bindValue($key, $insertValues[$key] ?? null);
            }

            $result = $this->insertStatement->execute();
            if ($result) {
                $lastId = (int)$this->pdo->lastInsertId($this->getTable() . '_id_seq');
                $entity->setId($lastId);
                $this->identityMap->set($this->entityClass, $lastId, $entity);
            }
        }

        return $result;
    }

    public function delete(EntityInterface $entity): bool
    {
        $id = $entity->getId();
        if ($id) {
            $result = $this->deleteStatement->execute(['id' => $id]);
            if ($result) {
                $this->identityMap->remove($this->entityClass, $id);
            }
            return $result;
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