<?php

namespace Pasha234\Hw12\Db;

interface EntityInterface
{
    public function __construct(array $values);
    public function toArray(): array;
    public function getId(): ?int;
}