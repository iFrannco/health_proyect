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

        // Si no hay sesión válida, redirige a login
        if (! $session->has('rol')) {
            return redirect()->to(site_url('auth/login'));
        }

        $rolActual = (string) $session->get('rol');
        $rolesPermitidos = is_array($arguments) ? $arguments : [];

        if ($rolesPermitidos === [] || in_array($rolActual, $rolesPermitidos, true)) {
            return null;
        }

        // Acceso denegado
        $response = service('response');
        return $response->setStatusCode(403, 'Acceso denegado');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
