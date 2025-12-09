<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoriaActividadModel;
use App\Models\PlanCuidadoModel;
use App\Models\PlanEstandarModel;
use App\Models\PlanEstandarActividadModel;
use App\Models\TipoDiagnosticoModel;
use App\Services\PlanEstadoService;
use CodeIgniter\Exceptions\PageNotFoundException;

class PlanesEstandar extends BaseController
{
    protected $planModel;
    protected $actividadModel;
    protected $tipoDiagnosticoModel;
    protected $categoriaActividadModel;
    protected $planCuidadoModel;

    public function __construct()
    {
        $this->planModel = new PlanEstandarModel();
        $this->actividadModel = new PlanEstandarActividadModel();
        $this->tipoDiagnosticoModel = new TipoDiagnosticoModel();
        $this->categoriaActividadModel = new CategoriaActividadModel();
        $this->planCuidadoModel = new PlanCuidadoModel();
    }

    public function index()
    {
        $planes = $this->planModel
            ->select('plan_estandar.*, tipos_diagnostico.nombre as tipo_diagnostico_nombre')
            ->join('tipos_diagnostico', 'tipos_diagnostico.id = plan_estandar.tipo_diagnostico_id', 'left')
            ->orderBy('plan_estandar.id', 'DESC')
            ->findAll();

        return view('admin/planes_estandar/index', $this->layoutData() + [
            'planes' => $planes,
        ]);
    }

    public function new()
    {
        return view('admin/planes_estandar/form', $this->layoutData() + [
            'plan' => null,
            'tipos_diagnostico' => $this->tipoDiagnosticoModel->findActivos(),
            'actividades' => [],
            'categoriasActividad' => $this->obtenerCategoriasConAsignadas([]),
            'actividadesBloqueadas' => false,
        ]);
    }

    public function create()
    {
        $rules = [
            'nombre'              => 'required|min_length[3]|max_length[180]',
            'version'             => 'required|integer',
            'tipo_diagnostico_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            // Al redireccionar con withInput, los datos viejos se pasan automáticamente.
            // Pero si la vista espera layoutData, debemos asegurarnos de que el redirect 
            // no rompa nada. El redirect carga una nueva petición, que llamará a new() o edit().
            // PERO: Si validación falla, CodeIgniter suele hacer redirect()->back().
            // Si back() lleva a new(), new() carga layoutData. Todo bien.
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $actividades = $this->obtenerActividadesDesdeRequest();
        if (empty($actividades)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Agrega al menos una actividad al plan estándar.')
                ->with('errors', ['actividades' => 'El plan estándar debe incluir al menos una actividad.']);
        }

        $catalogoCategorias = $this->obtenerCatalogoCategorias();
        if (empty($catalogoCategorias)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Configura categorías de actividad antes de crear una plantilla.')
                ->with('errors', ['categorias' => 'No hay categorías de actividad disponibles.']);
        }
        $categoriasLookup = $this->crearLookupCategorias($catalogoCategorias);
        $categoriaDefaultId = $this->elegirCategoriaDefault($catalogoCategorias);

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Guardar Plan
        $dataPlan = [
            'nombre'              => $this->request->getPost('nombre'),
            'descripcion'         => $this->request->getPost('descripcion'),
            'version'             => $this->request->getPost('version'),
            'vigente'             => $this->request->getPost('vigente') ?? 1,
            'tipo_diagnostico_id' => $this->request->getPost('tipo_diagnostico_id'),
            'fecha_creacion'      => date('Y-m-d H:i:s'),
        ];

        $planId = $this->planModel->insert($dataPlan);

        if (!$planId) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('errors', $this->planModel->errors());
        }

        // 2. Guardar Actividades
        if (! empty($actividades)) {
            foreach ($actividades as $act) {
                // Calcular Offset Fin
                $duracionVal = (int) $act['duracion_valor'];
                $duracionUnit = $act['duracion_unidad'];
                $factor = 1;
                if ($duracionUnit === 'Semanas') $factor = 7;
                if ($duracionUnit === 'Meses') $factor = 30;
                
                $offsetInicio = (int) ($act['offset_inicio_dias'] ?? 0);
                $offsetFin = $offsetInicio + ($duracionVal * $factor);
                $categoriaId = isset($act['categoria_actividad_id']) ? (int) $act['categoria_actividad_id'] : 0;
                if ($categoriaId <= 0 && $categoriaDefaultId !== null) {
                    $categoriaId = $categoriaDefaultId;
                }
                if ($categoriaId <= 0 || ! isset($categoriasLookup[$categoriaId])) {
                    $db->transRollback();

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Selecciona una categoría válida para cada actividad.')
                        ->with('errors', ['categorias' => 'Elige una categoría válida para las actividades.']);
                }

                $dataAct = [
                    'plan_estandar_id'        => $planId,
                    'categoria_actividad_id'  => $categoriaId,
                    'nombre'                  => $act['nombre'],
                    'descripcion'             => $act['descripcion'],
                    'frecuencia_repeticiones' => $act['frecuencia_repeticiones'],
                    'frecuencia_periodo'      => $act['frecuencia_periodo'],
                    'duracion_valor'          => $duracionVal,
                    'duracion_unidad'         => $duracionUnit,
                    'offset_inicio_dias'      => $offsetInicio,
                    'offset_fin_dias'         => $offsetFin,
                    'vigente'                 => 1,
                    'orden'                   => 0,
                ];

                if (!$this->actividadModel->insert($dataAct)) {
                    $db->transRollback();
                    $erroresActividad = $this->actividadModel->errors();

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Error al guardar una actividad.')
                        ->with('errors', $erroresActividad);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
             return redirect()->back()->withInput()->with('error', 'Error al guardar el plan y sus actividades.');
        }

        return redirect()->to(base_url("admin/planes-estandar"))
                         ->with('message', 'Plan creado exitosamente.');
    }

    public function edit($id)
    {
        $plan = $this->planModel->find($id);

        if (!$plan) {
            throw PageNotFoundException::forPageNotFound("Plan estándar no encontrado: $id");
        }

        $actividades = $this->actividadModel->where('plan_estandar_id', $id)
                                            ->orderBy('id', 'ASC')
                                            ->findAll();

        $actividadesBloqueadas = $this->existePlanCuidadoActivo($id);

        return view('admin/planes_estandar/form', $this->layoutData() + [
            'plan' => $plan,
            'tipos_diagnostico' => $this->tipoDiagnosticoModel->findActivos(),
            'actividades' => $actividades,
            'categoriasActividad' => $this->obtenerCategoriasConAsignadas($actividades),
            'actividadesBloqueadas' => $actividadesBloqueadas,
        ]);
    }

    public function update($id)
    {
        $plan = $this->planModel->find($id);
        if (!$plan) {
            return redirect()->to(base_url('admin/planes-estandar'))->with('error', 'Plan no encontrado.');
        }

        $rules = [
            'nombre'              => 'required|min_length[3]|max_length[180]',
            'version'             => 'required|integer',
            'tipo_diagnostico_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $actividadesBloqueadas = $this->existePlanCuidadoActivo($id);
        $catalogoCategorias = [];
        $categoriasLookup = [];
        $categoriaDefaultId = null;
        $actividadesEnviadas = [];

        if (!$actividadesBloqueadas) {
            $actividadesEnviadas = $this->obtenerActividadesDesdeRequest();
            if (empty($actividadesEnviadas)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Agrega al menos una actividad al plan estándar.')
                    ->with('errors', ['actividades' => 'El plan estándar debe incluir al menos una actividad.']);
            }

            $catalogoCategorias = $this->obtenerCatalogoCategorias();
            if (empty($catalogoCategorias)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Configura categorías de actividad antes de editar una plantilla.')
                    ->with('errors', ['categorias' => 'No hay categorías de actividad disponibles.']);
            }
            $categoriasLookup = $this->crearLookupCategorias($catalogoCategorias);
            $categoriaDefaultId = $this->elegirCategoriaDefault($catalogoCategorias);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Actualizar Plan
        $dataPlan = [
            'id'                  => $id,
            'nombre'              => $this->request->getPost('nombre'),
            'descripcion'         => $this->request->getPost('descripcion'),
            'version'             => $this->request->getPost('version'),
            'vigente'             => $this->request->getPost('vigente') ?? 1,
            'tipo_diagnostico_id' => $this->request->getPost('tipo_diagnostico_id'),
        ];
        
        if (!$this->planModel->save($dataPlan)) {
             $db->transRollback();
             return redirect()->back()->withInput()->with('errors', $this->planModel->errors());
        }

        // 2. Sincronizar Actividades (solo si no están bloqueadas)
        if (!$actividadesBloqueadas) {
            $actividadesActuales = $this->actividadModel->where('plan_estandar_id', $id)->findColumn('id') ?? [];
            $idsEnviados = [];

            foreach ($actividadesEnviadas as $act) {
                $actId = isset($act['id']) ? (int) $act['id'] : null;
                
                // Cálculos
                $duracionVal = (int) $act['duracion_valor'];
                $duracionUnit = $act['duracion_unidad'];
                $factor = 1;
                if ($duracionUnit === 'Semanas') $factor = 7;
                if ($duracionUnit === 'Meses') $factor = 30;
                
                $offsetInicio = (int) ($act['offset_inicio_dias'] ?? 0);
                $offsetFin = $offsetInicio + ($duracionVal * $factor);
                $categoriaId = isset($act['categoria_actividad_id']) ? (int) $act['categoria_actividad_id'] : 0;
                if ($categoriaId <= 0 && $categoriaDefaultId !== null) {
                    $categoriaId = $categoriaDefaultId;
                }
                if ($categoriaId <= 0 || ! isset($categoriasLookup[$categoriaId])) {
                    $db->transRollback();

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Selecciona una categoría válida para cada actividad.')
                        ->with('errors', ['categorias' => 'Elige una categoría válida para las actividades.']);
                }

                $dataAct = [
                    'plan_estandar_id'        => $id,
                    'categoria_actividad_id'  => $categoriaId,
                    'nombre'                  => $act['nombre'],
                    'descripcion'             => $act['descripcion'],
                    'frecuencia_repeticiones' => $act['frecuencia_repeticiones'],
                    'frecuencia_periodo'      => $act['frecuencia_periodo'],
                    'duracion_valor'          => $duracionVal,
                    'duracion_unidad'         => $duracionUnit,
                    'offset_inicio_dias'      => $offsetInicio,
                    'offset_fin_dias'         => $offsetFin,
                    'vigente'                 => 1, 
                ];

                if ($actId && in_array($actId, $actividadesActuales)) {
                    $dataAct['id'] = $actId;
                    if (!$this->actividadModel->save($dataAct)) {
                        $db->transRollback();
                        $erroresActividad = $this->actividadModel->errors();

                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error al actualizar actividad.')
                            ->with('errors', $erroresActividad);
                    }
                    $idsEnviados[] = $actId;
                } else {
                    if (!$this->actividadModel->insert($dataAct)) {
                        $db->transRollback();
                        $erroresActividad = $this->actividadModel->errors();

                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error al insertar nueva actividad.')
                            ->with('errors', $erroresActividad);
                    }
                }
            }

            $idsBorrar = array_diff($actividadesActuales, $idsEnviados);
            if (!empty($idsBorrar)) {
                $this->actividadModel->delete($idsBorrar);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
             return redirect()->back()->withInput()->with('error', 'Error en la transacción al actualizar el plan.');
        }

        $mensaje = 'Plan actualizado exitosamente.';
        if ($actividadesBloqueadas) {
            $mensaje .= ' Las actividades no se modificaron porque la plantilla está en uso por planes de cuidado en curso o sin iniciar.';
        }

        return redirect()->to(base_url("admin/planes-estandar"))
                         ->with('message', $mensaje);
    }

    public function toggle($id)
    {
        $plan = $this->planModel->find($id);
        if (!$plan) {
            return redirect()->to(base_url('admin/planes-estandar'))->with('error', 'Plan no encontrado.');
        }

        $nuevoEstado = !$plan->vigente;
        
        if ($this->planModel->save(['id' => $id, 'vigente' => $nuevoEstado])) {
            $this->actividadModel->where('plan_estandar_id', $id)->set(['vigente' => $nuevoEstado])->update();
            
            $mensaje = $nuevoEstado ? 'Plan reactivado.' : 'Plan deshabilitado.';
            return redirect()->to(base_url('admin/planes-estandar'))->with('message', $mensaje);
        }
        
        return redirect()->back()->with('error', 'Error al cambiar estado.');
    }
    
    public function delete($id)
    {
        return $this->toggle($id);
    }

    /**
     * @param array<int, \App\Entities\PlanEstandarActividad> $actividades
     * @return array<int, array<string, mixed>>
     */
    private function obtenerCategoriasConAsignadas(array $actividades): array
    {
        $categorias = $this->categoriaActividadModel->findActivas();
        $categoriasPorId = [];

        foreach ($categorias as $categoria) {
            $id = (int) ($categoria['id'] ?? 0);
            if ($id > 0) {
                $categoriasPorId[$id] = $categoria;
            }
        }

        foreach ($actividades as $actividad) {
            $categoriaId = (int) ($actividad->categoria_actividad_id ?? 0);
            if ($categoriaId > 0 && ! isset($categoriasPorId[$categoriaId])) {
                $extra = $this->categoriaActividadModel->find($categoriaId);
                if (is_array($extra) && ! empty($extra)) {
                    $categoriasPorId[$categoriaId] = $extra;
                }
            }
        }

        usort($categoriasPorId, static function (array $a, array $b): int {
            return strcmp($a['nombre'] ?? '', $b['nombre'] ?? '');
        });

        return array_values($categoriasPorId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerCatalogoCategorias(): array
    {
        return $this->categoriaActividadModel
            ->asArray()
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * @param array<int, array<string, mixed>> $categorias
     */
    private function crearLookupCategorias(array $categorias): array
    {
        $ids = array_filter(
            array_map(static fn ($categoria) => (int) ($categoria['id'] ?? 0), $categorias),
            static fn (int $id): bool => $id > 0
        );
        return array_fill_keys($ids, true);
    }

    /**
     * @param array<int, array<string, mixed>> $categorias
     */
    private function elegirCategoriaDefault(array $categorias): ?int
    {
        foreach ($categorias as $categoria) {
            if ((int) ($categoria['id'] ?? 0) === 1 && (int) ($categoria['activo'] ?? 0) === 1) {
                return 1;
            }
        }

        foreach ($categorias as $categoria) {
            if ((int) ($categoria['activo'] ?? 0) === 1) {
                return (int) $categoria['id'];
            }
        }

        return isset($categorias[0]['id']) ? (int) $categorias[0]['id'] : null;
    }

    private function existePlanCuidadoActivo(int $planEstandarId): bool
    {
        return $this->planCuidadoModel
            ->where('plan_estandar_id', $planEstandarId)
            ->whereIn('estado', [
                PlanEstadoService::ESTADO_EN_CURSO,
                PlanEstadoService::ESTADO_SIN_INICIAR,
            ])
            ->countAllResults() > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerActividadesDesdeRequest(): array
    {
        $actividades = $this->request->getPost('actividades');

        if (!is_array($actividades)) {
            return [];
        }

        $limpias = [];

        foreach ($actividades as $act) {
            $nombre = trim((string) ($act['nombre'] ?? ''));
            $categoriaId = isset($act['categoria_actividad_id']) ? (int) $act['categoria_actividad_id'] : 0;
            $descripcion = $act['descripcion'] ?? '';
            $frecuenciaRepeticiones = (int) ($act['frecuencia_repeticiones'] ?? 0);
            $frecuenciaPeriodo = $act['frecuencia_periodo'] ?? 'Día';
            $duracionValor = (int) ($act['duracion_valor'] ?? 0);
            $duracionUnidad = $act['duracion_unidad'] ?? 'Días';
            $offsetInicio = isset($act['offset_inicio_dias']) ? (int) $act['offset_inicio_dias'] : 0;
            $actividadId = isset($act['id']) ? (int) $act['id'] : null;

            // Ignorar filas completamente vacías
            if ($nombre === '' && $categoriaId === 0 && $frecuenciaRepeticiones === 0 && $duracionValor === 0) {
                continue;
            }

            $limpias[] = [
                'id'                       => $actividadId,
                'nombre'                   => $nombre,
                'categoria_actividad_id'   => $categoriaId,
                'descripcion'              => $descripcion,
                'frecuencia_repeticiones'  => $frecuenciaRepeticiones,
                'frecuencia_periodo'       => $frecuenciaPeriodo,
                'duracion_valor'           => $duracionValor,
                'duracion_unidad'          => $duracionUnidad,
                'offset_inicio_dias'       => $offsetInicio,
            ];
        }

        return $limpias;
    }
}
