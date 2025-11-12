<?php

namespace App\Controllers\Paciente;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\UserModel;
use App\Services\PacientePerfilService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Perfil extends BaseController
{
    private UserModel $userModel;

    private PacientePerfilService $perfilService;

    public function __construct()
    {
        $this->userModel     = new UserModel();
        $this->perfilService = new PacientePerfilService($this->userModel);
    }

    public function index()
    {
        $paciente = $this->obtenerPacienteActual();

        return view('paciente/perfil/index', $this->layoutData() + [
            'title'              => 'Mi perfil',
            'paciente'           => $paciente,
            'errorsDatos'        => session()->getFlashdata('errors_datos') ?? [],
            'errorsPassword'     => session()->getFlashdata('errors_password') ?? [],
        ]);
    }

    public function actualizarDatos()
    {
        $paciente = $this->obtenerPacienteActual();

        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[120]',
            'apellido' => 'required|min_length[2]|max_length[120]',
            'email'    => 'required|valid_email|max_length[180]|is_unique[users.email,id,' . (int) $paciente->id . ']',
            'telefono' => 'permit_empty|max_length[50]',
            'fecha_nac'=> 'permit_empty|valid_date[Y-m-d]',
        ];

        $messages = [
            'email' => [
                'is_unique' => 'El email ya está registrado por otro usuario.',
            ],
            'fecha_nac' => [
                'valid_date' => 'La fecha de nacimiento debe tener el formato YYYY-MM-DD.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors_datos', $this->validator->getErrors());
        }

        $payload = [
            'nombre'    => trim((string) $this->request->getPost('nombre')),
            'apellido'  => trim((string) $this->request->getPost('apellido')),
            'email'     => trim((string) $this->request->getPost('email')),
            'telefono'  => trim((string) $this->request->getPost('telefono')) ?: null,
            'fecha_nac' => $this->normalizarFecha($this->request->getPost('fecha_nac')),
        ];

        try {
            $pacienteActualizado = $this->perfilService->actualizarDatos((int) $paciente->id, $payload);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al actualizar perfil del paciente: {exception}', ['exception' => $exception]);

            return redirect()->back()->withInput()->with('errors_datos', [
                'general' => 'No se pudo actualizar el perfil. Inténtalo nuevamente.',
            ]);
        }

        $this->actualizarSesion($pacienteActualizado);
        session()->setFlashdata('success', 'Perfil actualizado correctamente.');

        return redirect()->route('paciente_perfil_index');
    }

    public function actualizarPassword()
    {
        $paciente = $this->obtenerPacienteActual();

        $rules = [
            'password_actual'       => 'required',
            'password_nueva'        => 'required|min_length[8]|max_length[64]',
            'password_confirmacion' => 'required|matches[password_nueva]',
        ];

        $messages = [
            'password_confirmacion' => [
                'matches' => 'La confirmación debe coincidir con la nueva contraseña.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors_password', $this->validator->getErrors());
        }

        $passwordActual = (string) $this->request->getPost('password_actual');
        $passwordNueva  = (string) $this->request->getPost('password_nueva');

        if (! password_verify($passwordActual, (string) $paciente->password_hash)) {
            return redirect()->back()->withInput()->with('errors_password', [
                'password_actual' => 'La contraseña actual no es válida.',
            ]);
        }

        try {
            $this->perfilService->actualizarPassword((int) $paciente->id, $passwordNueva);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al actualizar contraseña del paciente: {exception}', ['exception' => $exception]);

            return redirect()->back()->withInput()->with('errors_password', [
                'general' => 'No se pudo actualizar la contraseña. Inténtalo nuevamente.',
            ]);
        }

        session()->setFlashdata('success', 'Contraseña actualizada correctamente.');

        return redirect()->route('paciente_perfil_index');
    }

    private function normalizarFecha($fecha): ?string
    {
        $valor = trim((string) ($fecha ?? ''));
        if ($valor === '') {
            return null;
        }

        return $valor;
    }

    private function actualizarSesion(User $usuario): void
    {
        $session = session();

        $session->set([
            'email'  => (string) $usuario->email,
            'nombre' => trim(((string) ($usuario->nombre ?? '')) . ' ' . ((string) ($usuario->apellido ?? ''))),
        ]);
    }

    private function obtenerPacienteActual(): User
    {
        $session = session();
        $userId  = $session->get('user_id');

        if ($userId !== null) {
            $paciente = $this->userModel->findActivoPorRol((int) $userId, UserModel::ROLE_PACIENTE);
            if ($paciente instanceof User) {
                return $paciente;
            }
        }

        $paciente = $this->userModel->findPrimeroActivoPorRol(UserModel::ROLE_PACIENTE);
        if ($paciente === null) {
            throw new PageNotFoundException('No existen pacientes activos configurados.');
        }

        $session->set('user_id', $paciente->id);

        return $paciente;
    }
}
