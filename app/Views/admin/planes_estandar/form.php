<?= $this->extend('layouts/base') ?>

<?= $this->section('title') ?><?= isset($plan) ? 'Editar' : 'Nuevo' ?> Plan Estandarizado<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $categoriasActividad = $categoriasActividad ?? [];
    $bloqueaActividades = (bool) ($actividadesBloqueadas ?? false);
    $categoriaDefaultId = '';

    foreach ($categoriasActividad as $categoria) {
        if ((int) ($categoria['id'] ?? 0) === 1 && (int) ($categoria['activo'] ?? 0) === 1) {
            $categoriaDefaultId = 1;
            break;
        }
    }

    if ($categoriaDefaultId === '' && ! empty($categoriasActividad)) {
        foreach ($categoriasActividad as $categoria) {
            if ((int) ($categoria['activo'] ?? 0) === 1) {
                $categoriaDefaultId = (int) ($categoria['id'] ?? 0);
                break;
            }
        }
    }

    if ($categoriaDefaultId === '' && ! empty($categoriasActividad)) {
        $categoriaDefaultId = (int) ($categoriasActividad[0]['id'] ?? 0);
    }

    $opcionesCategoria = '<option value="">Selecciona una categoría</option>';
    foreach ($categoriasActividad as $categoria) {
        $selected = ($categoriaDefaultId !== '' && (int) $categoriaDefaultId === (int) ($categoria['id'] ?? 0)) ? ' selected' : '';
        $opcionesCategoria .= '<option value="' . esc($categoria['id'], 'attr') . '"' . $selected . '>' . esc($categoria['nombre'] ?? '', 'attr') . '</option>';
    }
?>
<form action="<?= isset($plan) ? base_url('admin/planes-estandar/update/' . $plan->id) : base_url('admin/planes-estandar/create') ?>" method="post" id="formPlan">
    <?= csrf_field() ?>
    
    <!-- Card Datos Generales -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Datos Generales</h3>
        </div>
        <div class="card-body">
            <?php
                $erroresFlash = session()->getFlashdata('errors');
                $mensajeError = session()->getFlashdata('error');
            ?>
            <?php if ($mensajeError): ?>
                <div class="alert alert-danger mb-2">
                    <?= esc($mensajeError) ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($erroresFlash) && is_array($erroresFlash)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                    <?php foreach ($erroresFlash as $error): ?>
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
            <button type="button" class="btn btn-light btn-sm ml-auto" onclick="agregarFilaActividad()" <?= $bloqueaActividades ? 'disabled title="No se pueden modificar actividades mientras existan planes de cuidado sin iniciar o en curso que usan esta plantilla."' : '' ?>>
                <i class="fas fa-plus"></i> Agregar Actividad
            </button>
        </div>
        <div class="card-body p-0">
            <?php if ($bloqueaActividades): ?>
                <div class="alert alert-warning m-3">
                    Las actividades están bloqueadas porque hay planes de cuidado sin iniciar o en curso que usan esta plantilla. Solo puedes editar los datos generales.
                </div>
            <?php endif; ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 20%">Nombre *</th>
                        <th style="width: 18%">Categoría *</th>
                        <th style="width: 18%">Descripción</th>
                        <th style="width: 16%">Frecuencia</th>
                        <th style="width: 16%">Duración</th>
                        <th style="width: 8%">Día Inicio</th>
                        <th style="width: 4%"></th>
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
                                    <input type="text" name="actividades[<?= $index ?>][nombre]" class="form-control form-control-sm" value="<?= esc($act->nombre) ?>" placeholder="Nombre actividad" <?= $bloqueaActividades ? 'readonly' : 'required' ?>>
                                </td>
                                <td>
                                    <select name="actividades[<?= $index ?>][categoria_actividad_id]" class="form-control form-control-sm" <?= $bloqueaActividades ? 'disabled' : 'required' ?>>
                                        <option value="">Selecciona una categoría</option>
                                        <?php foreach ($categoriasActividad as $categoria): ?>
                                            <?php $selectedCategoria = (int) ($act->categoria_actividad_id ?? $categoriaDefaultId) === (int) ($categoria['id'] ?? 0); ?>
                                            <option value="<?= esc($categoria['id']) ?>" <?= $selectedCategoria ? 'selected' : '' ?>>
                                                <?= esc($categoria['nombre'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="actividades[<?= $index ?>][descripcion]" class="form-control form-control-sm" value="<?= esc($act->descripcion) ?>" placeholder="Opcional" <?= $bloqueaActividades ? 'readonly' : '' ?>>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="actividades[<?= $index ?>][frecuencia_repeticiones]" class="form-control" value="<?= $act->frecuencia_repeticiones ?>" min="1" <?= $bloqueaActividades ? 'readonly' : 'required' ?>>
                                        <div class="input-group-append"><span class="input-group-text">veces al</span></div>
                                        <select name="actividades[<?= $index ?>][frecuencia_periodo]" class="form-control" <?= $bloqueaActividades ? 'disabled' : '' ?>>
                                            <option value="Día" <?= $act->frecuencia_periodo == 'Día' ? 'selected' : '' ?>>Día</option>
                                            <option value="Semana" <?= $act->frecuencia_periodo == 'Semana' ? 'selected' : '' ?>>Semana</option>
                                            <option value="Mes" <?= $act->frecuencia_periodo == 'Mes' ? 'selected' : '' ?>>Mes</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text">durante</span></div>
                                        <input type="number" name="actividades[<?= $index ?>][duracion_valor]" class="form-control" value="<?= $act->duracion_valor ?>" min="1" <?= $bloqueaActividades ? 'readonly' : 'required' ?>>
                                        <select name="actividades[<?= $index ?>][duracion_unidad]" class="form-control" <?= $bloqueaActividades ? 'disabled' : '' ?>>
                                            <option value="Días" <?= $act->duracion_unidad == 'Días' ? 'selected' : '' ?>>Días</option>
                                            <option value="Semanas" <?= $act->duracion_unidad == 'Semanas' ? 'selected' : '' ?>>Semanas</option>
                                            <option value="Meses" <?= $act->duracion_unidad == 'Meses' ? 'selected' : '' ?>>Meses</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="actividades[<?= $index ?>][offset_inicio_dias]" class="form-control form-control-sm" value="<?= $act->offset_inicio_dias ?? 0 ?>" min="0" <?= $bloqueaActividades ? 'readonly' : '' ?>>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)" <?= $bloqueaActividades ? 'disabled' : '' ?>>
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
    const opcionesCategoria = <?= json_encode($opcionesCategoria, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const actividadesBloqueadas = <?= $bloqueaActividades ? 'true' : 'false' ?>;
    const contenedorActividades = document.getElementById('contenedorActividades');
    const msgSinActividades = document.getElementById('msgSinActividades');
    const formPlan = document.getElementById('formPlan');

    function ocultarMensajeActividades() {
        if (msgSinActividades) {
            msgSinActividades.style.display = 'none';
        }
    }

    function mostrarMensajeActividades(error = false) {
        if (!msgSinActividades) {
            return;
        }

        if (error) {
            msgSinActividades.textContent = 'Agrega al menos una actividad antes de guardar el plan.';
            msgSinActividades.classList.remove('text-muted', 'font-italic');
            msgSinActividades.classList.add('text-danger', 'font-weight-bold');
        } else {
            msgSinActividades.textContent = 'No hay actividades definidas. Haga clic en "+ Agregar Actividad".';
            msgSinActividades.classList.remove('text-danger', 'font-weight-bold');
            msgSinActividades.classList.add('text-muted', 'font-italic');
        }

        msgSinActividades.style.display = 'block';
    }

    function agregarFilaActividad() {
        if (actividadesBloqueadas) {
            mostrarMensajeActividades(true);
            return;
        }
        if (!contenedorActividades) {
            return;
        }
        ocultarMensajeActividades();

        const row = document.createElement('tr');
        row.className = 'fila-actividad';
        row.innerHTML = `
            <td>
                <input type="text" name="actividades[${actividadIndex}][nombre]" class="form-control form-control-sm" placeholder="Nombre actividad" required>
            </td>
            <td>
                <select name="actividades[${actividadIndex}][categoria_actividad_id]" class="form-control form-control-sm" required>
                    ${opcionesCategoria}
                </select>
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
        contenedorActividades.appendChild(row);
        actividadIndex++;
    }

    function eliminarFila(btn) {
        const row = btn.closest('tr');
        row.remove();
        
        if (contenedorActividades && contenedorActividades.children.length === 0) {
            mostrarMensajeActividades();
        }
    }

    if (formPlan) {
        formPlan.addEventListener('submit', function (event) {
            if (contenedorActividades && contenedorActividades.children.length === 0) {
                event.preventDefault();
                mostrarMensajeActividades(true);
            }
        });
    }
</script>
<?= $this->endSection() ?>
