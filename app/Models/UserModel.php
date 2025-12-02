<?php

namespace App\Models;

use App\Entities\User;
use CodeIgniter\Model;

class UserModel extends Model
{
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_MEDICO   = 'medico';
    public const ROLE_PACIENTE = 'paciente';

    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = User::class;
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'nombre',
        'apellido',
        'dni',
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
        'dni'           => 'required|min_length[6]|max_length[20]',
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buscarPacientesPorNombreODni(string $termino, ?string $dniSoloDigitos = null, int $limit = 10): array
    {
        $builder = $this->select([
                'users.id',
                'users.nombre',
                'users.apellido',
                'users.dni',
            ])
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('users.activo', 1)
            ->where('roles.slug', self::ROLE_PACIENTE)
            ->orderBy('users.apellido', 'ASC')
            ->orderBy('users.nombre', 'ASC')
            ->limit($limit)
            ->asArray();

        if ($dniSoloDigitos !== null && $dniSoloDigitos !== '') {
            return $builder
                ->like('users.dni', $dniSoloDigitos, 'both', null, true)
                ->findAll();
        }

        $terminoNormalizado = trim($termino);

        return $builder
            ->groupStart()
                ->like('users.nombre', $terminoNormalizado, 'both', null, true)
                ->orLike('users.apellido', $terminoNormalizado, 'both', null, true)
            ->groupEnd()
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

    public function findPacientePorId(int $userId): ?User
    {
        return $this->select('users.*')
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('users.id', $userId)
            ->where('roles.slug', self::ROLE_PACIENTE)
            ->first();
    }

    /**
     * Devuelve un listado paginado de pacientes con filtro opcional por nombre/apellido.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paginatePacientes(?string $busqueda, int $perPage = 10, string $pagerGroup = 'default'): array
    {
        $columnas = [
            'users.id',
            'users.nombre',
            'users.apellido',
            'users.email',
            'users.telefono',
            'users.activo',
        ];

        $camposDisponibles = $this->db->getFieldNames($this->table);
        if (in_array('dni', $camposDisponibles, true)) {
            $columnas[] = 'users.dni';
        }

        $builder = $this->select($columnas)
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('roles.slug', self::ROLE_PACIENTE)
            ->where('users.activo', 1)
            ->orderBy('users.apellido', 'ASC')
            ->orderBy('users.nombre', 'ASC')
            ->asArray();

        $termino = trim((string) $busqueda);
        if ($termino !== '') {
            $builder
                ->groupStart()
                    ->like('users.nombre', $termino, 'both', null, true)
                    ->orLike('users.apellido', $termino, 'both', null, true)
                ->groupEnd();
        }

        return $builder->paginate($perPage, $pagerGroup);
    }

    /**
     * Devuelve un listado paginado de usuarios para el módulo de administración.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paginateUsuarios(
        ?string $busqueda,
        ?string $roleSlug,
        bool $soloActivos = true,
        int $perPage = 10,
        string $pagerGroup = 'default'
    ): array {
        $columnas = [
            'users.id',
            'users.nombre',
            'users.apellido',
            'users.email',
            'users.telefono',
            'users.activo',
            'roles.slug AS rol',
            'roles.nombre AS rol_nombre',
        ];

        $camposDisponibles = $this->db->getFieldNames($this->table);
        if (in_array('dni', $camposDisponibles, true)) {
            $columnas[] = 'users.dni';
        }

        $builder = $this->select($columnas)
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->orderBy('users.apellido', 'ASC')
            ->orderBy('users.nombre', 'ASC')
            ->asArray();

        if ($soloActivos) {
            $builder->where('users.activo', 1);
        }

        $rolNormalizado = trim(strtolower((string) $roleSlug));
        if ($rolNormalizado !== '' && $rolNormalizado !== 'todos') {
            $builder->where('roles.slug', $rolNormalizado);
        }

        $termino = trim((string) $busqueda);
        if ($termino !== '') {
            $builder
                ->groupStart()
                    ->like('users.nombre', $termino, 'both', null, true)
                    ->orLike('users.apellido', $termino, 'both', null, true)
                    ->orLike('users.email', $termino, 'both', null, true)
                ->groupEnd();
        }

        return $builder->paginate($perPage, $pagerGroup);
    }
}
