<?php

namespace App\Models;

use App\Entities\Diagnostico;
use CodeIgniter\Model;

class DiagnosticoModel extends Model
{
    protected $table            = 'diagnosticos';
    protected $primaryKey       = 'id';
    protected $returnType       = Diagnostico::class;
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'autor_user_id',
        'destinatario_user_id',
        'tipo_diagnostico_id',
        'descripcion',
        'fecha_creacion',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';
    protected $validationRules  = [
        'autor_user_id'         => 'required|is_natural_no_zero',
        'destinatario_user_id'  => 'required|is_natural_no_zero',
        'tipo_diagnostico_id'   => 'required|is_natural_no_zero',
        'descripcion'           => 'required|min_length[10]|max_length[2000]',
    ];
    protected $validationMessages = [
        'descripcion' => [
            'required'   => 'La descripcion del diagnostico es obligatoria.',
            'min_length' => 'La descripcion debe tener al menos 10 caracteres.',
            'max_length' => 'La descripcion no puede superar los 2000 caracteres.',
        ],
    ];
    protected $skipValidation   = false;
    protected $beforeInsert     = ['setFechaCreacion'];

    /**
     * Garantiza que fecha_creacion siempre tenga un valor al insertar.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function setFechaCreacion(array $data): array
    {
        if (! isset($data['data']['fecha_creacion']) || empty($data['data']['fecha_creacion'])) {
            $data['data']['fecha_creacion'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Devuelve los diagnósticos del médico junto a información del paciente y tipo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDetallesPorMedico(int $medicoId): array
    {
        return $this->asArray()
            ->select([
                'diagnosticos.id',
                'diagnosticos.descripcion',
                'diagnosticos.fecha_creacion',
                'paciente.id as paciente_id',
                'paciente.nombre as paciente_nombre',
                'paciente.apellido as paciente_apellido',
                'tipo.id as tipo_id',
                'tipo.nombre as tipo_nombre',
            ])
            ->join('users as paciente', 'paciente.id = diagnosticos.destinatario_user_id', 'inner')
            ->join('tipos_diagnostico as tipo', 'tipo.id = diagnosticos.tipo_diagnostico_id', 'inner')
            ->where('diagnosticos.autor_user_id', $medicoId)
            ->orderBy('diagnosticos.fecha_creacion', 'DESC')
            ->findAll();
    }
}
