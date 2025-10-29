<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Diagnostico extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
        'fecha_creacion'
    ];
    protected $casts   = [
        'id' => 'integer',
        'autor_user_id' => 'integer',
        'destinatario_user_id' => 'integer',
        'tipo_diagnostico_id' => 'integer'
    ];
}