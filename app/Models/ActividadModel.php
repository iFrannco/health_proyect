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
        'paciente_comentario',
        'paciente_completada_en',
        'fecha_validacion',
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findPorPlanConEstado(int $planId): array
    {
        return $this->asArray()
            ->select([
                'actividades.id',
                'actividades.plan_id',
                'actividades.nombre',
                'actividades.descripcion',
                'actividades.fecha_creacion',
                'actividades.fecha_inicio',
                'actividades.fecha_fin',
                'actividades.estado_id',
                'actividades.validado',
                'actividades.paciente_comentario',
                'actividades.paciente_completada_en',
                'actividades.fecha_validacion',
                'estado_actividad.nombre AS estado_nombre',
                'estado_actividad.slug AS estado_slug',
            ])
            ->join('estado_actividad', 'estado_actividad.id = actividades.estado_id', 'left')
            ->where('actividades.plan_id', $planId)
            ->orderBy('actividades.fecha_inicio', 'ASC')
            ->orderBy('actividades.id', 'ASC')
            ->findAll();
    }
}
