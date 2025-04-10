<?php

namespace Pasha234\Hw12\Db;

class User implements EntityInterface
{
    protected array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function getId(): ?int
    {
        return $this->values['id'] ?? null;
    }

    public function setFirstName(string $first_name)
    {
        $this->values['first_name'] = $first_name;
    }

    public function setLastName(string $last_name)
    {
        $this->values['last_name'] = $last_name;
    }

    public function setEmail(string $email)
    {
        $this->values['email'] = $email;
    }

    public function setId(int $id): void
    {
        $this->values['id'] = $id;
    }

    public function setPassword(string $password)
    {
        $this->values['password'] = $password;
    }

    public function getFirstName(): ?string
    {
        return $this->values['first_name'] ?? null;
    }
     public function getLastName(): ?string
    {
        return $this->values['last_name'] ?? null;
    }
    public function getEmail(): ?string
    {
        return $this->values['email'] ?? null;
    }
}