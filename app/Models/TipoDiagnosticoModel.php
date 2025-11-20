<?php

namespace App\Models;

use App\Entities\TipoDiagnostico;
use CodeIgniter\Model;

class TipoDiagnosticoModel extends Model
{
    protected $table          = 'tipos_diagnostico';
    protected $primaryKey     = 'id';
    protected $returnType     = TipoDiagnostico::class;
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'nombre',
        'slug',
        'descripcion',
        'activo',
    ];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    /**
     * @return array<int, TipoDiagnostico>
     */
    public function findActivos(): array
    {
        return $this->where('activo', 1)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * Obtiene los tipos paginados junto al total de usos en diagnósticos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paginateConUso(?string $busqueda, int $perPage, string $pagerGroup): array
    {
        $builder = $this->asArray()
            ->select('tipos_diagnostico.*')
            ->select('COUNT(d.id) AS total_usos', false)
            ->join('diagnosticos d', 'd.tipo_diagnostico_id = tipos_diagnostico.id AND d.deleted_at IS NULL', 'left');

        if ($busqueda !== null && $busqueda !== '') {
            $builder->like('tipos_diagnostico.nombre', $busqueda);
        }

        return $builder
            ->groupBy('tipos_diagnostico.id')
            ->orderBy('tipos_diagnostico.nombre', 'ASC')
            ->paginate($perPage, $pagerGroup);
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $builder = $this->builder()
            ->select('id')
            ->where('slug', $slug);

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return (int) $builder->countAllResults() > 0;
    }
}
