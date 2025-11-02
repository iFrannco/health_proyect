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
use CodeIgniter\Database\Exceptions\RollbackException;
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
        } catch (RollbackException $exception) {
            return $this->redirectBackWithActividadErrors(
                'No se pudo crear el plan de cuidado.',
                [$exception->getMessage()],
                $actividadesData['erroresPorIndice']
            );
        }

        session()->setFlashdata('success', 'Plan de cuidado creado con éxito.');

        return redirect()->to(site_url('medico/planes'));
    }

    private function extraerActividadesDesdeRequest(): array
    {
        $nombres       = (array) $this->request->getPost('actividad_nombre');
        $descripciones = (array) $this->request->getPost('actividad_descripcion');
        $fechasInicio  = (array) $this->request->getPost('actividad_fecha_inicio');
        $fechasFin     = (array) $this->request->getPost('actividad_fecha_fin');

        $actividades       = [];
        $erroresGenerales  = [];
        $erroresPorIndice  = [];

        $total = max(count($nombres), count($descripciones), count($fechasInicio), count($fechasFin));
        if ($total === 0) {
            $erroresGenerales[] = 'Debes agregar al menos una actividad al plan.';

            return [
                'actividades'       => [],
                'erroresGenerales'  => $erroresGenerales,
                'erroresPorIndice'  => $erroresPorIndice,
            ];
        }

        for ($index = 0; $index < $total; $index++) {
            $nombre       = trim($nombres[$index] ?? '');
            $descripcion  = trim($descripciones[$index] ?? '');
            $fechaInicio  = $fechasInicio[$index] ?? '';
            $fechaFin     = $fechasFin[$index] ?? '';

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
