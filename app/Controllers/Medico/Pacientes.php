<?php

namespace App\Controllers\Medico;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Exceptions\PageForbiddenException;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Pacientes extends BaseController
{
    private const PACIENTES_POR_PAGINA = 10;

    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $medico   = $this->obtenerMedicoActual();
        $busqueda = trim((string) $this->request->getGet('q'));

        $pacientes = $this->userModel->paginatePacientes(
            $busqueda,
            self::PACIENTES_POR_PAGINA,
            'pacientes'
        );

        $pager = $this->userModel->pager;
        if ($pager !== null) {
            $pager->setPath(site_url('medico/pacientes'), true);
        }

        $data = [
            'title'     => 'Pacientes',
            'medico'    => $medico,
            'pacientes' => $pacientes,
            'pager'     => $pager,
            'busqueda'  => $busqueda,
        ];

        return view('medico/pacientes/index', $this->layoutData() + $data);
    }

    private function obtenerMedicoActual(): User
    {
        $session = session();
        $userId  = $session->get('user_id');

        if ($userId !== null) {
            $medico = $this->userModel->findActivoPorRol((int) $userId, UserModel::ROLE_MEDICO);
            if ($medico !== null) {
                return $medico;
            }

            throw new PageForbiddenException('Acceso denegado para el usuario actual.');
        }

        $medico = $this->userModel->findPrimeroActivoPorRol(UserModel::ROLE_MEDICO);

        if ($medico === null) {
            throw new PageNotFoundException('No existen medicos activos configurados.');
        }

        $session->set('user_id', $medico->id);

        return $medico;
    }
}
