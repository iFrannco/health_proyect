<?php

namespace App\Models;

use CodeIgniter\Model;

class EstadoActividadModel extends Model
{
    protected $table          = 'estado_actividad';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'nombre',
        'slug',
        'orden',
    ];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findActivos(): array
    {
        return $this->orderBy('orden', 'ASC')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }
}

