<?php

namespace App\Controllers\Paciente;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Exceptions\PageForbiddenException;
use App\Models\UserModel;
use App\Services\PacienteDiagnosticoService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Diagnosticos extends BaseController
{
    private const FILTROS = [
        'activos'    => 'Activos',
        'historicos' => 'Históricos',
        'todos'      => 'Todos',
    ];

    private UserModel $userModel;

    private PacienteDiagnosticoService $diagnosticoService;

    public function __construct()
    {
        $this->userModel           = new UserModel();
        $this->diagnosticoService  = new PacienteDiagnosticoService();
    }

    public function index()
    {
        session()->set('rol', 'paciente');

        $paciente = $this->obtenerPacienteActual();
        $estado   = (string) ($this->request->getGet('estado') ?? '');

        $resultado = $this->diagnosticoService->obtenerDiagnosticos((int) $paciente->id, $estado);

        return view('paciente/diagnosticos/index', $this->layoutData() + [
            'title'              => 'Diagnósticos',
            'paciente'           => $paciente,
            'diagnosticos'       => $resultado['diagnosticos'],
            'conteos'            => $resultado['conteos'],
            'filtroActual'       => $resultado['filtro'],
            'filtrosDisponibles' => self::FILTROS,
        ]);
    }

    public function show(int $diagnosticoId)
    {
        session()->set('rol', 'paciente');

        $paciente = $this->obtenerPacienteActual();
        $detalle  = $this->diagnosticoService->obtenerDiagnosticoDetalle((int) $paciente->id, $diagnosticoId);

        return view('paciente/diagnosticos/show', $this->layoutData() + [
            'title'       => 'Detalle del diagnóstico',
            'paciente'    => $paciente,
            'diagnostico' => $detalle['diagnostico'],
            'planes'      => $detalle['planes'],
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

            throw new PageForbiddenException('Acceso denegado para el usuario actual.');
        }

        $paciente = $this->userModel->findPrimeroActivoPorRol(UserModel::ROLE_PACIENTE);
        if ($paciente === null) {
            throw new PageNotFoundException('No existen pacientes activos configurados.');
        }

        $session->set('user_id', $paciente->id);

        return $paciente;
    }
}
