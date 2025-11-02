<?php

namespace App\Controllers\Medico;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\ActividadModel;
use App\Models\DiagnosticoModel;
use App\Models\EstadoActividadModel;
use App\Models\PlanCuidadoModel;
use App\Models\UserModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Exceptions\PageForbiddenException;
use CodeIgniter\Exceptions\PageNotFoundException;

class Planes extends BaseController
{
    private DiagnosticoModel $diagnosticoModel;
    private PlanCuidadoModel $planCuidadoModel;
    private ActividadModel $actividadModel;
    private EstadoActividadModel $estadoActividadModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->diagnosticoModel     = new DiagnosticoModel();
        $this->planCuidadoModel     = new PlanCuidadoModel();
        $this->actividadModel       = new ActividadModel();
        $this->estadoActividadModel = new EstadoActividadModel();
        $this->userModel            = new UserModel();
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

    public function create()
    {
        $medico    = $this->obtenerMedicoActual();
        $pacientes = $this->userModel->findActivosPorRol(UserModel::ROLE_PACIENTE);
        $diagnosticos = $this->diagnosticoModel->asArray()
            ->select([
                'diagnosticos.id',
                'diagnosticos.descripcion',
                'diagnosticos.destinatario_user_id',
            ])
            ->orderBy('diagnosticos.fecha_creacion', 'DESC')
            ->findAll();

        $data = [
            'title'                   => 'Nuevo plan personalizado',
            'medico'                  => $medico,
            'pacientes'               => $pacientes,
            'diagnosticos'            => $diagnosticos,
            'errors'                  => session()->getFlashdata('errors') ?? [],
            'actividadErrors'         => session()->getFlashdata('actividad_errors') ?? [],
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
                'rules' => 'required|valid_date[Y-m-d]',
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

        $pacienteId    = (int) $this->request->getPost('paciente_id');
        $diagnosticoId = (int) $this->request->getPost('diagnostico_id');
        $fechaInicio   = $this->request->getPost('fecha_inicio');
        $fechaFin      = $this->request->getPost('fecha_fin');

        if ($fechaInicio > $fechaFin) {
            return $this->redirectBackWithErrors('La fecha de inicio no puede ser posterior a la fecha de fin.', [
                'fecha_inicio' => 'Debe ser anterior o igual a la fecha de fin.',
                'fecha_fin'    => 'Debe ser posterior o igual a la fecha de inicio.',
            ]);
        }

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

        $actividadesData = $this->extraerActividadesDesdeRequest();
        if (! empty($actividadesData['erroresGenerales'])) {
            return $this->redirectBackWithActividadErrors(
                'Revisa las actividades ingresadas.',
                $actividadesData['erroresGenerales'],
                $actividadesData['erroresPorIndice']
            );
        }

        $estadoSinIniciar = $this->estadoActividadModel->findBySlug('sin_iniciar');
        if ($estadoSinIniciar === null) {
            return $this->redirectBackWithErrors('No se encontró el estado inicial de actividades. Contacta al administrador.', []);
        }

        $planData = [
            'diagnostico_id' => $diagnosticoId,
            'creador_user_id' => $medico->id,
            'plan_estandar_id' => null,
            'nombre'         => $this->request->getPost('nombre') ?: null,
            'descripcion'    => $this->request->getPost('descripcion') ?: null,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'fecha_inicio'   => $fechaInicio,
            'fecha_fin'      => $fechaFin,
        ];

        $db = $this->planCuidadoModel->db;
        $db->transBegin();

        try {
            $planId = $this->planCuidadoModel->insert($planData, true);
            if ($planId === false) {
                throw new DataException('No se pudo crear el plan de cuidado.');
            }

            foreach ($actividadesData['actividades'] as $actividad) {
                $actividadPayload = [
                    'plan_id'        => $planId,
                    'nombre'         => $actividad['nombre'],
                    'descripcion'    => $actividad['descripcion'],
                    'fecha_creacion' => date('Y-m-d H:i:s'),
                    'fecha_inicio'   => $actividad['fecha_inicio'],
                    'fecha_fin'      => $actividad['fecha_fin'],
                    'estado_id'      => $estadoSinIniciar['id'],
                    'validado'       => null,
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
        ];

        return view('medico/planes/show', $this->layoutData() + $data);
    }

    public function edit(int $planId)
    {
        $medico = $this->obtenerMedicoActual();
        $plan   = $this->findPlanDetalleParaMedico($planId, $medico->id);

        if ($plan === null) {
            return $this->planNoDisponibleRedirect();
        }

        $actividades = $this->actividadModel->findPorPlanConEstado($plan['id']);

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

        $rules = [
            'fecha_inicio' => [
                'label' => 'Fecha de inicio',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'fecha_fin' => [
                'label' => 'Fecha de fin',
                'rules' => 'required|valid_date[Y-m-d]',
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

        if ($fechaInicio > $fechaFin) {
            return $this->redirectBackWithErrors('La fecha de inicio no puede ser posterior a la fecha de fin.', [
                'fecha_inicio' => 'Debe ser anterior o igual a la fecha de fin.',
                'fecha_fin'    => 'Debe ser posterior o igual a la fecha de inicio.',
            ]);
        }

        $actividadesData = $this->extraerActividadesDesdeRequest();
        if (! empty($actividadesData['erroresGenerales'])) {
            return $this->redirectBackWithActividadErrors(
                'Revisa las actividades ingresadas.',
                $actividadesData['erroresGenerales'],
                $actividadesData['erroresPorIndice']
            );
        }

        $actividadesExistentes = $this->actividadModel
            ->where('plan_id', $plan['id'])
            ->findAll();

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

        $estadoSinIniciar = $this->estadoActividadModel->findBySlug('sin_iniciar');
        if ($estadoSinIniciar === null) {
            return $this->redirectBackWithErrors('No se encontró el estado inicial de actividades. Contacta al administrador.', []);
        }

        $db = $this->planCuidadoModel->db;
        $db->transBegin();

        try {
            $planPayload = [
                'nombre'      => $this->request->getPost('nombre') ?: null,
                'descripcion' => $this->request->getPost('descripcion') ?: null,
                'fecha_inicio'=> $fechaInicio,
                'fecha_fin'   => $fechaFin,
            ];

            if ($this->planCuidadoModel->update($plan['id'], $planPayload) === false) {
                throw new DataException('No se pudo actualizar el plan de cuidado.');
            }

            $idsPersistidos = [];

            foreach ($actividadesData['actividades'] as $actividadInput) {
                $actividadId = $actividadInput['id'] ?? null;

                if ($actividadId !== null && $actividadId > 0) {
                    $idsPersistidos[] = $actividadId;
                    $actividadEntity = $actividadesPorId[$actividadId] ?? null;
                    $payload = [
                        'nombre'       => $actividadInput['nombre'],
                        'descripcion'  => $actividadInput['descripcion'],
                        'fecha_inicio' => $actividadInput['fecha_inicio'],
                        'fecha_fin'    => $actividadInput['fecha_fin'],
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
                        );

                        if ($haCambiado && $actividadEntity->validado === true) {
                            $payload['validado'] = null;
                            $payload['estado_id'] = $estadoSinIniciar['id'];
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
                    'estado_id'      => $estadoSinIniciar['id'],
                    'validado'       => null,
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

    public function delete(int $planId)
    {
        $medico = $this->obtenerMedicoActual();
        $plan   = $this->findPlanDetalleParaMedico($planId, $medico->id);

        if ($plan === null) {
            return $this->planNoDisponibleRedirect();
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

    private function extraerActividadesDesdeRequest(): array
    {
        $nombres       = (array) $this->request->getPost('actividad_nombre');
        $descripciones = (array) $this->request->getPost('actividad_descripcion');
        $fechasInicio  = (array) $this->request->getPost('actividad_fecha_inicio');
        $fechasFin     = (array) $this->request->getPost('actividad_fecha_fin');
        $ids           = (array) $this->request->getPost('actividad_id');

        $actividades       = [];
        $erroresGenerales  = [];
        $erroresPorIndice  = [];

        $total = max(count($nombres), count($descripciones), count($fechasInicio), count($fechasFin));
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
            $actividadId = isset($ids[$index]) && $ids[$index] !== ''
                ? (int) $ids[$index]
                : null;

            $erroresFila = [];

            if ($nombre === '') {
                $erroresFila['nombre'] = 'El nombre es obligatorio.';
            } elseif (strlen($nombre) > 120) {
                $erroresFila['nombre'] = 'El nombre no debe superar los 120 caracteres.';
            }

            if ($descripcion === '') {
                $erroresFila['descripcion'] = 'La descripción es obligatoria.';
            } elseif (strlen($descripcion) > 2000) {
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

            if (! empty($erroresFila)) {
                $erroresPorIndice[$index] = $erroresFila;

                continue;
            }

            $actividades[] = [
                'id'           => $actividadId,
                'nombre'       => $nombre,
                'descripcion'  => $descripcion,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
            ];
        }

        if (empty($actividades)) {
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
