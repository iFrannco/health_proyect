<?= $this->extend('layouts/base') ?>

<?= $this->section('title') ?><?= $action === 'create' ? 'Nuevo Plan' : 'Editar Plan' ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= $action === 'create' ? 'Nuevo Plan Estandarizado' : 'Editar Plan: ' . esc($plan->nombre) ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= site_url('admin/home') ?>">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= route_to('admin_planes_estandar_index') ?>">Planes</a></li>
                    <li class="breadcrumb-item active"><?= $action === 'create' ? 'Nuevo' : 'Editar' ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">

        <?php if (session()->has('errors')) : ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach (session('errors') as $error) : ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (session()->has('error')) : ?>
            <div class="alert alert-danger">
                <?= session('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= $action === 'create' ? route_to('admin_planes_estandar_store') : route_to('admin_planes_estandar_update', $plan->id) ?>" method="post" id="planForm">
            <?= csrf_field() ?>
            
            <div class="row">
                <!-- Cabecera del Plan -->
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Datos Generales</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre">Nombre del Plan <span class="text-danger">*</span></label>
                                        <input type="text" name="nombre" class="form-control" id="nombre" value="<?= old('nombre', $plan->nombre ?? '') ?>" required maxlength="180">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="version">Versión <span class="text-danger">*</span></label>
                                        <input type="number" name="version" class="form-control" id="version" value="<?= old('version', $plan->version ?? '1') ?>" required min="1">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="descripcion">Descripción</label>
                                        <input type="text" name="descripcion" class="form-control" id="descripcion" value="<?= old('descripcion', $plan->descripcion ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalle de Actividades -->
                <div class="col-md-12">
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Actividades del Plan</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" id="btnAddActivity">
                                    <i class="fas fa-plus"></i> Agregar Actividad
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped" id="activitiesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 30%">Nombre <span class="text-danger">*</span></th>
                                        <th style="width: 30%">Descripción</th>
                                        <th style="width: 15%">Día Inicio (Offset)</th>
                                        <th style="width: 15%">Día Fin (Offset)</th>
                                        <th style="width: 10%"></th>
                                    </tr>
                                </thead>
                                <tbody id="activitiesBody">
                                    <!-- Actividades dinámicas aquí -->
                                    <?php 
                                        $oldActividades = old('actividades', $actividades ?? []);
                                        if (!empty($oldActividades)): 
                                            foreach($oldActividades as $index => $act):
                                                // Handle both object (from DB) and array (from old input)
                                                $nombre = is_object($act) ? $act->nombre : $act['nombre'];
                                                $desc = is_object($act) ? $act->descripcion : ($act['descripcion'] ?? '');
                                                $inicio = is_object($act) ? $act->offset_inicio_dias : $act['offset_inicio_dias'];
                                                $fin = is_object($act) ? $act->offset_fin_dias : $act['offset_fin_dias'];
                                    ?>
                                    <tr class="activity-row">
                                        <td>
                                            <input type="text" name="actividades[<?= $index ?>][nombre]" class="form-control form-control-sm" value="<?= esc($nombre) ?>" required>
                                        </td>
                                        <td>
                                            <input type="text" name="actividades[<?= $index ?>][descripcion]" class="form-control form-control-sm" value="<?= esc($desc) ?>">
                                        </td>
                                        <td>
                                            <input type="number" name="actividades[<?= $index ?>][offset_inicio_dias]" class="form-control form-control-sm" value="<?= esc($inicio) ?>" required min="0">
                                        </td>
                                        <td>
                                            <input type="number" name="actividades[<?= $index ?>][offset_fin_dias]" class="form-control form-control-sm" value="<?= esc($fin) ?>" required min="0">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-xs btnRemove"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                            <?php if(empty($oldActividades)): ?>
                                <div id="emptyState" class="text-center p-3 text-muted">
                                    No hay actividades definidas. Agregue al menos una.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="<?= route_to('admin_planes_estandar_index') ?>" class="btn btn-default">Cancelar</a>
                            <button type="submit" class="btn btn-primary float-right">Guardar Plan</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnAdd = document.getElementById('btnAddActivity');
        const tbody = document.getElementById('activitiesBody');
        const emptyState = document.getElementById('emptyState');
        
        let rowCount = <?= !empty($oldActividades) ? count($oldActividades) : 0 ?>;

        btnAdd.addEventListener('click', function() {
            if(emptyState) emptyState.style.display = 'none';
            
            const tr = document.createElement('tr');
            tr.className = 'activity-row';
            tr.innerHTML = `
                <td>
                    <input type="text" name="actividades[${rowCount}][nombre]" class="form-control form-control-sm" required placeholder="Ej. Control Signos Vitales">
                </td>
                <td>
                    <input type="text" name="actividades[${rowCount}][descripcion]" class="form-control form-control-sm" placeholder="Instrucciones...">
                </td>
                <td>
                    <input type="number" name="actividades[${rowCount}][offset_inicio_dias]" class="form-control form-control-sm" required min="0" value="0">
                </td>
                <td>
                    <input type="number" name="actividades[${rowCount}][offset_fin_dias]" class="form-control form-control-sm" required min="0" value="0">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-xs btnRemove"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
            rowCount++;
        });

        tbody.addEventListener('click', function(e) {
            if (e.target.closest('.btnRemove')) {
                e.target.closest('tr').remove();
                if (tbody.children.length === 0 && emptyState) {
                    emptyState.style.display = 'block';
                }
            }
        });
    });
</script>
<?= $this->endSection() ?>
