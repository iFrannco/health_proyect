<?php

namespace App\Models;

use App\Entities\User;
use CodeIgniter\Model;

class UserModel extends Model
{
    public const ROLE_MEDICO   = 'medico';
    public const ROLE_PACIENTE = 'paciente';

    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = User::class;
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'nombre',
        'apellido',
        'email',
        'password_hash',
        'role_id',
        'activo',
        'fecha_nac',
        'telefono',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';
    protected $validationRules  = [
        'nombre'        => 'required|min_length[2]|max_length[120]',
        'apellido'      => 'required|min_length[2]|max_length[120]',
        'email'         => 'required|valid_email|max_length[180]',
        'password_hash' => 'required|max_length[255]',
        'role_id'       => 'required|is_natural_no_zero',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findActivosPorRol(string $roleSlug): array
    {
        return $this->select([
                'users.id',
                'users.nombre',
                'users.apellido',
                'users.email',
            ])
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('users.activo', 1)
            ->where('roles.slug', $roleSlug)
            ->orderBy('users.apellido', 'ASC')
            ->orderBy('users.nombre', 'ASC')
            ->asArray()
            ->findAll();
    }

    public function findActivoPorRol(int $userId, string $roleSlug): ?User
    {
        return $this->select('users.*')
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('users.id', $userId)
            ->where('users.activo', 1)
            ->where('roles.slug', $roleSlug)
            ->first();
    }

    public function findPrimeroActivoPorRol(string $roleSlug): ?User
    {
        return $this->select('users.*')
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('roles.slug', $roleSlug)
            ->where('users.activo', 1)
            ->orderBy('users.id', 'ASC')
            ->first();
    }
}
