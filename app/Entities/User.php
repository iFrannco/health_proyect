<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
        'fecha_nac'
    ];
    protected $casts   = [
        'id' => 'integer',
        'role_id' => 'integer',
        'activo' => 'boolean'
    ];

    protected function setPassword(string $password): self
    {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_BCRYPT);

        return $this;
    }

    protected function setActivo(string $val): void
    {
        $this->attributes['activo'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
}