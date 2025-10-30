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
}

