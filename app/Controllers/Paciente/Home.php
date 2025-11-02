<?php

namespace App\Controllers\Paciente;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\UserModel;
use App\Services\PacienteDashboardService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    private UserModel $userModel;

    private PacienteDashboardService $dashboardService;

    public function __construct()
    {
        $this->userModel        = new UserModel();
        $this->dashboardService = new PacienteDashboardService();
    }

    public function index()
    {
        session()->set('rol', 'paciente');

        $paciente  = $this->obtenerPacienteActual();
        $dashboard = $this->dashboardService->obtenerDashboard((int) $paciente->id);

        return view('paciente/home', $this->layoutData() + [
            'title'      => 'Panel del Paciente',
            'paciente'   => $paciente,
            'dashboard'  => $dashboard,
        ]);
    }

    public function resumen(): ResponseInterface
    {
        try {
            session()->set('rol', 'paciente');

            $paciente  = $this->obtenerPacienteActual();
            $dashboard = $this->dashboardService->obtenerDashboard((int) $paciente->id);

            return $this->response->setJSON([
                'success' => true,
                'data'    => $dashboard,
            ]);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al obtener el dashboard del paciente: {exception}', ['exception' => $exception]);

            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => 'No se pudo actualizar el dashboard. Inténtalo más tarde.',
                ]);
        }
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
