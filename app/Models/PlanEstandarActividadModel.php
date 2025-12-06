<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\PlanEstandarActividad;

class PlanEstandarActividadModel extends Model
{
    protected $table            = 'plan_estandar_actividades';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = PlanEstandarActividad::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'plan_estandar_id',
        'nombre',
        'descripcion',
        'offset_inicio_dias',
        'offset_fin_dias',
        'orden',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'plan_estandar_id'   => 'required|integer',
        'nombre'             => 'required|max_length[180]',
        'offset_inicio_dias' => 'required|integer|greater_than_equal_to[0]',
        'offset_fin_dias'    => 'required|integer|greater_than_equal_to[0]',
        'orden'              => 'permit_empty|integer',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
