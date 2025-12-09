<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\PlanEstandarActividad;

class PlanEstandarActividadModel extends Model
{
    protected $table            = 'plan_estandar_actividad';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = PlanEstandarActividad::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'plan_estandar_id',
        'categoria_actividad_id',
        'nombre',
        'descripcion',
        'offset_inicio_dias',
        'offset_fin_dias',
        'orden',
        'vigente',
        'frecuencia_repeticiones',
        'frecuencia_periodo',
        'duracion_valor',
        'duracion_unidad'
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
        'plan_estandar_id'        => 'required|integer',
        'categoria_actividad_id'  => 'required|is_natural_no_zero',
        'nombre'                  => 'required|min_length[3]|max_length[180]',
        'frecuencia_repeticiones' => 'permit_empty|integer',
        'duracion_valor'          => 'permit_empty|integer',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
