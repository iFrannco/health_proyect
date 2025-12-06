<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PlanEstandarModel;
use App\Models\PlanEstandarActividadModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PlanesEstandar extends BaseController
{
    protected $planEstandarModel;
    protected $planEstandarActividadModel;

    public function __construct()
    {
        $this->planEstandarModel          = new PlanEstandarModel();
        $this->planEstandarActividadModel = new PlanEstandarActividadModel();
    }

    public function index()
    {
        $search = $this->request->getGet('search');

        // Incluir eliminados (soft delete) para listar todos por defecto
        $query = $this->planEstandarModel->withDeleted()->orderBy('created_at', 'DESC');

        if (! empty($search)) {
            $query->like('nombre', $search);
        }

        $planes = $query->paginate(10);

        return view('admin/planes_estandar/index', $this->layoutData() + [
            'planes' => $planes,
            'pager'  => $this->planEstandarModel->pager,
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin/planes_estandar/form', $this->layoutData() + [
            'plan' => null,
            'actividades' => [],
            'action' => 'create'
        ]);
    }

    public function store()
    {
        if (! $this->validate([
            'nombre'  => 'required|max_length[180]|is_unique[planes_estandar.nombre]',
            'version' => 'required|integer|greater_than[0]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $actividadesData = $this->request->getPost('actividades');
        if (empty($actividadesData) || ! is_array($actividadesData)) {
            return redirect()->back()->withInput()->with('error', 'Debe agregar al menos una actividad.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Insert Plan
            $planData = [
                'nombre'      => $this->request->getPost('nombre'),
                'version'     => $this->request->getPost('version'),
                'descripcion' => $this->request->getPost('descripcion'),
                'vigente'     => 1,
            ];

            $planId = $this->planEstandarModel->insert($planData);
            if (! $planId) {
                throw new \Exception('Error al guardar el plan: ' . implode(', ', $this->planEstandarModel->errors()));
            }

            // 2. Insert Activities
            foreach ($actividadesData as $index => $actividad) {
                if ($actividad['offset_fin_dias'] < $actividad['offset_inicio_dias']) {
                    throw new \Exception("La actividad '{$actividad['nombre']}' tiene un día de fin menor al día de inicio.");
                }

                $actividadToInsert = [
                    'plan_estandar_id'   => $planId,
                    'nombre'             => $actividad['nombre'],
                    'descripcion'        => $actividad['descripcion'] ?? '',
                    'offset_inicio_dias' => $actividad['offset_inicio_dias'],
                    'offset_fin_dias'    => $actividad['offset_fin_dias'],
                    'orden'              => $index + 1,
                ];

                if (! $this->planEstandarActividadModel->insert($actividadToInsert)) {
                    throw new \Exception('Error al guardar actividad: ' . implode(', ', $this->planEstandarActividadModel->errors()));
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error en la transacción de base de datos.');
            }

            return redirect()->route('admin_planes_estandar_index')->with('message', 'Plan estandarizado creado correctamente.');

        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        // Use withDeleted() to allow editing "No vigente" plans if needed, or check business logic. 
        // Story says: "Editar planes existentes". If it's soft deleted (no vigente), maybe we should allow edit?
        // Usually you restore first, but let's allow find.
        $plan = $this->planEstandarModel->withDeleted()->find($id);
        
        if (! $plan) {
            throw PageNotFoundException::forPageNotFound("Plan estándar no encontrado.");
        }

        $actividades = $this->planEstandarActividadModel
                            ->withDeleted() // Just in case
                            ->where('plan_estandar_id', $id)
                            ->orderBy('orden', 'ASC')
                            ->findAll();

        return view('admin/planes_estandar/form', $this->layoutData() + [
            'plan'        => $plan,
            'actividades' => $actividades,
            'action'      => 'edit'
        ]);
    }

    public function update($id)
    {
        $plan = $this->planEstandarModel->withDeleted()->find($id);
        if (! $plan) {
            throw PageNotFoundException::forPageNotFound();
        }

        // Validate unique name ignoring current record
        if (! $this->validate([
            'nombre'  => "required|max_length[180]|is_unique[planes_estandar.nombre,id,{$id}]",
            'version' => 'required|integer|greater_than[0]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $actividadesData = $this->request->getPost('actividades');
        if (empty($actividadesData) || ! is_array($actividadesData)) {
            return redirect()->back()->withInput()->with('error', 'Debe agregar al menos una actividad.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Update Plan
            $planData = [
                'id'          => $id,
                'nombre'      => $this->request->getPost('nombre'),
                'version'     => $this->request->getPost('version'),
                'descripcion' => $this->request->getPost('descripcion'),
            ];

            if (! $this->planEstandarModel->save($planData)) {
                throw new \Exception('Error al actualizar el plan.');
            }

            // 2. Update Activities - Strategy: Delete (Hard or Soft) existing and re-insert
            // Since these are templates and we want to ensure fresh structure matching the form order:
            // We will PURGE (hard delete) the old activities for this plan, because Soft Delete would keep them in DB 
            // and we are inserting new ones. If we use soft delete + insert, we accumulate junk.
            // Since no other table FKs directly to `plan_estandar_actividades` (except maybe logging?), 
            // and `planes_cuidado` points to `planes_estandar`, and copied activities are in `actividades` table...
            // It is safe to PURGE the template activities.
            
            $this->planEstandarActividadModel->where('plan_estandar_id', $id)->purgeDeleted(); // Clean up old mess if any
            $this->planEstandarActividadModel->where('plan_estandar_id', $id)->delete(); // Soft delete current
            $this->planEstandarActividadModel->where('plan_estandar_id', $id)->purgeDeleted(); // Immediately purge them to avoid unique constraint issues if any, or just cleanliness.
            
            // Wait, purgeDeleted() works on rows that are ALREADY soft deleted.
            // delete() marks them as soft deleted.
            // So:
            // 1. $this->planEstandarActividadModel->where('plan_estandar_id', $id)->delete();
            // 2. $this->planEstandarActividadModel->purgeDeleted(); // This purges ALL deleted in table? Be careful.
            // The safer way for "replace" logic on a 1-N relation in CI4 with SoftDeletes is often just to use a permanent delete method if available, or force delete.
            // CI4 Model delete($id, $purge = false). 
            // We can iterate and delete purtging.
            
            // Let's do it via Builder to be efficient and hard delete directly for this replacement logic.
            $db->table('plan_estandar_actividades')->where('plan_estandar_id', $id)->delete(); 

            foreach ($actividadesData as $index => $actividad) {
                 if ($actividad['offset_fin_dias'] < $actividad['offset_inicio_dias']) {
                    throw new \Exception("La actividad '{$actividad['nombre']}' tiene un día de fin menor al día de inicio.");
                }

                $actividadToInsert = [
                    'plan_estandar_id'   => $id,
                    'nombre'             => $actividad['nombre'],
                    'descripcion'        => $actividad['descripcion'] ?? '',
                    'offset_inicio_dias' => $actividad['offset_inicio_dias'],
                    'offset_fin_dias'    => $actividad['offset_fin_dias'],
                    'orden'              => $index + 1,
                ];
                
                $this->planEstandarActividadModel->insert($actividadToInsert);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error en la transacción de base de datos.');
            }

            return redirect()->route('admin_planes_estandar_index')->with('message', 'Plan actualizado correctamente.');

        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function toggleVigencia($id)
    {
        $plan = $this->planEstandarModel->withDeleted()->find($id);
        
        if (! $plan) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($plan->deleted_at !== null) {
            // Restaurar: Usamos builder() para saltar el filtro de soft deletes del modelo al hacer update
            $this->planEstandarModel->builder()->where('id', $id)->update(['deleted_at' => null, 'vigente' => 1]);
            $mensaje = 'Plan reactivado (vigente) correctamente.';
        } else {
            // Eliminar (Soft Delete)
            $this->planEstandarModel->delete($id);
            // Sincronizar flag vigente a 0 (aunque deleted_at ya marca la baja)
            $this->planEstandarModel->builder()->where('id', $id)->update(['vigente' => 0]);
            
            $mensaje = 'Plan inhabilitado (no vigente) correctamente.';
        }

        return redirect()->back()->with('message', $mensaje);
    }
}
