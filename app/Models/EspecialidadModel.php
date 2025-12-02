<?php

namespace App\Models;

use CodeIgniter\Model;

class EspecialidadModel extends Model
{
    protected $table          = 'especialidades';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields  = ['slug', 'nombre'];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    /**
     * @return array<int, array{id:int, slug:string, nombre:string}>
     */
    public function obtenerCatalogo(): array
    {
        return $this->select(['id', 'slug', 'nombre'])
            ->orderBy('nombre', 'ASC')
            ->asArray()
            ->findAll();
    }
}
