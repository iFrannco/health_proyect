<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\PlanEstandar;

class PlanEstandarModel extends Model
{
    protected $table            = 'planes_estandar';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = PlanEstandar::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'descripcion',
        'version',
        'vigente',
        'fecha_creacion',
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
        'nombre'  => 'required|max_length[180]',
        'version' => 'required|integer',
        'vigente' => 'required|in_list[0,1]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setFechaCreacion'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    protected function setFechaCreacion(array $data)
    {
        if (! isset($data['data']['fecha_creacion'])) {
            $data['data']['fecha_creacion'] = date('Y-m-d H:i:s');
        }
        return $data;
    }
}
