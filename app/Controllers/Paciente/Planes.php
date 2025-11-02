<?php

namespace App\Controllers\Paciente;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Exceptions\PageForbiddenException;
use App\Models\UserModel;
use App\Services\PacientePlanService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;

class Planes extends BaseController
{
    private const FILTROS = [
        'activos'     => 'Activos',
        'futuros'     => 'Futuros',
        'finalizados' => 'Finalizados',
        'todos'       => 'Todos',
    ];

    private UserModel $userModel;

    private PacientePlanService $planService;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->planService = new PacientePlanService();
    }

    public function index()
    {
        $paciente = $this->obtenerPacienteActual();
        $estado   = (string) ($this->request->getGet('estado') ?? '');

        $resultado = $this->planService->obtenerPlanes((int) $paciente->id, $estado);

        return view('paciente/planes/index', $this->layoutData() + [
            'title'              => 'Planes de cuidado',
            'paciente'           => $paciente,
            'planes'             => $resultado['planes'],
            'conteos'            => $resultado['conteos'],
            'filtroActual'       => $resultado['filtro'],
            'filtrosDisponibles' => self::FILTROS,
        ]);
    }

    public function show(int $planId)
    {
        $paciente = $this->obtenerPacienteActual();

        $detalle = $this->planService->obtenerPlanDetalle((int) $paciente->id, $planId);

        return view('paciente/planes/show', $this->layoutData() + [
            'title'       => 'Detalle del plan',
            'paciente'    => $paciente,
            'plan'        => $detalle['plan'],
            'metricas'    => $detalle['metricas'],
            'actividades' => $detalle['actividades'],
        ]);
    }

    public function marcarActividad(int $actividadId): ResponseInterface
    {
        $paciente = $this->obtenerPacienteActual();
        $payload  = $this->obtenerPayload();

        $comentario = isset($payload['comentario']) && is_string($payload['comentario'])
            ? $payload['comentario']
            : null;

        try {
            $resultado = $this->planService->marcarActividad((int) $paciente->id, $actividadId, $comentario);

            return $this->response->setJSON([
                'success' => true,
                'data'    => $resultado,
                'message' => 'Actividad marcada como realizada.',
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ]);
        } catch (PageForbiddenException $exception) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ]);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al marcar actividad: {exception}', ['exception' => $exception]);

            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => 'No se pudo marcar la actividad. Inténtalo nuevamente.',
                ]);
        }
    }

    public function desmarcarActividad(int $actividadId): ResponseInterface
    {
        $paciente = $this->obtenerPacienteActual();

        try {
            $resultado = $this->planService->desmarcarActividad((int) $paciente->id, $actividadId);

            return $this->response->setJSON([
                'success' => true,
                'data'    => $resultado,
                'message' => 'Actividad desmarcada.',
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ]);
        } catch (PageForbiddenException $exception) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ]);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al desmarcar actividad: {exception}', ['exception' => $exception]);

            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => 'No se pudo desmarcar la actividad. Inténtalo nuevamente.',
                ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerPayload(): array
    {
        $jsonPayload = $this->request->getJSON(true);
        if (is_array($jsonPayload)) {
            return $jsonPayload;
        }

        $postPayload = $this->request->getPost();

        return is_array($postPayload) ? $postPayload : [];
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
