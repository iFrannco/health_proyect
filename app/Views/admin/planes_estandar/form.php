<?= $this->extend('layouts/base') ?>

<?= $this->section('title') ?><?= isset($plan) ? 'Editar' : 'Nuevo' ?> Plan Estandarizado<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form action="<?= isset($plan) ? base_url('admin/planes-estandar/update/' . $plan->id) : base_url('admin/planes-estandar/create') ?>" method="post" id="formPlan">
    <?= csrf_field() ?>
    
    <!-- Card Datos Generales -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Datos Generales</h3>
        </div>
        <div class="card-body">
            <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger">
                    <ul>
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Nombre del Plan <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" value="<?= isset($plan) ? esc($plan->nombre) : old('nombre') ?>" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Versión <span class="text-danger">*</span></label>
                        <input type="number" name="version" class="form-control" value="<?= isset($plan) ? esc($plan->version) : (old('version') ?? 1) ?>" min="1" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Descripción</label>
                        <input type="text" name="descripcion" class="form-control" value="<?= isset($plan) ? esc($plan->descripcion) : old('descripcion') ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                 <div class="col-md-6">
                     <div class="form-group">
                        <label>Tipo de Diagnóstico <span class="text-danger">*</span></label>
                        <select name="tipo_diagnostico_id" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($tipos_diagnostico as $tipo): ?>
                                <option value="<?= $tipo->id ?>" <?= (isset($plan) && $plan->tipo_diagnostico_id == $tipo->id) ? 'selected' : '' ?>>
                                    <?= esc($tipo->nombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="vigente" class="form-control">
                            <option value="1" <?= (!isset($plan) || $plan->vigente) ? 'selected' : '' ?>>Vigente</option>
                            <option value="0" <?= (isset($plan) && !$plan->vigente) ? 'selected' : '' ?>>No Vigente</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Actividades -->
    <div class="card card-secondary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Actividades del Plan</h3>
            <button type="button" class="btn btn-light btn-sm ml-auto" onclick="agregarFilaActividad()">
                <i class="fas fa-plus"></i> Agregar Actividad
            </button>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 25%">Nombre *</th>
                        <th style="width: 20%">Descripción</th>
                        <th style="width: 20%">Frecuencia</th>
                        <th style="width: 20%">Duración</th>
                        <th style="width: 10%">Día Inicio</th>
                        <th style="width: 5%"></th>
                    </tr>
                </thead>
                <tbody id="contenedorActividades">
                    <?php 
                    // Si hay actividades guardadas (edit) o old input (validacion fallida)
                    $actividadesList = isset($actividades) ? $actividades : [];
                    // TODO: Manejar old('actividades') si falla validación
                    ?>
                    
                    <?php if (!empty($actividadesList)): ?>
                        <?php foreach ($actividadesList as $index => $act): ?>
                            <tr class="fila-actividad">
                                <td>
                                    <input type="hidden" name="actividades[<?= $index ?>][id]" value="<?= $act->id ?>">
                                    <input type="text" name="actividades[<?= $index ?>][nombre]" class="form-control form-control-sm" value="<?= esc($act->nombre) ?>" placeholder="Nombre actividad" required>
                                </td>
                                <td>
                                    <input type="text" name="actividades[<?= $index ?>][descripcion]" class="form-control form-control-sm" value="<?= esc($act->descripcion) ?>" placeholder="Opcional">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="actividades[<?= $index ?>][frecuencia_repeticiones]" class="form-control" value="<?= $act->frecuencia_repeticiones ?>" min="1" required>
                                        <div class="input-group-append"><span class="input-group-text">veces al</span></div>
                                        <select name="actividades[<?= $index ?>][frecuencia_periodo]" class="form-control">
                                            <option value="Día" <?= $act->frecuencia_periodo == 'Día' ? 'selected' : '' ?>>Día</option>
                                            <option value="Semana" <?= $act->frecuencia_periodo == 'Semana' ? 'selected' : '' ?>>Semana</option>
                                            <option value="Mes" <?= $act->frecuencia_periodo == 'Mes' ? 'selected' : '' ?>>Mes</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text">durante</span></div>
                                        <input type="number" name="actividades[<?= $index ?>][duracion_valor]" class="form-control" value="<?= $act->duracion_valor ?>" min="1" required>
                                        <select name="actividades[<?= $index ?>][duracion_unidad]" class="form-control">
                                            <option value="Días" <?= $act->duracion_unidad == 'Días' ? 'selected' : '' ?>>Días</option>
                                            <option value="Semanas" <?= $act->duracion_unidad == 'Semanas' ? 'selected' : '' ?>>Semanas</option>
                                            <option value="Meses" <?= $act->duracion_unidad == 'Meses' ? 'selected' : '' ?>>Meses</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="actividades[<?= $index ?>][offset_inicio_dias]" class="form-control form-control-sm" value="<?= $act->offset_inicio_dias ?? 0 ?>" min="0">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
             <div class="p-3 text-muted font-italic text-center" id="msgSinActividades" style="<?= !empty($actividadesList) ? 'display:none' : '' ?>">
                No hay actividades definidas. Haga clic en "+ Agregar Actividad".
            </div>
        </div>
        <div class="card-footer clearfix">
            <a href="<?= base_url('admin/planes-estandar') ?>" class="btn btn-default float-left">Cancelar</a>
            <button type="submit" class="btn btn-primary float-right">Guardar Plan</button>
        </div>
    </div>
</form>

<script>
    let actividadIndex = <?= isset($actividadesList) ? count($actividadesList) : 0 ?>;

    function agregarFilaActividad() {
        const contenedor = document.getElementById('contenedorActividades');
        const msg = document.getElementById('msgSinActividades');
        msg.style.display = 'none';

        const row = document.createElement('tr');
        row.className = 'fila-actividad';
        row.innerHTML = `
            <td>
                <input type="text" name="actividades[${actividadIndex}][nombre]" class="form-control form-control-sm" placeholder="Nombre actividad" required>
            </td>
            <td>
                <input type="text" name="actividades[${actividadIndex}][descripcion]" class="form-control form-control-sm" placeholder="Opcional">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" name="actividades[${actividadIndex}][frecuencia_repeticiones]" class="form-control" value="1" min="1" required>
                    <div class="input-group-append"><span class="input-group-text">veces al</span></div>
                    <select name="actividades[${actividadIndex}][frecuencia_periodo]" class="form-control">
                        <option value="Día">Día</option>
                        <option value="Semana">Semana</option>
                        <option value="Mes">Mes</option>
                    </select>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text">durante</span></div>
                    <input type="number" name="actividades[${actividadIndex}][duracion_valor]" class="form-control" value="1" min="1" required>
                    <select name="actividades[${actividadIndex}][duracion_unidad]" class="form-control">
                        <option value="Días">Días</option>
                        <option value="Semanas">Semanas</option>
                        <option value="Meses">Meses</option>
                    </select>
                </div>
            </td>
            <td>
                <input type="number" name="actividades[${actividadIndex}][offset_inicio_dias]" class="form-control form-control-sm" value="0" min="0">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        contenedor.appendChild(row);
        actividadIndex++;
    }

    function eliminarFila(btn) {
        const row = btn.closest('tr');
        row.remove();
        
        const contenedor = document.getElementById('contenedorActividades');
        if (contenedor.children.length === 0) {
            document.getElementById('msgSinActividades').style.display = 'block';
        }
    }
    
</script>
<?= $this->endSection() ?>
