<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TipoDiagnostico extends Entity
{
    /**
     * @var list<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'id'     => 'integer',
        'activo' => 'boolean',
    ];
}

