<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Login extends BaseController
{
    public function index()
    {
        $session = session();

        if ($session->has('user_id') && $session->has('rol')) {
            return redirect()->to($this->rutaPorRol((string) $session->get('rol')));
        }

        return view('auth/login', [
            'title' => 'Iniciar sesión',
        ]);
    }

    public function autenticar()
    {
        $validationRules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ];

        if (! $this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        $userModel = new UserModel();

        try {
            $usuario = $userModel
                ->select('users.*, roles.slug AS rol_slug')
                ->join('roles', 'roles.id = users.role_id', 'inner')
                ->where('users.email', $email)
                ->first();
        } catch (\Throwable $exception) {
            log_message('critical', 'Error de base de datos al autenticar: {exception}', ['exception' => $exception]);
            return redirect()->back()->withInput()->with('login_error', 'Servicio temporalmente no disponible. Verifica la conexión a la base de datos.');
        }

        if ($usuario === null) {
            return $this->falloAutenticacion('Credenciales inválidas.');
        }

        // Verifica activo y password
        $activo = (int) ($usuario->activo ?? 0) === 1;
        $hash = (string) ($usuario->password_hash ?? '');

        if (! $activo || $hash === '' || ! password_verify($password, $hash)) {
            return $this->falloAutenticacion('Credenciales inválidas o usuario inactivo.');
        }

        // Inicia sesión
        $session = session();
        $session->regenerate();
        $session->set([
            'user_id' => (int) $usuario->id,
            'email'   => (string) $usuario->email,
            'nombre'  => trim(((string) ($usuario->nombre ?? '')) . ' ' . ((string) ($usuario->apellido ?? ''))),
            'rol'     => (string) ($usuario->rol_slug ?? ''),
        ]);

        return redirect()->to($this->rutaPorRol((string) $session->get('rol')));
    }

    private function falloAutenticacion(string $mensaje)
    {
        return redirect()->back()->withInput()->with('login_error', $mensaje);
    }

    private function rutaPorRol(string $rol): string
    {
        return match ($rol) {
            'admin'   => site_url('admin/home'),
            'medico'  => site_url('medico/home'),
            'paciente'=> site_url('paciente/home'),
            default   => base_url('/'),
        };
    }
}
