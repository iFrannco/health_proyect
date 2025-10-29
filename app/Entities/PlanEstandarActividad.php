<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PlanEstandarActividad extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'integer',
        'plan_estandar_id' => 'integer',
        'offset_inicio_dias' => 'integer',
        'offset_fin_dias' => 'integer',
        'orden' => 'integer'
    ];
}