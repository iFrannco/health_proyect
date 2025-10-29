<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Documento extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'integer',
        'usuario_id' => 'integer'
    ];
}