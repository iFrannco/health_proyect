<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\User;
use App\Models\EspecialidadModel;
use App\Models\UsuarioEspecialidadModel;
use App\Models\UserModel;
use InvalidArgumentException;
use RuntimeException;

class PerfilUsuarioService
{
    private UserModel $userModel;

    private EspecialidadModel $especialidadModel;

    private UsuarioEspecialidadModel $usuarioEspecialidadModel;

    public function __construct(
        ?UserModel $userModel = null,
        ?EspecialidadModel $especialidadModel = null,
        ?UsuarioEspecialidadModel $usuarioEspecialidadModel = null
    ) {
        $this->userModel               = $userModel ?? new UserModel();
        $this->especialidadModel       = $especialidadModel ?? new EspecialidadModel();
        $this->usuarioEspecialidadModel = $usuarioEspecialidadModel ?? new UsuarioEspecialidadModel();
    }

    public function obtenerPaciente(int $userId): User
    {
        $usuario = $this->userModel->find($userId);

        if (! $usuario instanceof User) {
            throw new RuntimeException('El paciente no existe o fue eliminado.');
        }

        return $usuario;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizarDatos(int $userId, array $datos): User
    {
        $camposPermitidos = ['nombre', 'apellido', 'dni', 'email', 'telefono', 'fecha_nac'];
        $payload          = array_intersect_key($datos, array_flip($camposPermitidos));

        if ($payload !== []) {
            $this->userModel->update($userId, $payload);
        }

        return $this->obtenerPaciente($userId);
    }

    public function actualizarPassword(int $userId, string $nuevaPassword): void
    {
        $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT);

        $this->userModel->update($userId, [
            'password_hash' => $hash,
        ]);
    }

    /**
     * @return array<int, array{id:int, slug:string, nombre:string}>
     */
    public function obtenerCatalogoEspecialidades(): array
    {
        $catalogo = $this->especialidadModel->obtenerCatalogo();

        return array_map(
            static fn (array $item): array => [
                'id'     => (int) $item['id'],
                'slug'   => (string) $item['slug'],
                'nombre' => (string) $item['nombre'],
            ],
            $catalogo
        );
    }

    /**
     * @return array<int, array{id:int, slug:string, nombre:string}>
     */
    public function obtenerEspecialidadesAsignadas(int $userId, bool $permitirInactivos = false): array
    {
        $this->asegurarMedico($userId, ! $permitirInactivos);

        $filas = $this->usuarioEspecialidadModel->obtenerEspecialidadesPorUsuario($userId);

        return array_map(
            static fn (array $row): array => [
                'id'     => (int) $row['especialidad_id'],
                'slug'   => (string) $row['slug'],
                'nombre' => (string) $row['nombre'],
            ],
            $filas
        );
    }

    /**
     * @param array<int|string> $especialidadIds
     *
     * @return array<int, array{id:int, slug:string, nombre:string}>
     */
    public function actualizarEspecialidades(int $userId, array $especialidadIds, bool $permitirInactivos = false): array
    {
        $this->asegurarMedico($userId, ! $permitirInactivos);

        $catalogo = $this->obtenerCatalogoEspecialidades();
        if ($catalogo === []) {
            throw new RuntimeException('No hay especialidades configuradas.');
        }

        $catalogoPorId = [];
        foreach ($catalogo as $especialidad) {
            $catalogoPorId[$especialidad['id']] = $especialidad;
        }

        $idsNormalizados = $this->normalizarIds($especialidadIds);
        foreach ($idsNormalizados as $id) {
            if (! isset($catalogoPorId[$id])) {
                throw new InvalidArgumentException('Una o más especialidades no son válidas.');
            }
        }

        $db = $this->usuarioEspecialidadModel->db;
        $db->transStart();

        $this->usuarioEspecialidadModel->where('user_id', $userId)->delete();

        if ($idsNormalizados !== []) {
            $payload = array_map(
                static fn (int $id): array => [
                    'user_id'         => $userId,
                    'especialidad_id' => $id,
                ],
                $idsNormalizados
            );

            $this->usuarioEspecialidadModel->insertBatch($payload);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new RuntimeException('No se pudo actualizar las especialidades.');
        }

        return $this->obtenerEspecialidadesAsignadas($userId, $permitirInactivos);
    }

    private function asegurarMedico(int $userId, bool $soloActivos = true): User
    {
        $builder = $this->userModel->select('users.*')
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('roles.slug', UserModel::ROLE_MEDICO)
            ->where('users.id', $userId);

        if ($soloActivos) {
            $builder->where('users.activo', 1);
        }

        /** @var User|null $medico */
        $medico = $builder->first();

        if (! $medico instanceof User) {
            throw new RuntimeException('El usuario no corresponde a un médico válido para especialidades.');
        }

        return $medico;
    }

    /**
     * @param array<int|string> $ids
     *
     * @return array<int, int>
     */
    private function normalizarIds(array $ids): array
    {
        $filtrados = array_filter($ids, static fn ($valor) => $valor !== null && $valor !== '');

        $enteros = array_map(static fn ($valor): int => (int) $valor, $filtrados);

        $enteros = array_filter($enteros, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($enteros));
    }
}
