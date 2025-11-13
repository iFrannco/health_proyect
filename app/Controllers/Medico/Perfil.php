<?php

namespace App\Controllers\Medico;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\UserModel;
use App\Services\PerfilUsuarioService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Perfil extends BaseController
{
    private UserModel $userModel;

    private PerfilUsuarioService $perfilService;

    public function __construct()
    {
        $this->userModel     = new UserModel();
        $this->perfilService = new PerfilUsuarioService($this->userModel);
    }

    public function index()
    {
        $medico = $this->obtenerMedicoActual();

        return view('paciente/perfil/index', $this->layoutData() + [
            'title'          => 'Mi perfil',
            'usuario'        => $medico,
            'rolLabel'       => 'Médico',
            'formRoutes'     => [
                'datos'    => route_to('medico_perfil_actualizar_datos'),
                'password' => route_to('medico_perfil_actualizar_password'),
            ],
            'errorsDatos'    => session()->getFlashdata('errors_datos') ?? [],
            'errorsPassword' => session()->getFlashdata('errors_password') ?? [],
        ]);
    }

    public function actualizarDatos()
    {
        $medico = $this->obtenerMedicoActual();

        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[120]',
            'apellido' => 'required|min_length[2]|max_length[120]',
            'dni'      => 'required|min_length[6]|max_length[20]|is_unique[users.dni,id,' . (int) $medico->id . ']',
            'email'    => 'required|valid_email|max_length[180]|is_unique[users.email,id,' . (int) $medico->id . ']',
            'telefono' => 'permit_empty|max_length[50]',
            'fecha_nac'=> 'permit_empty|valid_date[Y-m-d]',
        ];

        $messages = [
            'dni' => [
                'is_unique' => 'El DNI ya está registrado por otro usuario.',
            ],
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
            'dni'       => trim((string) $this->request->getPost('dni')),
            'email'     => trim((string) $this->request->getPost('email')),
            'telefono'  => trim((string) $this->request->getPost('telefono')) ?: null,
            'fecha_nac' => $this->normalizarFecha($this->request->getPost('fecha_nac')),
        ];

        try {
            $medicoActualizado = $this->perfilService->actualizarDatos((int) $medico->id, $payload);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al actualizar perfil del médico: {exception}', ['exception' => $exception]);

            return redirect()->back()->withInput()->with('errors_datos', [
                'general' => 'No se pudo actualizar el perfil. Inténtalo nuevamente.',
            ]);
        }

        $this->actualizarSesion($medicoActualizado);
        session()->setFlashdata('success', 'Perfil actualizado correctamente.');

        return redirect()->route('medico_perfil_index');
    }

    public function actualizarPassword()
    {
        $medico = $this->obtenerMedicoActual();

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

        if (! password_verify($passwordActual, (string) $medico->password_hash)) {
            return redirect()->back()->withInput()->with('errors_password', [
                'password_actual' => 'La contraseña actual no es válida.',
            ]);
        }

        try {
            $this->perfilService->actualizarPassword((int) $medico->id, $passwordNueva);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al actualizar contraseña del médico: {exception}', ['exception' => $exception]);

            return redirect()->back()->withInput()->with('errors_password', [
                'general' => 'No se pudo actualizar la contraseña. Inténtalo nuevamente.',
            ]);
        }

        session()->setFlashdata('success', 'Contraseña actualizada correctamente.');

        return redirect()->route('medico_perfil_index');
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

    private function obtenerMedicoActual(): User
    {
        $session = session();
        $userId  = $session->get('user_id');

        if ($userId !== null) {
            $medico = $this->userModel->findActivoPorRol((int) $userId, UserModel::ROLE_MEDICO);
            if ($medico instanceof User) {
                return $medico;
            }
        }

        $medico = $this->userModel->findPrimeroActivoPorRol(UserModel::ROLE_MEDICO);
        if ($medico === null) {
            throw new PageNotFoundException('No existen médicos activos configurados.');
        }

        $session->set('user_id', $medico->id);

        return $medico;
    }
}
