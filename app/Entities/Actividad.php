<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Actividad extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
        'fecha_validacion',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_fin'
    ];
    protected $casts   = [
        'id' => 'integer',
        'plan_id' => 'integer',
        'estado_id' => 'integer',
        'validada' => 'boolean'
    ];

    protected function setValidada(string $val): void
    {
        $this->attributes['validada'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
}