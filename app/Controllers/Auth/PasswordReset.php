<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\PasswordResetModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;

class PasswordReset extends BaseController
{
    private const COOLDOWN_SECONDS    = 60;
    private const EXPIRATION_MINUTES  = 15;

    public function request()
    {
        if ($redirect = $this->redirigirSiAutenticado()) {
            return $redirect;
        }

        return view('auth/password_request', [
            'title'       => 'Restaurar contraseña',
            'errors'      => session()->getFlashdata('errors') ?? [],
            'status'      => session()->getFlashdata('status'),
            'reset_link'  => session()->getFlashdata('reset_link'),
            'reset_error' => session()->getFlashdata('reset_error'),
        ]);
    }

    public function send()
    {
        if ($redirect = $this->redirigirSiAutenticado()) {
            return $redirect;
        }

        $session = session();
        $cooldownUntil = (int) ($session->get('password_reset_next_allowed') ?? 0);
        $now = time();

        if ($cooldownUntil > $now) {
            $secondsLeft = $cooldownUntil - $now;
            $session->setFlashdata('reset_error', 'Debe esperar ' . $secondsLeft . ' segundos antes de solicitar un nuevo enlace.');

            return redirect()->back()->withInput();
        }

        $rules = [
            'email' => 'required|valid_email|max_length[180]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email = trim((string) $this->request->getPost('email'));
        $statusMessage = 'Si el correo existe, le enviamos un enlace para restablecer su contraseña.';

        $resetLink = null;

        try {
            $userModel = new UserModel();
            $user = $userModel
                ->where('email', $email)
                ->where('activo', 1)
                ->first();

            if ($user !== null && $user->id !== null) {
                $resetModel = new PasswordResetModel();

                // Invalida tokens previos sin usar para el usuario
                $resetModel->invalidateExistingForUser((int) $user->id);

                $tokenPlain = bin2hex(random_bytes(32));
                $tokenHash  = hash('sha256', $tokenPlain);
                $expiresAt  = Time::now()->addMinutes(self::EXPIRATION_MINUTES)->toDateTimeString();

                $resetModel->insert([
                    'user_id'    => (int) $user->id,
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt,
                ]);

                $resetLink = site_url('auth/reset-password?token=' . $tokenPlain);
            }
        } catch (\Throwable $exception) {
            log_message('error', 'Error al generar enlace de restablecimiento: {exception}', ['exception' => $exception]);
            $session->setFlashdata('reset_error', 'No pudimos procesar su solicitud. Intente nuevamente más tarde.');

            return redirect()->back()->withInput();
        }

        $session->setFlashdata('status', $statusMessage);

        if ($resetLink !== null) {
            // Nota: visible temporalmente hasta implementar envío real por correo.
            $session->setFlashdata('reset_link', $resetLink);
        }

        $session->set('password_reset_next_allowed', $now + self::COOLDOWN_SECONDS);

        return redirect()->route('auth_password_request');
    }

    public function showResetForm()
    {
        if ($redirect = $this->redirigirSiAutenticado()) {
            return $redirect;
        }

        $token = (string) $this->request->getGet('token');

        if ($token === '') {
            return $this->invalidTokenResponse();
        }

        $resetModel = new PasswordResetModel();
        $tokenData  = $resetModel->findValidByTokenHash(hash('sha256', $token));

        if ($tokenData === null) {
            return $this->invalidTokenResponse();
        }

        return view('auth/password_reset', [
            'title'  => 'Restablecer contraseña',
            'token'  => $token,
            'errors' => session()->getFlashdata('errors') ?? [],
            'status' => session()->getFlashdata('status'),
        ]);
    }

    public function processReset()
    {
        if ($redirect = $this->redirigirSiAutenticado()) {
            return $redirect;
        }

        $rules = [
            'token'            => 'required',
            'password'         => 'required|min_length[8]|max_length[64]|regex_match[/^(?=.*[A-Za-z])(?=.*\\d)(?=.*[^A-Za-z0-9]).+$/]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'password' => [
                'regex_match' => 'La contraseña debe tener al menos 8 caracteres, una letra, un número y un símbolo.',
            ],
            'password_confirm' => [
                'matches' => 'Las contraseñas no coinciden.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $token     = (string) $this->request->getPost('token');
        $tokenHash = hash('sha256', $token);

        $resetModel = new PasswordResetModel();
        $tokenData  = $resetModel->findValidByTokenHash($tokenHash);

        if ($tokenData === null) {
            return $this->invalidTokenResponse();
        }

        $userModel = new UserModel();
        $user      = $userModel->find((int) $tokenData['user_id']);

        if ($user === null || (int) ($user->activo ?? 0) !== 1) {
            return $this->invalidTokenResponse();
        }

        $newPassword = (string) $this->request->getPost('password');
        $db          = db_connect();

        $db->transBegin();

        try {
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

            $db->table('users')
                ->where('id', (int) $user->id)
                ->update(['password_hash' => $passwordHash]);

            $db->table('password_resets')
                ->where('id', (int) $tokenData['id'])
                ->update(['used_at' => Time::now()->toDateTimeString()]);
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Error al restablecer contraseña: {exception}', ['exception' => $exception]);

            return redirect()->back()->withInput()->with('errors', [
                'general' => 'No pudimos actualizar la contraseña. Intente nuevamente más tarde.',
            ]);
        }

        if ($db->transStatus() === false) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('errors', [
                'general' => 'No pudimos actualizar la contraseña. Intente nuevamente más tarde.',
            ]);
        }

        $db->transCommit();

        session()->setFlashdata('status', 'Su contraseña se actualizó correctamente. Ya puede iniciar sesión.');

        return redirect()->route('auth_login');
    }

    private function redirigirSiAutenticado(): ?RedirectResponse
    {
        $session = session();

        if ($session->has('user_id') && $session->has('rol')) {
            return redirect()->to($this->rutaPorRol((string) $session->get('rol')));
        }

        return null;
    }

    private function rutaPorRol(string $rol): string
    {
        return match ($rol) {
            UserModel::ROLE_ADMIN    => site_url('admin/home'),
            UserModel::ROLE_MEDICO   => site_url('medico/home'),
            UserModel::ROLE_PACIENTE => site_url('paciente/home'),
            default                  => base_url('/'),
        };
    }

    private function invalidTokenResponse(): RedirectResponse
    {
        session()->setFlashdata('reset_error', 'El enlace no es válido o expiró. Solicite uno nuevo.');

        return redirect()->route('auth_password_request');
    }
}
