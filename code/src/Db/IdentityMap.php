<?php

namespace Pasha234\Hw12\Db;

class IdentityMap
{
    /**
     * @var array<string, array<int, EntityInterface>>
     */
    private array $map = [];

    /**
     * Добавляет или обновляет сущность в карте.
     *
     * @param string $entityClass Класс сущности (e.g., User::class)
     * @param int $id Идентификатор сущности
     * @param EntityInterface $entity Объект сущности
     */
    public function set(string $entityClass, int $id, EntityInterface $entity): void
    {
        if (!isset($this->map[$entityClass])) {
            $this->map[$entityClass] = [];
        }
        $this->map[$entityClass][$id] = $entity;
    }

    /**
     * Получает сущность из карты по классу и ID.
     *
     * @param string $entityClass Класс сущности
     * @param int $id Идентификатор сущности
     * @return EntityInterface|null Возвращает объект сущности или null, если не найден.
     */
    public function get(string $entityClass, int $id): ?EntityInterface
    {
        return $this->map[$entityClass][$id] ?? null;
    }

    /**
     * Проверяет, есть ли сущность в карте.
     *
     * @param string $entityClass Класс сущности
     * @param int $id Идентификатор сущности
     * @return bool
     */
    public function has(string $entityClass, int $id): bool
    {
        return isset($this->map[$entityClass][$id]);
    }

    /**
     * Удаляет сущность из карты.
     *
     * @param string $entityClass Класс сущности
     * @param int $id Идентификатор сущности
     */
    public function remove(string $entityClass, int $id): void
    {
        if (isset($this->map[$entityClass][$id])) {
            unset($this->map[$entityClass][$id]);
        }
    }

    /**
     * Очищает всю карту или карту для конкретного класса.
     *
     * @param string|null $entityClass Класс сущности для очистки (null для очистки всей карты)
     */
    public function clear(?string $entityClass = null): void
    {
        if ($entityClass === null) {
            $this->map = [];
        } elseif (isset($this->map[$entityClass])) {
            unset($this->map[$entityClass]);
        }
    }
}