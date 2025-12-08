<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PlanEstandarModel;
use App\Models\PlanEstandarActividadModel;
use App\Models\TipoDiagnosticoModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PlanesEstandar extends BaseController
{
    protected $planModel;
    protected $actividadModel;
    protected $tipoDiagnosticoModel;

    public function __construct()
    {
        $this->planModel = new PlanEstandarModel();
        $this->actividadModel = new PlanEstandarActividadModel();
        $this->tipoDiagnosticoModel = new TipoDiagnosticoModel();
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
            'actividades' => []
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
        $actividades = $this->request->getPost('actividades');
        if ($actividades && is_array($actividades)) {
            foreach ($actividades as $act) {
                // Calcular Offset Fin
                $duracionVal = (int)$act['duracion_valor'];
                $duracionUnit = $act['duracion_unidad'];
                $factor = 1;
                if ($duracionUnit === 'Semanas') $factor = 7;
                if ($duracionUnit === 'Meses') $factor = 30;
                
                $offsetInicio = (int)($act['offset_inicio_dias'] ?? 0);
                $offsetFin = $offsetInicio + ($duracionVal * $factor);

                $dataAct = [
                    'plan_estandar_id'        => $planId,
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
                    return redirect()->back()->withInput()->with('error', 'Error al guardar una actividad.');
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

        return view('admin/planes_estandar/form', $this->layoutData() + [
            'plan' => $plan,
            'tipos_diagnostico' => $this->tipoDiagnosticoModel->findActivos(),
            'actividades' => $actividades
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

        // 2. Sincronizar Actividades
        $actividadesEnviadas = $this->request->getPost('actividades');
        if (!is_array($actividadesEnviadas)) $actividadesEnviadas = [];

        $actividadesActuales = $this->actividadModel->where('plan_estandar_id', $id)->findColumn('id') ?? [];
        $idsEnviados = [];

        foreach ($actividadesEnviadas as $act) {
            $actId = isset($act['id']) ? (int)$act['id'] : null;
            
            // Cálculos
            $duracionVal = (int)$act['duracion_valor'];
            $duracionUnit = $act['duracion_unidad'];
            $factor = 1;
            if ($duracionUnit === 'Semanas') $factor = 7;
            if ($duracionUnit === 'Meses') $factor = 30;
            
            $offsetInicio = (int)($act['offset_inicio_dias'] ?? 0);
            $offsetFin = $offsetInicio + ($duracionVal * $factor);

            $dataAct = [
                'plan_estandar_id'        => $id,
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
                    return redirect()->back()->withInput()->with('error', 'Error al actualizar actividad.');
                }
                $idsEnviados[] = $actId;
            } else {
                if (!$this->actividadModel->insert($dataAct)) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', 'Error al insertar nueva actividad.');
                }
            }
        }

        $idsBorrar = array_diff($actividadesActuales, $idsEnviados);
        if (!empty($idsBorrar)) {
            $this->actividadModel->delete($idsBorrar);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
             return redirect()->back()->withInput()->with('error', 'Error en la transacción al actualizar el plan.');
        }

        return redirect()->to(base_url("admin/planes-estandar"))
                         ->with('message', 'Plan actualizado exitosamente.');
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
}
