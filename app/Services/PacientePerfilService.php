<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\User;
use App\Models\UserModel;
use RuntimeException;

class PacientePerfilService
{
    private UserModel $userModel;

    public function __construct(?UserModel $userModel = null)
    {
        $this->userModel = $userModel ?? new UserModel();
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
        $camposPermitidos = ['nombre', 'apellido', 'email', 'telefono', 'fecha_nac'];
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
}

