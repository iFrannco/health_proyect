<?php

namespace App\Models;

use App\Entities\Actividad;
use CodeIgniter\Model;

class ActividadModel extends Model
{
    protected $table            = 'actividades';
    protected $primaryKey       = 'id';
    protected $returnType       = Actividad::class;
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'plan_id',
        'nombre',
        'descripcion',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_fin',
        'estado_id',
        'validado',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';
    protected $validationRules  = [
        'plan_id'      => 'required|is_natural_no_zero',
        'nombre'       => 'required|min_length[1]|max_length[120]',
        'descripcion'  => 'required|min_length[1]|max_length[2000]',
        'fecha_inicio' => 'required',
        'fecha_fin'    => 'required',
        'estado_id'    => 'required|is_natural_no_zero',
    ];
}
