<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioEspecialidadModel extends Model
{
    protected $table          = 'usuario_especialidad';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields  = ['user_id', 'especialidad_id'];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerEspecialidadesPorUsuario(int $userId): array
    {
        return $this->select([
                'usuario_especialidad.id',
                'usuario_especialidad.user_id',
                'usuario_especialidad.especialidad_id',
                'especialidades.slug',
                'especialidades.nombre',
            ])
            ->join('especialidades', 'especialidades.id = usuario_especialidad.especialidad_id', 'inner')
            ->where('usuario_especialidad.user_id', $userId)
            ->orderBy('especialidades.nombre', 'ASC')
            ->asArray()
            ->findAll();
    }
}
