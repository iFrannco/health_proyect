<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$selectedPacienteId   = (int) old('paciente_id');
$selectedDiagnosticoId = (int) old('diagnostico_id');
$errores               = $errors ?? [];
$actividadErrors       = $actividadErrors ?? [];

$oldNombres       = (array) old('actividad_nombre');
$oldDescripciones = (array) old('actividad_descripcion');
$oldFechasInicio  = (array) old('actividad_fecha_inicio');
$oldFechasFin     = (array) old('actividad_fecha_fin');

$cantidadActividades = max(
    count($oldNombres),
    count($oldDescripciones),
    count($oldFechasInicio),
    count($oldFechasFin),
    1
);
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Nuevo plan personalizado</h1>
                <p class="text-muted mb-0">
                    Define manualmente un plan de cuidado asociado a un diagnóstico existente.
                </p>
            </div>
            <a href="<?= route_to('medico_planes_index') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver al listado
            </a>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <?php if (! empty($errores)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle mr-2"></i>
                Revisa los datos del formulario. Algunos campos requieren tu atención.
            </div>
        <?php endif; ?>

        <form action="<?= route_to('medico_planes_store') ?>" method="post" id="form-plan-personalizado">
            <?= csrf_field() ?>
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Información del plan</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="paciente_id">Paciente <span class="text-danger">*</span></label>
                            <select name="paciente_id" id="paciente_id" class="form-control <?= isset($errores['paciente_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Selecciona un paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <?php
                                    $nombreCompleto = trim(($paciente['apellido'] ?? '') . ', ' . ($paciente['nombre'] ?? ''));
                                    $esSeleccionado = $selectedPacienteId === (int) $paciente['id'];
                                    ?>
                                    <option value="<?= esc($paciente['id']) ?>" <?= $esSeleccionado ? 'selected' : '' ?>>
                                        <?= esc($nombreCompleto) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errores['paciente_id'])): ?>
                                <div class="invalid-feedback"><?= esc($errores['paciente_id']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="diagnostico_id">Diagnóstico <span class="text-danger">*</span></label>
                            <select name="diagnostico_id" id="diagnostico_id" class="form-control <?= isset($errores['diagnostico_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Selecciona un diagnóstico</option>
                                <?php foreach ($diagnosticos as $diagnostico): ?>
                                    <?php
                                    $pertenecePaciente = (int) $diagnostico['destinatario_user_id'];
                                    $descripcionDiag    = trim($diagnostico['descripcion'] ?? '');
                                    if (mb_strlen($descripcionDiag) > 120) {
                                        $descripcionDiag = mb_substr($descripcionDiag, 0, 117) . '...';
                                    }
                                    $esSeleccionado = $selectedDiagnosticoId === (int) $diagnostico['id'];
                                    ?>
                                    <option value="<?= esc($diagnostico['id']) ?>"
                                            data-paciente-id="<?= esc($pertenecePaciente) ?>"
                                            <?= $esSeleccionado ? 'selected' : '' ?>>
                                        <?= esc('Diag #' . $diagnostico['id'] . ' — ' . $descripcionDiag) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errores['diagnostico_id'])): ?>
                                <div class="invalid-feedback"><?= esc($errores['diagnostico_id']) ?></div>
                            <?php endif; ?>
                            <small class="form-text text-muted">
                                Solo se listan diagnósticos creados por ti para el paciente seleccionado.
                            </small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nombre">Nombre del plan</label>
                            <input type="text" name="nombre" id="nombre" maxlength="180"
                                   value="<?= esc(old('nombre')) ?>"
                                   class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                                   placeholder="Ej. Plan post operatorio abril">
                            <?php if (isset($errores['nombre'])): ?>
                                <div class="invalid-feedback"><?= esc($errores['nombre']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="fecha_inicio">Fecha de inicio <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio"
                                   value="<?= esc(old('fecha_inicio')) ?>"
                                   class="form-control <?= isset($errores['fecha_inicio']) ? 'is-invalid' : '' ?>" required>
                            <?php if (isset($errores['fecha_inicio'])): ?>
                                <div class="invalid-feedback"><?= esc($errores['fecha_inicio']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="fecha_fin">Fecha de fin <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_fin" id="fecha_fin"
                                   value="<?= esc(old('fecha_fin')) ?>"
                                   class="form-control <?= isset($errores['fecha_fin']) ? 'is-invalid' : '' ?>" required>
                            <?php if (isset($errores['fecha_fin'])): ?>
                                <div class="invalid-feedback"><?= esc($errores['fecha_fin']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="4" maxlength="2000"
                                  class="form-control <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>"
                                  placeholder="Detalle clínico del plan (opcional)"><?= esc(old('descripcion')) ?></textarea>
                        <?php if (isset($errores['descripcion'])): ?>
                            <div class="invalid-feedback"><?= esc($errores['descripcion']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Actividades del plan</h3>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-agregar-actividad">
                        <i class="fas fa-plus mr-1"></i> Agregar actividad
                    </button>
                </div>
                <div class="card-body">
                    <?php if (! empty($actividadErrors)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Se encontraron errores en las actividades. Revísalos antes de guardar.
                        </div>
                    <?php endif; ?>
                    <div id="contenedor-actividades">
                        <?php for ($index = 0; $index < $cantidadActividades; $index++): ?>
                            <?php
                            $nombreActividad       = $oldNombres[$index] ?? '';
                            $descripcionActividad  = $oldDescripciones[$index] ?? '';
                            $fechaInicioActividad  = $oldFechasInicio[$index] ?? '';
                            $fechaFinActividad     = $oldFechasFin[$index] ?? '';
                            $erroresFila           = $actividadErrors[$index] ?? [];
                            ?>
                            <div class="card mb-3 actividad-item" data-index="<?= $index ?>">
                                <div class="card-body pb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0">Actividad <?= $index + 1 ?></h5>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remover-actividad">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Nombre <span class="text-danger">*</span></label>
                                            <input type="text" name="actividad_nombre[]" maxlength="120"
                                                   value="<?= esc($nombreActividad) ?>"
                                                   class="form-control <?= isset($erroresFila['nombre']) ? 'is-invalid' : '' ?>"
                                                   placeholder="Ej. Control de signos vitales">
                                            <?php if (isset($erroresFila['nombre'])): ?>
                                                <div class="invalid-feedback"><?= esc($erroresFila['nombre']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Fecha inicio <span class="text-danger">*</span></label>
                                            <input type="date" name="actividad_fecha_inicio[]"
                                                   value="<?= esc($fechaInicioActividad) ?>"
                                                   class="form-control <?= isset($erroresFila['fecha_inicio']) ? 'is-invalid' : '' ?>">
                                            <?php if (isset($erroresFila['fecha_inicio'])): ?>
                                                <div class="invalid-feedback"><?= esc($erroresFila['fecha_inicio']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Fecha fin <span class="text-danger">*</span></label>
                                            <input type="date" name="actividad_fecha_fin[]"
                                                   value="<?= esc($fechaFinActividad) ?>"
                                                   class="form-control <?= isset($erroresFila['fecha_fin']) ? 'is-invalid' : '' ?>">
                                            <?php if (isset($erroresFila['fecha_fin'])): ?>
                                                <div class="invalid-feedback"><?= esc($erroresFila['fecha_fin']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Descripción <span class="text-danger">*</span></label>
                                        <textarea name="actividad_descripcion[]" rows="3" maxlength="2000"
                                                  class="form-control <?= isset($erroresFila['descripcion']) ? 'is-invalid' : '' ?>"
                                                  placeholder="Detalle de la actividad"><?= esc($descripcionActividad) ?></textarea>
                                        <?php if (isset($erroresFila['descripcion'])): ?>
                                            <div class="invalid-feedback"><?= esc($erroresFila['descripcion']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <p class="text-muted mb-0">
                        Añade al menos una actividad. Todas comienzan en estado <strong>sin iniciar</strong>.
                    </p>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-1"></i> Guardar plan de cuidado
                </button>
            </div>
        </form>
    </div>
</div>

<template id="actividad-template">
    <div class="card mb-3 actividad-item" data-index="__index__">
        <div class="card-body pb-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0">Actividad __numero__</h5>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remover-actividad">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="actividad_nombre[]" maxlength="120" class="form-control" placeholder="Ej. Control de signos vitales">
                </div>
                <div class="form-group col-md-3">
                    <label>Fecha inicio <span class="text-danger">*</span></label>
                    <input type="date" name="actividad_fecha_inicio[]" class="form-control">
                </div>
                <div class="form-group col-md-3">
                    <label>Fecha fin <span class="text-danger">*</span></label>
                    <input type="date" name="actividad_fecha_fin[]" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Descripción <span class="text-danger">*</span></label>
                <textarea name="actividad_descripcion[]" rows="3" maxlength="2000" class="form-control" placeholder="Detalle de la actividad"></textarea>
            </div>
        </div>
    </div>
</template>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        const pacienteSelect = document.getElementById('paciente_id');
        const diagnosticoSelect = document.getElementById('diagnostico_id');
        const btnAgregarActividad = document.getElementById('btn-agregar-actividad');
        const contenedorActividades = document.getElementById('contenedor-actividades');
        const template = document.getElementById('actividad-template').innerHTML;

        function filtrarDiagnosticosPorPaciente() {
            const pacienteId = pacienteSelect.value;
            let hayDiagnosticos = false;

            Array.from(diagnosticoSelect.options).forEach((option) => {
                if (!option.dataset.pacienteId) {
                    return;
                }

                const coincide = pacienteId && option.dataset.pacienteId === pacienteId;
                option.hidden = !coincide;

                if (!coincide && option.selected) {
                    option.selected = false;
                }

                if (coincide) {
                    hayDiagnosticos = true;
                }
            });

            diagnosticoSelect.disabled = !hayDiagnosticos;
        }

        function actualizarNumeracionActividades() {
            const items = contenedorActividades.querySelectorAll('.actividad-item');
            items.forEach((item, index) => {
                item.dataset.index = index;
                const titulo = item.querySelector('.card-title');
                if (titulo) {
                    titulo.textContent = 'Actividad ' + (index + 1);
                }
            });
        }

        function agregarActividad() {
            const indice = contenedorActividades.querySelectorAll('.actividad-item').length;
            const html = template
                .replace(/__index__/g, indice.toString())
                .replace(/__numero__/g, (indice + 1).toString());

            const contenedor = document.createElement('div');
            contenedor.innerHTML = html.trim();
            contenedorActividades.appendChild(contenedor.firstChild);
            actualizarNumeracionActividades();
        }

        function removerActividad(event) {
            const boton = event.target.closest('.btn-remover-actividad');
            if (!boton) {
                return;
            }

            const actividad = boton.closest('.actividad-item');
            if (!actividad) {
                return;
            }

            if (contenedorActividades.querySelectorAll('.actividad-item').length === 1) {
                actividad.querySelectorAll('input, textarea').forEach((campo) => {
                    campo.value = '';
                });
                return;
            }

            actividad.remove();
            actualizarNumeracionActividades();
        }

        if (pacienteSelect) {
            filtrarDiagnosticosPorPaciente();
            pacienteSelect.addEventListener('change', filtrarDiagnosticosPorPaciente);
        }

        if (btnAgregarActividad) {
            btnAgregarActividad.addEventListener('click', agregarActividad);
        }

        if (contenedorActividades) {
            contenedorActividades.addEventListener('click', removerActividad);
        }
    })();
</script>
<?= $this->endSection() ?>
