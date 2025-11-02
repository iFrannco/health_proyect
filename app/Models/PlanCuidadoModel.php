<?php

namespace App\Models;

use App\Entities\PlanCuidado;
use CodeIgniter\Model;

class PlanCuidadoModel extends Model
{
    protected $table            = 'planes_cuidado';
    protected $primaryKey       = 'id';
    protected $returnType       = PlanCuidado::class;
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'diagnostico_id',
        'creador_user_id',
        'plan_estandar_id',
        'nombre',
        'descripcion',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';
    protected $skipValidation   = false;
    protected $validationRules  = [
        'diagnostico_id' => 'required|is_natural_no_zero',
        'creador_user_id' => 'required|is_natural_no_zero',
        'fecha_inicio'   => 'required',
        'fecha_fin'      => 'required',
    ];
}
