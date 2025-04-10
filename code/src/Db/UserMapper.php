<?php

namespace Pasha234\Hw12\Db;

use PDO;

class UserMapper extends DataMapper
{
    protected string $entityClass = User::class;
    protected array $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    public function getTable(): string
    {
        return 'users';
    }
}