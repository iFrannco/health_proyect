<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Sin sesión o sin rol → login
        if (! $session->has('user_id') || ! $session->has('rol')) {
            return redirect()->to(site_url('auth/login'));
        }

        $rolActual = (string) $session->get('rol');
        $rolesPermitidos = is_array($arguments) ? $arguments : [];

        // Si no se pasan argumentos, inferir por primer segmento de la URI
        if ($rolesPermitidos === []) {
            $segmento = strtolower((string) $request->getUri()->getSegment(1));
            if (in_array($segmento, ['admin', 'medico', 'paciente'], true)) {
                $rolesPermitidos = [$segmento];
            }
        }

        // Si no hay restricción o coincide, permitir
        if ($rolesPermitidos === [] || in_array($rolActual, $rolesPermitidos, true)) {
            return null;
        }

        // Rol incorrecto → redirigir a su home correspondiente
        return redirect()->to($this->rutaPorRol($rolActual));
    }

    private function rutaPorRol(string $rol): string
    {
        switch ($rol) {
            case 'admin':
                return site_url('admin/home');
            case 'medico':
                return site_url('medico/home');
            case 'paciente':
                return site_url('paciente/home');
            default:
                return site_url('auth/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
