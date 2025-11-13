<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Usuarios extends BaseController
{
    private const PAGINATION_GROUP = 'admin_usuarios';
    private const PER_PAGE = 10;
    private const ROLE_FILTER_DEFAULT = 'todos';
    private const ROLE_FILTERS = [
        'todos'    => 'Todos',
        'paciente' => 'Pacientes',
        'medico'   => 'Médicos',
        'admin'    => 'Administradores',
    ];

    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $roleFiltro = $this->normalizarRole($this->request->getGet('role'));
        $busqueda = trim((string) $this->request->getGet('q'));
        $mostrarInactivos = $this->debeMostrarInactivos($this->request->getGet('incluir_inactivos'));

        $usuarios = $this->userModel->paginateUsuarios(
            $busqueda,
            $roleFiltro === self::ROLE_FILTER_DEFAULT ? null : $roleFiltro,
            ! $mostrarInactivos,
            self::PER_PAGE,
            self::PAGINATION_GROUP
        );

        $viewData = $this->layoutData() + [
            'title'            => 'Usuarios del sistema',
            'usuarios'         => $usuarios,
            'pager'            => $this->userModel->pager,
            'pagerGroup'       => self::PAGINATION_GROUP,
            'busqueda'         => $busqueda,
            'roleFiltro'       => $roleFiltro,
            'roleOptions'      => self::ROLE_FILTERS,
            'mostrarInactivos' => $mostrarInactivos,
        ];

        return view('admin/usuarios/index', $viewData);
    }

    private function normalizarRole(mixed $valor): string
    {
        $valorNormalizado = strtolower(trim((string) $valor));

        if ($valorNormalizado === '') {
            return self::ROLE_FILTER_DEFAULT;
        }

        return array_key_exists($valorNormalizado, self::ROLE_FILTERS)
            ? $valorNormalizado
            : self::ROLE_FILTER_DEFAULT;
    }

    private function debeMostrarInactivos(mixed $valor): bool
    {
        if ($valor === null) {
            return false;
        }

        $valor = strtolower((string) $valor);

        return in_array($valor, ['1', 'true', 'on', 'si'], true);
    }
}
