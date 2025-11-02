<?php

namespace App\Controllers\Medico;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Exceptions\PageForbiddenException;
use App\Models\UserModel;
use App\Services\MedicoDashboardService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Home extends BaseController
{
    private UserModel $userModel;

    private MedicoDashboardService $dashboardService;

    public function __construct()
    {
        $this->userModel        = new UserModel();
        $this->dashboardService = new MedicoDashboardService();
    }

    public function index()
    {
        $medico         = $this->obtenerMedicoActual();
        $dashboardDatos = $this->dashboardService->obtenerDashboard((int) $medico->id);

        return view('medico/home', $this->layoutData() + [
            'title'      => 'Dashboard del Médico',
            'medico'     => $medico,
            'dashboard'  => $dashboardDatos,
        ]);
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
            throw new PageNotFoundException('No existen médicos activos configurados.');
        }

        $session->set('user_id', $medico->id);

        return $medico;
    }
}
