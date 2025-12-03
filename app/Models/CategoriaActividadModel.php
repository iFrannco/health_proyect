<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriaActividadModel extends Model
{
    protected $table            = 'categoria_actividad';
    protected $primaryKey       = 'id';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nombre',
        'descripcion',
        'color_hex',
        'activo',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $returnType       = 'array';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findActivas(): array
    {
        return $this->asArray()
            ->where('activo', 1)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }
}
