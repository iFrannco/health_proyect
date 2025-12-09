<?php

namespace App\Controllers\Medico;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\ActividadModel;
use App\Models\CategoriaActividadModel;
use App\Models\DiagnosticoModel;
use App\Models\EstadoActividadModel;
use App\Models\PlanEstandarActividadModel;
use App\Models\PlanEstandarModel;
use App\Models\PlanCuidadoModel;
use App\Models\UserModel;
use App\Libraries\CarePlanTemplate;
use App\Exceptions\PageForbiddenException;
use App\Services\PlanEstadoService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;

class Planes extends BaseController
{
    private DiagnosticoModel $diagnosticoModel;
    private PlanCuidadoModel $planCuidadoModel;
    private ActividadModel $actividadModel;
    private EstadoActividadModel $estadoActividadModel;
    private CategoriaActividadModel $categoriaActividadModel;
    private UserModel $userModel;
    private PlanEstandarModel $planEstandarModel;
    private PlanEstandarActividadModel $planEstandarActividadModel;
    private CarePlanTemplate $carePlanTemplate;

    public function __construct()
    {
        $this->diagnosticoModel     = new DiagnosticoModel();
        $this->planCuidadoModel     = new PlanCuidadoModel();
        $this->actividadModel       = new ActividadModel();
        $this->estadoActividadModel = new EstadoActividadModel();
        $this->categoriaActividadModel = new CategoriaActividadModel();
        $this->userModel            = new UserModel();
        $this->planEstandarModel    = new PlanEstandarModel();
        $this->planEstandarActividadModel = new PlanEstandarActividadModel();
        $this->carePlanTemplate     = new CarePlanTemplate();
    }

    public function index()
    {
        $medico = $this->obtenerMedicoActual();

        $planes = $this->planCuidadoModel
            ->asArray()
            ->select([
                'planes_cuidado.id',
                'planes_cuidado.nombre',
                'planes_cuidado.descripcion',
                'planes_cuidado.fecha_inicio',
                'planes_cuidado.fecha_fin',
                'planes_cuidado.fecha_creacion',
                'planes_cuidado.plan_estandar_id',
                'planes_cuidado.estado',
                'planes_cuidado.creador_user_id',
                'diagnosticos.descripcion AS diagnostico_descripcion',
                'paciente.id AS paciente_id',
                'paciente.nombre AS paciente_nombre',
                'paciente.apellido AS paciente_apellido',
            ])
            ->join('diagnosticos', 'diagnosticos.id = planes_cuidado.diagnostico_id', 'inner')
            ->join('users AS paciente', 'paciente.id = diagnosticos.destinatario_user_id', 'inner')
            ->where('planes_cuidado.creador_user_id', $medico->id)
            ->orderBy('planes_cuidado.fecha_creacion', 'DESC')
            ->findAll();

        $data = [
            'title'  => 'Planes de cuidado personalizados',
            'medico' => $medico,
            'planes' => $planes,
        ];

        return view('medico/planes/index', $this->layoutData() + $data);
    }

    public function buscarPacientes(): ResponseInterface
    {
        $medico = $this->obtenerMedicoActual();

        $termino = trim((string) ($this->request->getGet('q') ?? ''));
        $scope   = (string) ($this->request->getGet('scope') ?? '');

        if ($termino === '' || mb_strlen($termino) < 2) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'Ingresa al menos 2 caracteres para buscar por nombre o DNI.',
                ]);
        }

        $dniSoloDigitos = preg_replace('/\D+/', '', $termino);
        if ($dniSoloDigitos !== '' && mb_strlen($dniSoloDigitos) < 4) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'Ingresa al menos 4 dígitos para buscar por DNI.',
                ]);
        }

        try {
            $pacientes = $scope === 'diagnosticos'
                ? $this->userModel->buscarPacientesPorNombreODni(
                    $termino,
                    $dniSoloDigitos !== '' ? $dniSoloDigitos : null,
                    10
                )
                : $this->userModel->buscarPacientesConDiagnosticoDeMedico(
                    $medico->id,
                    $termino,
                    $dniSoloDigitos !== '' ? $dniSoloDigitos : null,
                    10
                );
        } catch (\Throwable $exception) {
            log_message('error', 'Error al buscar pacientes: {exception}', ['exception' => $exception]);

            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => 'No se pudo completar la búsqueda. Inténtalo nuevamente.',
                ]);
        }

        $resultado = array_map(static function (array $paciente) {
            return [
                'id'       => (int) ($paciente['id'] ?? 0),
                'nombre'   => $paciente['nombre'] ?? '',
                'apellido' => $paciente['apellido'] ?? '',
                'dni'      => $paciente['dni'] ?? '',
            ];
        }, $pacientes);

        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'pacientes' => $resultado,
            ],
        ]);
    }

    public function planesEstandarPorDiagnostico(): ResponseInterface
    {
        $diagnosticoId = (int) ($this->request->getGet('diagnostico_id') ?? 0);
        $pacienteId    = (int) ($this->request->getGet('paciente_id') ?? 0);

        if ($diagnosticoId <= 0) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'Diagnóstico inválido.',
                ]);
        }

        $diagnostico = $this->diagnosticoModel->asArray()
            ->select(['id', 'destinatario_user_id', 'tipo_diagnostico_id'])
            ->where('id', $diagnosticoId)
            ->first();

        if (! $diagnostico || ($pacienteId > 0 && (int) $diagnostico['destinatario_user_id'] !== $pacienteId)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'El diagnóstico no es válido para el paciente seleccionado.',
                ]);
        }

        $planes = $this->planEstandarModel->asArray()
            ->select(['plan_estandar.id', 'plan_estandar.nombre', 'plan_estandar.descripcion', 'plan_estandar.version', 'plan_estandar.tipo_diagnostico_id'])
            ->where('plan_estandar.vigente', 1)
            ->where('plan_estandar.tipo_diagnostico_id', $diagnostico['tipo_diagnostico_id'])
            ->orderBy('plan_estandar.nombre', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'planes' => $planes,
            ],
        ]);
    }

    public function previsualizarPlanEstandar(): ResponseInterface
    {
        $planEstandarId = (int) ($this->request->getPost('plan_estandar_id') ?? 0);
        $diagnosticoId  = (int) ($this->request->getPost('diagnostico_id') ?? 0);
        $pacienteId     = (int) ($this->request->getPost('paciente_id') ?? 0);
        $fechaInicio    = (string) ($this->request->getPost('fecha_inicio') ?? '');
        $fechaFin       = (string) ($this->request->getPost('fecha_fin') ?? '');

        if ($planEstandarId <= 0 || $diagnosticoId <= 0 || $fechaInicio === '') {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'Faltan datos para previsualizar la plantilla.',
                ]);
        }

        $diagnostico = $this->diagnosticoModel->asArray()
            ->select(['id', 'destinatario_user_id', 'tipo_diagnostico_id'])
            ->where('id', $diagnosticoId)
            ->first();

        if (! $diagnostico || ($pacienteId > 0 && (int) $diagnostico['destinatario_user_id'] !== $pacienteId)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'El diagnóstico no es válido para el paciente seleccionado.',
                ]);
        }

        $planEstandar = $this->planEstandarModel
            ->where('id', $planEstandarId)
            ->where('vigente', 1)
            ->first();

        if ($planEstandar === null || (int) $planEstandar->tipo_diagnostico_id !== (int) $diagnostico['tipo_diagnostico_id']) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'La plantilla seleccionada no es válida para este diagnóstico.',
                ]);
        }

        $actividadesPlantilla = $this->planEstandarActividadModel
            ->where('plan_estandar_id', $planEstandarId)
            ->where('vigente', 1)
            ->orderBy('orden', 'ASC')
            ->findAll();

        if (empty($actividadesPlantilla)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'La plantilla no tiene actividades vigentes.',
                ]);
        }

        $categoriaDefaultId = $this->obtenerCategoriaActividadDefaultId();
        if ($categoriaDefaultId === null) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => 'No hay categorías de actividad activas configuradas.',
                ]);
        }

        try {
            $resultado = $this->carePlanTemplate->materializar(
                $actividadesPlantilla,
                $fechaInicio,
                $fechaFin !== '' ? $fechaFin : null,
                $categoriaDefaultId
            );
        } catch (\Throwable $exception) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ]);
        }

        if (! empty($resultado['errores'])) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => $resultado['errores'][0] ?? 'No se pudo generar la plantilla.',
                    'errors'  => $resultado['errores'],
                ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'plan'        => [
                    'id'          => $planEstandar->id,
                    'nombre'      => (string) $planEstandar->nombre,
                    'descripcion' => (string) $planEstandar->descripcion,
                    'version'     => (int) $planEstandar->version,
                ],
                'actividades' => $resultado['actividades'],
                'fecha_fin_calculada' => $resultado['fecha_fin_calculada'] ?? null,
            ],
        ]);
    }

    public function create()
    {
        $medico = $this->obtenerMedicoActual();
        $pacienteSeleccionado = null;
        $pacienteIdOld        = (int) (old('paciente_id') ?? 0);

        if ($pacienteIdOld > 0) {
            $pacienteSeleccionado = $this->userModel->findActivoPorRol($pacienteIdOld, UserModel::ROLE_PACIENTE);
        }

        $diagnosticos = $this->diagnosticoModel->asArray()
            ->select([
                'diagnosticos.id',
                'diagnosticos.descripcion',
                'diagnosticos.destinatario_user_id',
            ])
            ->orderBy('diagnosticos.fecha_creacion', 'DESC')
            ->findAll();
        $categorias = $this->asegurarCategoriasAsignadas(
            $this->categoriaActividadModel->findActivas(),
            []
        );

        $data = [
            'title'                    => 'Nuevo plan personalizado',
            'medico'                   => $medico,
            'pacientes'                => [],
            'diagnosticos'             => $diagnosticos,
            'categoriasActividad'      => $categorias,
            'pacienteSeleccionado'     => $pacienteSeleccionado,
            'terminoBusquedaPaciente'  => old('busqueda_paciente', ''),
            'errors'                   => session()->getFlashdata('errors') ?? [],
            'actividadErrors'          => session()->getFlashdata('actividad_errors') ?? [],
        ];

        return view('medico/planes/create', $this->layoutData() + $data);
    }

    public function store()
    {
        $medico = $this->obtenerMedicoActual();

        $rules = [
            'paciente_id' => [
                'label' => 'Paciente',
                'rules' => 'required|is_natural_no_zero',
            ],
            'diagnostico_id' => [
                'label' => 'Diagnóstico',
                'rules' => 'required|is_natural_no_zero',
            ],
            'fecha_inicio' => [
                'label' => 'Fecha de inicio',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'fecha_fin' => [
                'label' => 'Fecha de fin',
                'rules' => 'permit_empty|valid_date[Y-m-d]',
            ],
            'nombre' => [
                'label' => 'Nombre del plan',
                'rules' => 'permit_empty|max_length[180]',
            ],
            'descripcion' => [
                'label' => 'Descripción del plan',
                'rules' => 'permit_empty|max_length[2000]',
            ],
            'plan_estandar_id' => [
                'label' => 'Plan de cuidado estándar',
                'rules' => 'permit_empty|is_natural_no_zero',
            ],
        ];

        if (! $this->validate($rules)) {
            return $this->redirectBackWithErrors('Revisa los datos del plan.', $this->validator->getErrors());
        }

        $pacienteId    = (int) $this->request->getPost('paciente_id');
        $diagnosticoId = (int) $this->request->getPost('diagnostico_id');
        $fechaInicio   = $this->request->getPost('fecha_inicio');
        $planEstandarId = (int) ($this->request->getPost('plan_estandar_id') ?? 0);
        $usaPlantilla   = $planEstandarId > 0;
        $fechaFinPost   = $this->request->getPost('fecha_fin');

        $paciente = $this->userModel->findActivoPorRol($pacienteId, UserModel::ROLE_PACIENTE);
        if (! $paciente) {
            return $this->redirectBackWithErrors('El paciente seleccionado no es válido.', [
                'paciente_id' => 'Paciente inexistente o inactivo.',
            ]);
        }

        $diagnostico = $this->diagnosticoModel->asArray()
            ->where('id', $diagnosticoId)
            ->first();

        if (! $diagnostico || (int) $diagnostico['destinatario_user_id'] !== $paciente->id) {
            return $this->redirectBackWithErrors('Selecciona un diagnóstico válido para el paciente.', [
                'diagnostico_id' => 'El diagnóstico no pertenece al paciente seleccionado.',
            ]);
        }

        $planEstandar = null;
        $fechaFin = $fechaFinPost;
        if ($usaPlantilla) {
            $planEstandar = $this->planEstandarModel
                ->where('id', $planEstandarId)
                ->where('vigente', 1)
                ->first();

            if ($planEstandar === null || (int) $planEstandar->tipo_diagnostico_id !== (int) $diagnostico['tipo_diagnostico_id']) {
                return $this->redirectBackWithErrors('La plantilla seleccionada no es válida para el diagnóstico elegido.', [
                    'plan_estandar_id' => 'Selecciona un plan estándar vigente compatible con el diagnóstico.',
                ]);
            }

            $actividadesPlantilla = $this->planEstandarActividadModel
                ->where('plan_estandar_id', $planEstandarId)
                ->where('vigente', 1)
                ->orderBy('orden', 'ASC')
                ->findAll();

            if (empty($actividadesPlantilla)) {
                return $this->redirectBackWithErrors('La plantilla seleccionada no tiene actividades vigentes.', [
                    'plan_estandar_id' => 'Selecciona una plantilla con actividades activas.',
                ]);
            }

            $categoriaDefaultId = $this->obtenerCategoriaActividadDefaultId();
            if ($categoriaDefaultId === null) {
                return $this->redirectBackWithErrors('No hay categorías de actividad activas disponibles. Contacta al administrador.', [
                    'actividad_categoria_id' => 'Configura al menos una categoría activa.',
                ]);
            }

            try {
                $generacion = $this->carePlanTemplate->materializar(
                    $actividadesPlantilla,
                    $fechaInicio,
                    $fechaFinPost ?: null,
                    $categoriaDefaultId
                );
            } catch (\Throwable $exception) {
                return $this->redirectBackWithErrors('No se pudo generar las actividades desde la plantilla.', [
                    'plan_estandar_id' => $exception->getMessage(),
                ]);
            }

            if (! empty($generacion['errores'])) {
                return $this->redirectBackWithErrors('Revisa la plantilla seleccionada.', [
                    'plan_estandar_id' => $generacion['errores'][0] ?? 'La plantilla contiene datos inválidos.',
                ]);
            }

            $fechaFin = $generacion['fecha_fin_calculada'] ?? $fechaFinPost;
            if (! $fechaFin) {
                return $this->redirectBackWithErrors('No se pudo calcular la fecha fin del plan a partir de la plantilla.', [
                    'fecha_fin' => 'La plantilla no devolvió una vigencia calculada.',
                ]);
            }

            $actividadesData = [
                'actividades'       => $generacion['actividades'] ?? [],
                'erroresGenerales'  => [],
                'erroresPorIndice'  => [],
            ];
        } else {
            $actividadesData = $this->extraerActividadesDesdeRequest($fechaInicio, $fechaFin);
            if (! empty($actividadesData['erroresGenerales'])) {
                return $this->redirectBackWithActividadErrors(
                    'Revisa las actividades ingresadas.',
                    $actividadesData['erroresGenerales'],
                    $actividadesData['erroresPorIndice']
                );
            }
        }

        $estadoPendiente = $this->estadoActividadModel->findBySlug('pendiente');
        if ($estadoPendiente === null) {
            return $this->redirectBackWithErrors('No se encontró el estado inicial de actividades. Contacta al administrador.', []);
        }

        if ($fechaInicio > $fechaFin) {
            return $this->redirectBackWithErrors('La fecha de inicio no puede ser posterior a la fecha de fin.', [
                'fecha_inicio' => 'Debe ser anterior o igual a la fecha de fin.',
                'fecha_fin'    => 'Debe ser posterior o igual a la fecha de inicio.',
            ]);
        }

        $estadoPlan = PlanEstadoService::calcular(null, $fechaInicio, $fechaFin);

        $planData = [
            'diagnostico_id' => $diagnosticoId,
            'creador_user_id' => $medico->id,
            'plan_estandar_id' => $usaPlantilla ? $planEstandarId : null,
            'nombre'         => $usaPlantilla ? ($planEstandar->nombre ?? null) : ($this->request->getPost('nombre') ?: null),
            'descripcion'    => $usaPlantilla ? ($planEstandar->descripcion ?? null) : ($this->request->getPost('descripcion') ?: null),
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'fecha_inicio'   => $fechaInicio,
            'fecha_fin'      => $fechaFin,
            'estado'         => $estadoPlan['estado'],
        ];

        if (empty($actividadesData['actividades'])) {
            return $this->redirectBackWithErrors('Se requiere al menos una actividad para crear el plan.', [
                'plan_estandar_id' => 'La plantilla no generó actividades válidas.',
            ]);
        }

        $db = $this->planCuidadoModel->db;
        $db->transBegin();

        try {
            $planId = $this->planCuidadoModel->insert($planData, true);
            if ($planId === false) {
                throw new DataException('No se pudo crear el plan de cuidado.');
            }

            foreach ($actividadesData['actividades'] as $actividad) {
                $actividadPayload = [
                    'plan_id'                => $planId,
                    'nombre'                 => $actividad['nombre'],
                    'descripcion'            => $actividad['descripcion'],
                    'fecha_creacion'         => date('Y-m-d H:i:s'),
                    'fecha_inicio'           => $actividad['fecha_inicio'],
                    'fecha_fin'              => $actividad['fecha_fin'],
                    'estado_id'              => $estadoPendiente['id'],
                    'categoria_actividad_id' => $actividad['categoria_actividad_id'],
                    'validado'               => null,
                    'fecha_validacion'       => null,
                ];

                if ($this->actividadModel->insert($actividadPayload) === false) {
                    throw new DataException('No se pudo crear una actividad del plan.');
                }
            }
        } catch (DataException | DatabaseException $exception) {
            $db->transRollback();

            return $this->redirectBackWithActividadErrors(
                'No se pudo crear el plan de cuidado.',
                [$exception->getMessage()],
                $actividadesData['erroresPorIndice']
            );
        }

        try {
            $db->transCommit();
        } catch (DatabaseException $exception) {
            return $this->redirectBackWithActividadErrors(
                'No se pudo crear el plan de cuidado.',
                [$exception->getMessage()],
                $actividadesData['erroresPorIndice']
            );
        }

        session()->setFlashdata('success', 'Plan de cuidado creado con éxito.');

        return redirect()->to(site_url('medico/planes'));
    }

    public function show(int $planId)
    {
        $medico = $this->obtenerMedicoActual();
        $plan   = $this->findPlanDetalleParaMedico($planId, $medico->id);

        if ($plan === null) {
            return $this->planNoDisponibleRedirect();
        }

        $estadoPlan = PlanEstadoService::calcular(
            $plan['estado'] ?? null,
            $plan['fecha_inicio'] ?? null,
            $plan['fecha_fin'] ?? null
        );
        $plan['estado']             = $estadoPlan['estado'];
        $plan['estado_etiqueta']    = $estadoPlan['etiqueta'];
        $plan['se_puede_finalizar'] = $estadoPlan['sePuedeFinalizar'];

        $actividades     = $this->actividadModel->findPorPlanConEstado($plan['id']);
        $estadosCatalogo = $this->estadoActividadModel->findActivos();
        $resumen         = $this->construirResumenActividades($actividades, $estadosCatalogo);

        $data = [
            'title'        => 'Detalle del plan personalizado',
            'medico'       => $medico,
            'plan'         => $plan,
            'actividades'  => $actividades,
            'resumen'      => $resumen,
            'estados'      => $estadosCatalogo,
            'planEstado'   => $estadoPlan,
        ];

        return view('medico/planes/show', $this->layoutData() + $data);
    }

    public function validarActividad(int $actividadId): ResponseInterface
    {
        $medico = $this->obtenerMedicoActual();

        try {
            $resultado = $this->procesarValidacionActividad($medico->id, $actividadId);

            $status    = $resultado['status'] ?? 'validated';
            $statusCode = ResponseInterface::HTTP_OK;
            $success   = in_array($status, ['validated', 'already_validated'], true);

            if ($status === 'estado_invalido') {
                $statusCode = ResponseInterface::HTTP_BAD_REQUEST;
                $success    = false;
            }

            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'success' => $success,
                    'status'  => $status,
                    'message' => $resultado['message'] ?? '',
                    'data'    => [
                        'actividad' => $resultado['actividad'] ?? null,
                        'resumen'   => $resultado['resumen'] ?? null,
                    ],
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
            log_message('error', 'Error al validar actividad: {exception}', ['exception' => $exception]);

            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => 'No se pudo validar la actividad. Inténtalo nuevamente.',
                ]);
        }
    }
    public function desvalidarActividad(int $actividadId): ResponseInterface
    {
        $medico = $this->obtenerMedicoActual();

        try {
            $resultado = $this->procesarDesvalidacionActividad($medico->id, $actividadId);

            $status     = $resultado['status'] ?? 'unvalidated';
            $success    = in_array($status, ['unvalidated', 'already_unvalidated'], true);
            $statusCode = $success ? ResponseInterface::HTTP_OK : ResponseInterface::HTTP_BAD_REQUEST;

            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'success' => $success,
                    'status'  => $status,
                    'message' => $resultado['message'] ?? '',
                    'data'    => [
                        'actividad' => $resultado['actividad'] ?? null,
                        'resumen'   => $resultado['resumen'] ?? null,
                    ],
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
            log_message('error', 'Error al desvalidar actividad: {exception}', ['exception' => $exception]);

            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => 'No se pudo desvalidar la actividad. Inténtalo nuevamente.',
                ]);
        }
    }

    public function edit(int $planId)
    {
        $medico = $this->obtenerMedicoActual();
        $plan   = $this->findPlanDetalleParaMedico($planId, $medico->id);

        if ($plan === null) {
            return $this->planNoDisponibleRedirect();
        }

        $estadoPlan = PlanEstadoService::calcular(
            $plan['estado'] ?? null,
            $plan['fecha_inicio'] ?? null,
            $plan['fecha_fin'] ?? null
        );

        if ($estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO) {
            session()->setFlashdata('error', 'No puedes editar un plan finalizado.');

            return redirect()->to(route_to('medico_planes_show', $plan['id']));
        }

        $actividades = $this->actividadModel->findPorPlanConEstado($plan['id']);
        $categorias  = $this->asegurarCategoriasAsignadas(
            $this->categoriaActividadModel->findActivas(),
            $actividades
        );

        $data = [
            'title'           => 'Editar plan personalizado',
            'medico'          => $medico,
            'plan'            => $plan,
            'actividades'     => $actividades,
            'pacientes'       => $this->userModel->findActivosPorRol(UserModel::ROLE_PACIENTE),
            'diagnosticos'    => $this->diagnosticoModel->asArray()
                ->select([
                    'diagnosticos.id',
                    'diagnosticos.descripcion',
                    'diagnosticos.destinatario_user_id',
                ])
                ->orderBy('diagnosticos.fecha_creacion', 'DESC')
                ->findAll(),
            'categoriasActividad' => $categorias,
            'errors'          => session()->getFlashdata('errors') ?? [],
            'actividadErrors' => session()->getFlashdata('actividad_errors') ?? [],
        ];

        return view('medico/planes/edit', $this->layoutData() + $data);
    }

    public function update(int $planId)
    {
        $medico = $this->obtenerMedicoActual();
        $plan   = $this->findPlanDetalleParaMedico($planId, $medico->id);

        if ($plan === null) {
            return $this->planNoDisponibleRedirect();
        }

        $estadoPlan = PlanEstadoService::calcular(
            $plan['estado'] ?? null,
            $plan['fecha_inicio'] ?? null,
            $plan['fecha_fin'] ?? null
        );

        if ($estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO) {
            session()->setFlashdata('error', 'No puedes editar un plan finalizado.');

            return redirect()->to(route_to('medico_planes_show', $plan['id']));
        }

        $rules = [
            'fecha_inicio' => [
                'label' => 'Fecha de inicio',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'fecha_fin' => [
                'label' => 'Fecha de fin',
                'rules' => 'permit_empty|valid_date[Y-m-d]',
            ],
            'nombre' => [
                'label' => 'Nombre del plan',
                'rules' => 'permit_empty|max_length[180]',
            ],
            'descripcion' => [
                'label' => 'Descripción del plan',
                'rules' => 'permit_empty|max_length[2000]',
            ],
        ];

        if (! $this->validate($rules)) {
            return $this->redirectBackWithErrors('Revisa los datos del plan.', $this->validator->getErrors());
        }

        $fechaInicio = $this->request->getPost('fecha_inicio');
        $fechaFin    = $this->request->getPost('fecha_fin');
        if ($usaPlantilla) {
            $fechaFin = $plan['fecha_fin']; // mantener fecha fin calculada al crear
        }

        if ($fechaInicio > $fechaFin) {
            return $this->redirectBackWithErrors('La fecha de inicio no puede ser posterior a la fecha de fin.', [
                'fecha_inicio' => 'Debe ser anterior o igual a la fecha de fin.',
                'fecha_fin'    => 'Debe ser posterior o igual a la fecha de inicio.',
            ]);
        }

        $usaPlantilla = (int) ($plan['plan_estandar_id'] ?? 0) > 0;

        $actividadesData = [
            'actividades'       => [],
            'erroresGenerales'  => [],
            'erroresPorIndice'  => [],
        ];

        $actividadesExistentes = $this->actividadModel
            ->where('plan_id', $plan['id'])
            ->findAll();

        if (! $usaPlantilla) {
            $actividadesData = $this->extraerActividadesDesdeRequest($fechaInicio, $fechaFin);
            if (! empty($actividadesData['erroresGenerales'])) {
                return $this->redirectBackWithActividadErrors(
                    'Revisa las actividades ingresadas.',
                    $actividadesData['erroresGenerales'],
                    $actividadesData['erroresPorIndice']
                );
            }

            $actividadesPorId = [];
            foreach ($actividadesExistentes as $actividad) {
                $actividadesPorId[$actividad->id] = $actividad;
            }

            $idsInvalidos = array_filter(array_map(static function ($actividad) {
                return $actividad['id'] ?? null;
            }, $actividadesData['actividades']), static fn ($id) => $id !== null && $id > 0);

            $idsInvalidos = array_filter($idsInvalidos, static function ($id) use ($actividadesPorId) {
                return ! array_key_exists($id, $actividadesPorId);
            });

            if (! empty($idsInvalidos)) {
                return $this->redirectBackWithActividadErrors(
                    'No se pudo actualizar el plan.',
                    ['Una de las actividades no es válida para este plan.'],
                    $actividadesData['erroresPorIndice']
                );
            }

            $estadoPendiente = $this->estadoActividadModel->findBySlug('pendiente');
            if ($estadoPendiente === null) {
                return $this->redirectBackWithErrors('No se encontró el estado inicial de actividades. Contacta al administrador.', []);
            }
        } else {
            foreach ($actividadesExistentes as $actividad) {
                $fechaInicioActividad = $actividad->fecha_inicio instanceof \CodeIgniter\I18n\Time
                    ? $actividad->fecha_inicio->toDateString()
                    : (string) $actividad->fecha_inicio;

                $fechaFinActividad = $actividad->fecha_fin instanceof \CodeIgniter\I18n\Time
                    ? $actividad->fecha_fin->toDateString()
                    : (string) $actividad->fecha_fin;

                if (
                    ($fechaInicioActividad !== '' && $fechaInicioActividad < $fechaInicio)
                    || ($fechaFinActividad !== '' && $fechaFinActividad > $fechaFin)
                ) {
                    return $this->redirectBackWithErrors(
                        'Las fechas del plan deben cubrir las actividades generadas por la plantilla.',
                        [
                            'fecha_inicio' => 'No puede ser posterior al inicio de las actividades generadas.',
                            'fecha_fin'    => 'No puede ser anterior al fin de las actividades generadas.',
                        ]
                    );
                }
            }
        }

        $db = $this->planCuidadoModel->db;
        $db->transBegin();

        try {
            $estadoRecalculado = PlanEstadoService::calcular(null, $fechaInicio, $fechaFin);
            $planPayload = [
                'nombre'      => $usaPlantilla ? ($plan['nombre'] ?? null) : ($this->request->getPost('nombre') ?: null),
                'descripcion' => $usaPlantilla ? ($plan['descripcion'] ?? null) : ($this->request->getPost('descripcion') ?: null),
                'fecha_inicio'=> $fechaInicio,
                'fecha_fin'   => $fechaFin,
                'estado'      => $estadoRecalculado['estado'],
            ];

            if ($this->planCuidadoModel->update($plan['id'], $planPayload) === false) {
                throw new DataException('No se pudo actualizar el plan de cuidado.');
            }

            if (! $usaPlantilla) {
                $idsPersistidos = [];

                foreach ($actividadesData['actividades'] as $actividadInput) {
                    $actividadId = $actividadInput['id'] ?? null;

                    if ($actividadId !== null && $actividadId > 0) {
                        $idsPersistidos[] = $actividadId;
                        $actividadEntity = $actividadesPorId[$actividadId] ?? null;
                        $payload = [
                            'nombre'                 => $actividadInput['nombre'],
                            'descripcion'            => $actividadInput['descripcion'],
                            'fecha_inicio'           => $actividadInput['fecha_inicio'],
                            'fecha_fin'              => $actividadInput['fecha_fin'],
                            'categoria_actividad_id' => $actividadInput['categoria_actividad_id'],
                        ];

                        if ($actividadEntity !== null) {
                            $fechaInicioOriginal = $actividadEntity->fecha_inicio;
                            if ($fechaInicioOriginal instanceof \CodeIgniter\I18n\Time) {
                                $fechaInicioOriginal = $fechaInicioOriginal->toDateString();
                            }

                            $fechaFinOriginal = $actividadEntity->fecha_fin;
                            if ($fechaFinOriginal instanceof \CodeIgniter\I18n\Time) {
                                $fechaFinOriginal = $fechaFinOriginal->toDateString();
                            }

                            $haCambiado = (
                                (string) $actividadEntity->nombre !== $actividadInput['nombre']
                                || (string) $actividadEntity->descripcion !== $actividadInput['descripcion']
                                || (string) $fechaInicioOriginal !== $actividadInput['fecha_inicio']
                                || (string) $fechaFinOriginal !== $actividadInput['fecha_fin']
                                || (int) $actividadEntity->categoria_actividad_id !== (int) $actividadInput['categoria_actividad_id']
                            );

                            $cambioSoloCategoria = (int) $actividadEntity->categoria_actividad_id !== (int) $actividadInput['categoria_actividad_id'];

                            if ($cambioSoloCategoria && $actividadEntity->validado === true) {
                                $db->transRollback();

                                $erroresCategoria = [
                                    $actividadInput['indice'] ?? 0 => [
                                        'categoria' => 'No puedes cambiar la categoría de una actividad ya validada.',
                                    ],
                                ];

                                return $this->redirectBackWithActividadErrors(
                                    'No se pudo actualizar el plan.',
                                    ['Las actividades validadas mantienen su categoría.'],
                                    $erroresCategoria + $actividadesData['erroresPorIndice']
                                );
                            }

                            if ($haCambiado && $actividadEntity->validado === true) {
                                $payload['validado'] = null;
                                $payload['estado_id'] = $estadoPendiente['id'];
                                $payload['fecha_validacion'] = null;
                            }
                        }

                        if ($this->actividadModel->update($actividadId, $payload) === false) {
                            throw new DataException('No se pudo actualizar una de las actividades.');
                        }

                        continue;
                    }

                    $payload = [
                        'plan_id'        => $plan['id'],
                        'nombre'         => $actividadInput['nombre'],
                        'descripcion'    => $actividadInput['descripcion'],
                        'fecha_creacion' => date('Y-m-d H:i:s'),
                        'fecha_inicio'   => $actividadInput['fecha_inicio'],
                        'fecha_fin'      => $actividadInput['fecha_fin'],
                        'estado_id'      => $estadoPendiente['id'],
                        'categoria_actividad_id' => $actividadInput['categoria_actividad_id'],
                        'validado'       => null,
                        'fecha_validacion'     => null,
                    ];

                    if ($this->actividadModel->insert($payload) === false) {
                        throw new DataException('No se pudo crear una actividad del plan.');
                    }
                }

                foreach ($actividadesPorId as $actividadId => $actividadEntity) {
                    if (in_array($actividadId, $idsPersistidos, true)) {
                        continue;
                    }

                    if ($this->actividadModel->delete($actividadId) === false) {
                        throw new DataException('No se pudo eliminar una actividad del plan.');
                    }
                }
            }
        } catch (DataException | DatabaseException $exception) {
            $db->transRollback();

            return $this->redirectBackWithActividadErrors(
                'No se pudo actualizar el plan de cuidado.',
                [$exception->getMessage()],
                $actividadesData['erroresPorIndice']
            );
        }

        try {
            $db->transCommit();
        } catch (DatabaseException $exception) {
            return $this->redirectBackWithActividadErrors(
                'No se pudo actualizar el plan de cuidado.',
                [$exception->getMessage()],
                $actividadesData['erroresPorIndice']
            );
        }

        session()->setFlashdata('success', 'Plan de cuidado actualizado con éxito.');

        return redirect()->to(route_to('medico_planes_show', $plan['id']));
    }

    public function finalizar(int $planId)
    {
        $medico = $this->obtenerMedicoActual();
        $plan   = $this->findPlanDetalleParaMedico($planId, $medico->id);

        if ($plan === null) {
            return $this->planNoDisponibleRedirect();
        }

        $estadoPlan = PlanEstadoService::calcular(
            $plan['estado'] ?? null,
            $plan['fecha_inicio'] ?? null,
            $plan['fecha_fin'] ?? null
        );

        if ($estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO) {
            session()->setFlashdata('info', 'El plan ya está finalizado.');

            return redirect()->to(route_to('medico_planes_show', $plan['id']));
        }

        if ($this->planCuidadoModel->update($plan['id'], ['estado' => PlanEstadoService::ESTADO_FINALIZADO]) === false) {
            session()->setFlashdata('error', 'No se pudo finalizar el plan. Inténtalo nuevamente.');

            return redirect()->to(route_to('medico_planes_show', $plan['id']));
        }

        session()->setFlashdata('success', 'Plan finalizado con éxito.');

        return redirect()->to(route_to('medico_planes_show', $plan['id']));
    }

    public function delete(int $planId)
    {
        $medico = $this->obtenerMedicoActual();
        $plan   = $this->findPlanDetalleParaMedico($planId, $medico->id);

        if ($plan === null) {
            return $this->planNoDisponibleRedirect();
        }

        $estadoPlan = PlanEstadoService::calcular(
            $plan['estado'] ?? null,
            $plan['fecha_inicio'] ?? null,
            $plan['fecha_fin'] ?? null
        );

        if ($estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO) {
            session()->setFlashdata('error', 'No puedes eliminar un plan finalizado.');

            return redirect()->to(route_to('medico_planes_show', $plan['id']));
        }

        $db = $this->planCuidadoModel->db;
        $db->transBegin();

        try {
            if ($this->actividadModel->where('plan_id', $plan['id'])->delete() === false) {
                throw new DataException('No se pudieron eliminar las actividades relacionadas.');
            }

            if ($this->planCuidadoModel->delete($plan['id']) === false) {
                throw new DataException('No se pudo eliminar el plan de cuidado.');
            }
        } catch (DataException | DatabaseException $exception) {
            $db->transRollback();

            session()->setFlashdata('error', 'No se pudo eliminar el plan de cuidado.');
            session()->setFlashdata('errors', ['general' => $exception->getMessage()]);

            return redirect()->to(route_to('medico_planes_show', $plan['id']));
        }

        try {
            $db->transCommit();
        } catch (DatabaseException $exception) {
            session()->setFlashdata('error', 'No se pudo eliminar el plan de cuidado.');
            session()->setFlashdata('errors', ['general' => $exception->getMessage()]);

            return redirect()->to(route_to('medico_planes_show', $plan['id']));
        }

        session()->setFlashdata('success', 'Plan de cuidado eliminado con éxito.');

        return redirect()->to(route_to('medico_planes_index'));
    }

    private function obtenerCategoriaActividadDefaultId(): ?int
    {
        $categoriasActivas = $this->categoriaActividadModel->findActivas();
        if (! empty($categoriasActivas)) {
            foreach ($categoriasActivas as $categoria) {
                if ((int) ($categoria['id'] ?? 0) === 1) {
                    return 1;
                }
            }

            $primera = $categoriasActivas[0]['id'] ?? null;
            return $primera !== null ? (int) $primera : null;
        }

        // Si no hay categorías activas, se crea una genérica para no bloquear la generación.
        $categoriaDefault = [
            'nombre'       => 'General',
            'descripcion'  => 'Categoría por defecto para actividades generadas',
            'color_hex'    => '#6c757d',
            'activo'       => 1,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        $categoriaId = $this->categoriaActividadModel->insert($categoriaDefault, true);

        return $categoriaId !== false ? (int) $categoriaId : null;
    }

    private function extraerActividadesDesdeRequest(?string $planFechaInicio = null, ?string $planFechaFin = null): array
    {
        $nombres       = (array) $this->request->getPost('actividad_nombre');
        $descripciones = (array) $this->request->getPost('actividad_descripcion');
        $fechasInicio  = (array) $this->request->getPost('actividad_fecha_inicio');
        $fechasFin     = (array) $this->request->getPost('actividad_fecha_fin');
        $categoriasIds = (array) $this->request->getPost('actividad_categoria_id');
        $ids           = (array) $this->request->getPost('actividad_id');

        $actividades       = [];
        $erroresGenerales  = [];
        $erroresPorIndice  = [];

        $categoriasActivas = $this->categoriaActividadModel->findActivas();
        $categoriasActivasIds = array_column($categoriasActivas, 'id');
        $categoriasActivasLookup = array_fill_keys(array_map('intval', $categoriasActivasIds), true);

        if (empty($categoriasActivasLookup)) {
            $erroresGenerales[] = 'No hay categorías de actividad activas disponibles. Contacta al administrador.';

            return [
                'actividades'      => [],
                'erroresGenerales' => $erroresGenerales,
                'erroresPorIndice' => $erroresPorIndice,
            ];
        }

        $total = max(count($nombres), count($descripciones), count($fechasInicio), count($fechasFin), count($categoriasIds));
        $total = max($total, count($ids));
        if ($total === 0) {
            $erroresGenerales[] = 'Debes agregar al menos una actividad al plan.';

            return [
                'actividades'       => [],
                'erroresGenerales'  => $erroresGenerales,
                'erroresPorIndice'  => $erroresPorIndice,
            ];
        }

        for ($index = 0; $index < $total; $index++) {
            $nombre      = trim($nombres[$index] ?? '');
            $descripcion = trim($descripciones[$index] ?? '');
            $fechaInicio = $fechasInicio[$index] ?? '';
            $fechaFin    = $fechasFin[$index] ?? '';
            $categoriaId = isset($categoriasIds[$index]) && $categoriasIds[$index] !== ''
                ? (int) $categoriasIds[$index]
                : 0;
            $actividadId = isset($ids[$index]) && $ids[$index] !== ''
                ? (int) $ids[$index]
                : null;

            $erroresFila = [];

            if ($nombre === '') {
                $erroresFila['nombre'] = 'El nombre es obligatorio.';
            } elseif (strlen($nombre) > 120) {
                $erroresFila['nombre'] = 'El nombre no debe superar los 120 caracteres.';
            }

            if ($descripcion !== '' && strlen($descripcion) > 2000) {
                $erroresFila['descripcion'] = 'La descripción no debe superar los 2000 caracteres.';
            }

            if ($fechaInicio === '') {
                $erroresFila['fecha_inicio'] = 'La fecha de inicio es obligatoria.';
            }

            if ($fechaFin === '') {
                $erroresFila['fecha_fin'] = 'La fecha de fin es obligatoria.';
            }

            if ($fechaInicio !== '' && $fechaFin !== '' && $fechaInicio > $fechaFin) {
                $erroresFila['fecha_fin'] = 'La fecha de fin debe ser igual o posterior a la fecha de inicio.';
            }

            if ($planFechaInicio !== null && $fechaInicio !== '' && $fechaInicio < $planFechaInicio) {
                $erroresFila['fecha_inicio'] = 'La fecha de inicio debe estar dentro del período del plan.';
            }

            if ($planFechaFin !== null && $fechaFin !== '' && $fechaFin > $planFechaFin) {
                $erroresFila['fecha_fin'] = 'La fecha de fin debe estar dentro del período del plan.';
            }

            if ($categoriaId <= 0) {
                $erroresFila['categoria'] = 'La categoría es obligatoria.';
            } elseif (! isset($categoriasActivasLookup[$categoriaId])) {
                $erroresFila['categoria'] = 'Selecciona una categoría válida.';
            }

            if (! empty($erroresFila)) {
                $erroresPorIndice[$index] = $erroresFila;

                continue;
            }

            $actividades[] = [
                'id'                     => $actividadId,
                'indice'                 => $index,
                'nombre'                 => $nombre,
                // Guardamos vacío en lugar de null para respetar columnas NOT NULL.
                'descripcion'            => $descripcion === '' ? '' : $descripcion,
                'fecha_inicio'           => $fechaInicio,
                'fecha_fin'              => $fechaFin,
                'categoria_actividad_id' => $categoriaId,
            ];
        }

        if (! empty($erroresPorIndice)) {
            $erroresGenerales[] = 'Las actividades cargadas contienen errores. Corrígelos antes de guardar.';
        } elseif (empty($actividades)) {
            $erroresGenerales[] = 'Las actividades cargadas contienen errores. Corrígelos antes de guardar.';
        }

        return [
            'actividades'       => $actividades,
            'erroresGenerales'  => $erroresGenerales,
            'erroresPorIndice'  => $erroresPorIndice,
        ];
    }

    private function findPlanDetalleParaMedico(int $planId, int $medicoId): ?array
    {
        return $this->planCuidadoModel
            ->asArray()
            ->select([
                'planes_cuidado.id',
                'planes_cuidado.diagnostico_id',
                'planes_cuidado.creador_user_id',
                'planes_cuidado.nombre',
                'planes_cuidado.descripcion',
                'planes_cuidado.fecha_creacion',
                'planes_cuidado.fecha_inicio',
                'planes_cuidado.fecha_fin',
                'planes_cuidado.estado',
                'planes_cuidado.plan_estandar_id',
                'diagnosticos.descripcion AS diagnostico_descripcion',
                'diagnosticos.destinatario_user_id AS paciente_id',
                'paciente.nombre AS paciente_nombre',
                'paciente.apellido AS paciente_apellido',
            ])
            ->join('diagnosticos', 'diagnosticos.id = planes_cuidado.diagnostico_id', 'inner')
            ->join('users AS paciente', 'paciente.id = diagnosticos.destinatario_user_id', 'inner')
            ->where('planes_cuidado.id', $planId)
            ->where('planes_cuidado.creador_user_id', $medicoId)
            ->first();
    }

    /**
     * @param array<int, array<string, mixed>> $actividades
     * @param array<int, array<string, mixed>> $estadosCatalogo
     */
    private function construirResumenActividades(array $actividades, array $estadosCatalogo): array
    {
        $resumen = [
            'total'        => count($actividades),
            'porEstado'    => [],
            'validadas'    => 0,
            'noValidadas'  => 0,
        ];

        foreach ($estadosCatalogo as $estado) {
            $slug = (string) ($estado['slug'] ?? $estado['id'] ?? 'estado');
            $resumen['porEstado'][$slug] = [
                'slug'   => $slug,
                'nombre' => $estado['nombre'] ?? ucfirst(str_replace('_', ' ', $slug)),
                'total'  => 0,
                'orden'  => (int) ($estado['orden'] ?? 0),
            ];
        }

        foreach ($actividades as $actividad) {
            $slug = $actividad['estado_slug'] ?? 'desconocido';
            if (! isset($resumen['porEstado'][$slug])) {
                $resumen['porEstado'][$slug] = [
                    'slug'   => $slug,
                    'nombre' => $actividad['estado_nombre'] ?? ucfirst(str_replace('_', ' ', $slug)),
                    'total'  => 0,
                    'orden'  => PHP_INT_MAX,
                ];
            }

            $resumen['porEstado'][$slug]['total']++;

            $validado = $actividad['validado'];
            if ($validado === null || $validado === false || $validado === '0') {
                $resumen['noValidadas']++;
            } else {
                $resumen['validadas']++;
            }
        }

        usort($resumen['porEstado'], static fn ($a, $b) => $a['orden'] <=> $b['orden']);

        return $resumen;
    }

    /**
     * @return array{
     *     status: string,
     *     message: string,
     *     actividad: array<string, mixed>,
     *     resumen: array<string, mixed>
     * }
     */
    private function procesarValidacionActividad(int $medicoId, int $actividadId): array
    {
        $contexto = $this->obtenerActividadContexto($medicoId, $actividadId);

        $estadoPlan = PlanEstadoService::calcular(
            $contexto['plan_estado'] ?? null,
            $contexto['plan_fecha_inicio'] ?? null,
            $contexto['plan_fecha_fin'] ?? null
        );

        if ($estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO) {
            throw new InvalidArgumentException('No puedes validar actividades de un plan finalizado.');
        }

        $estadoCompletada = $this->estadoActividadModel->findBySlug('completada');
        if ($estadoCompletada === null) {
            throw new InvalidArgumentException('No se encontró el estado completada.');
        }

        $planId = (int) ($contexto['plan_id'] ?? 0);

        if (($contexto['estado_slug'] ?? '') !== 'completada') {
            $datos = $this->obtenerActividadYResumen($planId, $actividadId);

            return [
                'status'    => 'estado_invalido',
                'message'   => 'La actividad ya no está en estado "completada".',
                'actividad' => $this->prepararActividadRespuesta($datos['actividad']),
                'resumen'   => $datos['resumen'],
            ];
        }

        if ($this->esValorVerdadero($contexto['validado'] ?? null)) {
            $datos = $this->obtenerActividadYResumen($planId, $actividadId);

            return [
                'status'    => 'already_validated',
                'message'   => 'Esta actividad ya fue validada.',
                'actividad' => $this->prepararActividadRespuesta($datos['actividad']),
                'resumen'   => $datos['resumen'],
            ];
        }

        $now                 = date('Y-m-d H:i:s');
        $estadoCompletadaId  = (int) ($estadoCompletada['id'] ?? 0);

        $builder = $this->actividadModel->builder();
        $builder->set([
            'validado'             => 1,
            'fecha_validacion'     => $now,
            'updated_at'           => $now,
        ]);
        $builder->where('id', $actividadId)
            ->where('plan_id', $planId)
            ->where('deleted_at', null)
            ->where('estado_id', $estadoCompletadaId)
            ->groupStart()
                ->where('validado', null)
                ->orWhere('validado', 0)
            ->groupEnd();

        if ($builder->update() === false) {
            throw new DatabaseException('No se pudo validar la actividad.');
        }

        $afectadas = $this->actividadModel->db->affectedRows();
        $datos     = $this->obtenerActividadYResumen($planId, $actividadId);
        $actividadActual = $datos['actividad'];

        if ($afectadas === 0) {
            if ($this->esValorVerdadero($actividadActual['validado'] ?? null)) {
                $status  = 'already_validated';
                $message = 'Esta actividad ya fue validada.';
            } elseif (($actividadActual['estado_slug'] ?? '') !== 'completada') {
                $status  = 'estado_invalido';
                $message = 'La actividad ya no está en estado "completada".';
            } else {
                throw new DatabaseException('No se pudo validar la actividad.');
            }

            return [
                'status'    => $status,
                'message'   => $message,
                'actividad' => $this->prepararActividadRespuesta($actividadActual),
                'resumen'   => $datos['resumen'],
            ];
        }

        return [
            'status'    => 'validated',
            'message'   => 'Actividad validada.',
            'actividad' => $this->prepararActividadRespuesta($actividadActual),
            'resumen'   => $datos['resumen'],
        ];
    }

    private function procesarDesvalidacionActividad(int $medicoId, int $actividadId): array
    {
        $contexto = $this->obtenerActividadContexto($medicoId, $actividadId);
        $planId   = (int) ($contexto['plan_id'] ?? 0);

        $estadoPlan = PlanEstadoService::calcular(
            $contexto['plan_estado'] ?? null,
            $contexto['plan_fecha_inicio'] ?? null,
            $contexto['plan_fecha_fin'] ?? null
        );

        if ($estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO) {
            throw new InvalidArgumentException('No puedes modificar actividades de un plan finalizado.');
        }

        if (! $this->esValorVerdadero($contexto['validado'] ?? null)) {
            $datos = $this->obtenerActividadYResumen($planId, $actividadId);

            return [
                'status'    => 'already_unvalidated',
                'message'   => 'La actividad ya no estaba validada.',
                'actividad' => $this->prepararActividadRespuesta($datos['actividad']),
                'resumen'   => $datos['resumen'],
            ];
        }

        $now = date('Y-m-d H:i:s');

        $builder = $this->actividadModel->builder();
        $builder->set([
            'validado'         => null,
            'fecha_validacion' => null,
            'updated_at'       => $now,
        ]);
        $builder->where('id', $actividadId)
            ->where('plan_id', $planId)
            ->where('deleted_at', null)
            ->where('validado', 1);

        if ($builder->update() === false) {
            throw new DatabaseException('No se pudo desvalidar la actividad.');
        }

        $afectadas = $this->actividadModel->db->affectedRows();
        $datos     = $this->obtenerActividadYResumen($planId, $actividadId);
        $actividadActual = $datos['actividad'];
        $sigueValidada   = $this->esValorVerdadero($actividadActual['validado'] ?? null);

        if ($afectadas === 0 && $sigueValidada) {
            throw new DatabaseException('No se pudo desvalidar la actividad.');
        }

        if ($sigueValidada) {
            return [
                'status'    => 'already_unvalidated',
                'message'   => 'La actividad ya no estaba validada.',
                'actividad' => $this->prepararActividadRespuesta($actividadActual),
                'resumen'   => $datos['resumen'],
            ];
        }

        return [
            'status'    => 'unvalidated',
            'message'   => 'Validación revertida.',
            'actividad' => $this->prepararActividadRespuesta($actividadActual),
            'resumen'   => $datos['resumen'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerActividadContexto(int $medicoId, int $actividadId): array
    {
        $builder = $this->actividadModel->builder('actividades AS a');
        $fila = $builder
            ->select([
                'a.id',
                'a.plan_id',
                'a.estado_id',
                'a.categoria_actividad_id',
                'a.validado',
                'a.fecha_validacion',
                'planes_cuidado.creador_user_id',
                'planes_cuidado.estado AS plan_estado',
                'planes_cuidado.fecha_inicio AS plan_fecha_inicio',
                'planes_cuidado.fecha_fin AS plan_fecha_fin',
                'estado_actividad.slug AS estado_slug',
                'estado_actividad.nombre AS estado_nombre',
                'categoria_actividad.nombre AS categoria_nombre',
                'categoria_actividad.color_hex AS categoria_color',
            ])
            ->join('planes_cuidado', 'planes_cuidado.id = a.plan_id', 'inner')
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->join('categoria_actividad', 'categoria_actividad.id = a.categoria_actividad_id', 'left')
            ->where('a.id', $actividadId)
            ->where('a.deleted_at', null)
            ->where('planes_cuidado.deleted_at', null)
            ->get()
            ->getFirstRow('array');

        if ($fila === null) {
            throw new InvalidArgumentException('La actividad indicada no existe.');
        }

        if ((int) ($fila['creador_user_id'] ?? 0) !== $medicoId) {
            throw PageForbiddenException::forPageForbidden('No tienes acceso a esta actividad.');
        }

        return $fila;
    }

    /**
     * @return array{
     *     actividad: array<string, mixed>,
     *     resumen: array<string, mixed>
     * }
     */
    private function obtenerActividadYResumen(int $planId, int $actividadId): array
    {
        $actividades    = $this->actividadModel->findPorPlanConEstado($planId);
        $estadosCatalogo = $this->estadoActividadModel->findActivos();
        $actividad      = $this->buscarActividadPorId($actividades, $actividadId);

        if ($actividad === null) {
            throw new DatabaseException('No se pudo recuperar la actividad actualizada.');
        }

        return [
            'actividad' => $actividad,
            'resumen'   => $this->construirResumenActividades($actividades, $estadosCatalogo),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $actividades
     */
    private function buscarActividadPorId(array $actividades, int $actividadId): ?array
    {
        foreach ($actividades as $actividad) {
            if ((int) ($actividad['id'] ?? 0) === $actividadId) {
                return $actividad;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $actividad
     *
     * @return array<string, mixed>
     */
    private function prepararActividadRespuesta(array $actividad): array
    {
        $validadoOriginal   = $actividad['validado'] ?? null;
        $validadoNormalizado = null;

        if ($validadoOriginal !== null) {
            $validadoNormalizado = $this->esValorVerdadero($validadoOriginal);
        }

        return [
            'id'                   => (int) ($actividad['id'] ?? 0),
            'plan_id'              => (int) ($actividad['plan_id'] ?? 0),
            'nombre'               => $actividad['nombre'] ?? '',
            'descripcion'          => $actividad['descripcion'] ?? '',
            'paciente_comentario'  => $actividad['paciente_comentario'] ?? null,
            'fecha_inicio'         => $actividad['fecha_inicio'] ?? null,
            'fecha_fin'            => $actividad['fecha_fin'] ?? null,
            'estado_id'            => isset($actividad['estado_id']) ? (int) $actividad['estado_id'] : null,
            'estado_slug'          => $actividad['estado_slug'] ?? null,
            'estado_nombre'        => $actividad['estado_nombre'] ?? null,
            'categoria_actividad_id' => isset($actividad['categoria_actividad_id']) ? (int) $actividad['categoria_actividad_id'] : null,
            'categoria_nombre'      => $actividad['categoria_nombre'] ?? null,
            'categoria_color'       => $actividad['categoria_color'] ?? null,
            'validado'             => $validadoNormalizado,
            'fecha_validacion'     => $actividad['fecha_validacion'] ?? null,
        ];
    }

    /**
     * @param mixed $valor
     */
    private function esValorVerdadero($valor): bool
    {
        if ($valor === null) {
            return false;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        $filtrado = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $filtrado === true;
    }

    private function planNoDisponibleRedirect()
    {
        session()->setFlashdata('error', 'El plan solicitado no está disponible.');

        return redirect()->to(route_to('medico_planes_index'));
    }

    /**
     * Devuelve el médico autenticado o un médico por defecto de la seed.
     */
    private function obtenerMedicoActual(): User
    {
        $session = session();
        $userId  = $session->get('user_id');

        if ($userId !== null) {
            $medico = $this->userModel->findActivoPorRol((int) $userId, UserModel::ROLE_MEDICO);
            if ($medico !== null) {
                return $medico;
            }

            throw new PageNotFoundException('Acceso denegado para el usuario actual.');
        }

        $medico = $this->userModel->findPrimeroActivoPorRol(UserModel::ROLE_MEDICO);

        if ($medico === null) {
            throw new PageNotFoundException('No existen médicos activos configurados.');
        }

        $session->set('user_id', $medico->id);

        return $medico;
    }

    /**
     * Incluye en el catálogo las categorías actualmente asignadas (aunque estén inactivas) para evitar perder referencia al editar.
     *
     * @param array<int, array<string, mixed>> $categorias
     * @param array<int, array<string, mixed>> $actividades
     *
     * @return array<int, array<string, mixed>>
     */
    private function asegurarCategoriasAsignadas(array $categorias, array $actividades): array
    {
        $categoriasPorId = [];
        foreach ($categorias as $categoria) {
            $id = (int) ($categoria['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $categoriasPorId[$id] = $categoria;
        }

        $idsEnActividades = array_unique(array_filter(array_map(static function (array $actividad): int {
            return isset($actividad['categoria_actividad_id']) ? (int) $actividad['categoria_actividad_id'] : 0;
        }, $actividades)));

        $faltantes = array_diff($idsEnActividades, array_keys($categoriasPorId));

        if (! empty($faltantes)) {
            $extras = $this->categoriaActividadModel
                ->asArray()
                ->whereIn('id', $faltantes)
                ->findAll();

            foreach ($extras as $extra) {
                $id = (int) ($extra['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $categoriasPorId[$id] = $extra;
            }
        }

        usort($categoriasPorId, static function (array $a, array $b): int {
            return strcmp($a['nombre'] ?? '', $b['nombre'] ?? '');
        });

        return array_values($categoriasPorId);
    }

    private function redirectBackWithErrors(string $mensaje, array $errores)
    {
        return redirect()->back()
            ->withInput()
            ->with('error', $mensaje)
            ->with('errors', $this->normalizarErrores($errores))
            ->with('actividad_errors', session()->getFlashdata('actividad_errors') ?? []);
    }

    private function redirectBackWithActividadErrors(string $mensaje, array $erroresPlan, array $erroresActividades)
    {
        return redirect()->back()
            ->withInput()
            ->with('error', $mensaje)
            ->with('errors', $this->normalizarErrores($erroresPlan))
            ->with('actividad_errors', $erroresActividades);
    }

    /**
     * Garantiza estructura consistente de mensajes de error.
     *
     * @param array<int|string, string> $errores
     *
     * @return array<string, string>
     */
    private function normalizarErrores(array $errores): array
    {
        $normalizados = [];

        foreach ($errores as $clave => $mensaje) {
            if (is_string($clave)) {
                $normalizados[$clave] = $mensaje;

                continue;
            }

            $normalizados['general_' . $clave] = $mensaje;
        }

        return $normalizados;
    }
}
