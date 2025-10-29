<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PlanEstandar extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'fecha_creacion'];
    protected $casts   = [
        'id' => 'integer',
        'version' => 'integer',
        'vigente' => 'boolean'
    ];

    protected function setVigente(string $val): void
    {
        $this->attributes['vigente'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
}