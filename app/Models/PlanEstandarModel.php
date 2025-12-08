<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\PlanEstandar;

class PlanEstandarModel extends Model
{
    protected $table            = 'plan_estandar';
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
        'tipo_diagnostico_id',
        'fecha_creacion'
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
        'nombre'              => 'required|min_length[3]|max_length[180]',
        'descripcion'         => 'permit_empty|string',
        'version'             => 'required|integer',
        'tipo_diagnostico_id' => 'required|integer',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
