<?php

$isEdit          = (bool) ($isEdit ?? false);
$formAction      = $formAction ?? '';
$formMethod      = strtolower($formMethod ?? 'post');
$submitLabel     = $submitLabel ?? ($isEdit ? 'Actualizar plan de cuidado' : 'Guardar plan de cuidado');
$plan            = $plan ?? [];
$pacientes       = $pacientes ?? [];
$diagnosticos    = $diagnosticos ?? [];
$errores         = $errors ?? [];
$actividadErrors = $actividadErrors ?? [];
$actividades     = $actividades ?? [];
$pacienteSeleccionado = $pacienteSeleccionado ?? null;
$terminoBusquedaPaciente = $terminoBusquedaPaciente ?? '';

$selectedPacienteId    = old('paciente_id');
$selectedDiagnosticoId = old('diagnostico_id');

if ($selectedPacienteId === null && isset($plan['paciente_id'])) {
    $selectedPacienteId = (int) $plan['paciente_id'];
}

if ($selectedDiagnosticoId === null && isset($plan['diagnostico_id'])) {
    $selectedDiagnosticoId = (int) $plan['diagnostico_id'];
}

$nombrePlan     = old('nombre', $plan['nombre'] ?? '');
$descripcionPlan = old('descripcion', $plan['descripcion'] ?? '');
$fechaInicio    = old('fecha_inicio', $plan['fecha_inicio'] ?? '');
$fechaFin       = old('fecha_fin', $plan['fecha_fin'] ?? '');

$oldNombres       = (array) old('actividad_nombre');
$oldDescripciones = (array) old('actividad_descripcion');
$oldFechasInicio  = (array) old('actividad_fecha_inicio');
$oldFechasFin     = (array) old('actividad_fecha_fin');
$oldIds           = (array) old('actividad_id');

$actividadesForm = [];

$hayOld = count($oldNombres) > 0
    || count($oldDescripciones) > 0
    || count($oldFechasInicio) > 0
    || count($oldFechasFin) > 0
    || count($oldIds) > 0;

if ($hayOld) {
    $max = max(count($oldNombres), count($oldDescripciones), count($oldFechasInicio), count($oldFechasFin), count($oldIds));

    for ($index = 0; $index < $max; $index++) {
        $actividadesForm[] = [
            'id'           => $oldIds[$index] ?? null,
            'nombre'       => $oldNombres[$index] ?? '',
            'descripcion'  => $oldDescripciones[$index] ?? '',
            'fecha_inicio' => $oldFechasInicio[$index] ?? '',
            'fecha_fin'    => $oldFechasFin[$index] ?? '',
        ];
    }
} elseif (! empty($actividades)) {
    foreach ($actividades as $actividad) {
        $actividadesForm[] = [
            'id'           => $actividad['id'] ?? null,
            'nombre'       => $actividad['nombre'] ?? '',
            'descripcion'  => $actividad['descripcion'] ?? '',
            'fecha_inicio' => $actividad['fecha_inicio'] ?? '',
            'fecha_fin'    => $actividad['fecha_fin'] ?? '',
        ];
    }
}

if (empty($actividadesForm)) {
    $actividadesForm[] = [
        'id'           => null,
        'nombre'       => '',
        'descripcion'  => '',
        'fecha_inicio' => '',
        'fecha_fin'    => '',
    ];
}

$descripcionDiagnostico = $plan['diagnostico_descripcion'] ?? null;
$pacienteNombreCompleto = null;
$pacienteDniSeleccionado = null;
$tienePacienteSeleccionado = false;

if (isset($plan['paciente_apellido'], $plan['paciente_nombre'])) {
    $pacienteNombreCompleto = trim($plan['paciente_apellido'] . ', ' . $plan['paciente_nombre']);
}

if ($pacienteSeleccionado !== null) {
    $pacienteNombreCompleto = trim(($pacienteSeleccionado->apellido ?? '') . ', ' . ($pacienteSeleccionado->nombre ?? ''));
    $pacienteDniSeleccionado = $pacienteSeleccionado->dni ?? null;
}
$tienePacienteSeleccionado = (bool) ($selectedPacienteId && $pacienteNombreCompleto);
$diagnosticoDeshabilitado = ! $selectedPacienteId;
?>

<form action="<?= esc($formAction) ?>" method="post" id="form-plan-personalizado">
    <?= csrf_field() ?>
    <?php if ($formMethod !== 'post'): ?>
        <input type="hidden" name="_method" value="<?= esc(strtoupper($formMethod)) ?>">
    <?php endif; ?>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Información del plan</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="paciente_id">Paciente <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="paciente_id" value="<?= esc($plan['paciente_id'] ?? '') ?>">
                        <input type="text" class="form-control" value="<?= esc($pacienteNombreCompleto ?? 'Paciente sin datos') ?>" readonly>
                    <?php else: ?>
                        <input type="hidden" name="paciente_id" id="paciente_id" value="<?= esc($selectedPacienteId ?? '') ?>">
                        <div class="input-group">
                            <input type="text"
                                   name="busqueda_paciente"
                                   id="busqueda_paciente"
                                   class="form-control <?= isset($errores['paciente_id']) ? 'is-invalid' : '' ?>"
                                   placeholder="Ingresa nombre o DNI del paciente"
                                   value="<?= esc($terminoBusquedaPaciente) ?>"
                                   autocomplete="off">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary" id="btn-buscar-paciente">
                                    <i class="fas fa-search mr-1"></i> Buscar
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Mínimo 2 caracteres para nombre o 4 dígitos para DNI. Solo se listan pacientes activos.
                        </small>
                        <?php if (isset($errores['paciente_id'])): ?>
                            <div class="invalid-feedback d-block"><?= esc($errores['paciente_id']) ?></div>
                        <?php endif; ?>
                        <div id="paciente-seleccionado" class="alert alert-info bg-info text-white border-0 shadow-sm mt-3 <?= $tienePacienteSeleccionado ? '' : 'd-none' ?>" role="alert">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong id="paciente-seleccionado-nombre"><?= esc($pacienteNombreCompleto ?? '') ?></strong>
                                    <div class="text-white small font-weight-semibold mb-0" id="paciente-seleccionado-dni">
                                        <?= esc($pacienteDniSeleccionado ? 'DNI: ' . $pacienteDniSeleccionado : '') ?>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light text-info font-weight-bold align-self-center" id="btn-limpiar-paciente">
                                    Cambiar paciente
                                </button>
                            </div>
                        </div>
                        <div id="pacientes-resultados" class="list-group mt-3"></div>
                    <?php endif; ?>
                </div>
                <div class="form-group col-md-6">
                    <label for="diagnostico_id">Diagnóstico <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="diagnostico_id" value="<?= esc($plan['diagnostico_id'] ?? '') ?>">
                        <input type="text" class="form-control" value="<?= esc($descripcionDiagnostico ? ('Diag #' . ($plan['diagnostico_id'] ?? '') . ' — ' . $descripcionDiagnostico) : 'Diagnóstico sin descripción') ?>" readonly>
                    <?php else: ?>
                        <select name="diagnostico_id" id="diagnostico_id" class="form-control <?= isset($errores['diagnostico_id']) ? 'is-invalid' : '' ?>" required <?= $diagnosticoDeshabilitado ? 'disabled' : '' ?>>
                            <option value="">Selecciona un diagnóstico</option>
                            <?php foreach ($diagnosticos as $diagnostico): ?>
                                <?php
                                $pertenecePaciente = (int) ($diagnostico['destinatario_user_id'] ?? 0);
                                $descripcionDiag    = trim((string) ($diagnostico['descripcion'] ?? ''));
                                if (mb_strlen($descripcionDiag) > 120) {
                                    $descripcionDiag = mb_substr($descripcionDiag, 0, 117) . '...';
                                }
                                $esSeleccionado = (int) ($selectedDiagnosticoId ?? 0) === (int) ($diagnostico['id'] ?? 0);
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
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nombre">Nombre del plan</label>
                    <input type="text" name="nombre" id="nombre" maxlength="180"
                           value="<?= esc($nombrePlan) ?>"
                           class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                           placeholder="Ej. Plan de control post operatorio">
                    <?php if (isset($errores['nombre'])): ?>
                        <div class="invalid-feedback"><?= esc($errores['nombre']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group col-md-3">
                    <label for="fecha_inicio">Fecha inicio <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= esc($fechaInicio) ?>"
                           class="form-control <?= isset($errores['fecha_inicio']) ? 'is-invalid' : '' ?>" required>
                    <?php if (isset($errores['fecha_inicio'])): ?>
                        <div class="invalid-feedback"><?= esc($errores['fecha_inicio']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group col-md-3">
                    <label for="fecha_fin">Fecha fin <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_fin" id="fecha_fin" value="<?= esc($fechaFin) ?>"
                           class="form-control <?= isset($errores['fecha_fin']) ? 'is-invalid' : '' ?>" required>
                    <?php if (isset($errores['fecha_fin'])): ?>
                        <div class="invalid-feedback"><?= esc($errores['fecha_fin']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción del plan</label>
                <textarea name="descripcion" id="descripcion" rows="3" maxlength="2000"
                          class="form-control <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>"
                          placeholder="Contexto clínico, objetivos, consideraciones"><?= esc($descripcionPlan) ?></textarea>
                <?php if (isset($errores['descripcion'])): ?>
                    <div class="invalid-feedback"><?= esc($errores['descripcion']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title mb-0">Actividades del plan</h3>
        </div>
        <div class="card-body" id="contenedor-actividades">
            <?php foreach ($actividadesForm as $index => $actividad): ?>
                <?php $erroresFila = $actividadErrors[$index] ?? []; ?>
                <div class="card mb-3 actividad-item" data-index="<?= esc($index) ?>">
                    <div class="card-body pb-2">
                        <input type="hidden" name="actividad_id[]" value="<?= esc($actividad['id']) ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0">Actividad <?= esc($index + 1) ?></h5>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remover-actividad">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="actividad_nombre[]" maxlength="120"
                                       value="<?= esc($actividad['nombre']) ?>"
                                       class="form-control <?= isset($erroresFila['nombre']) ? 'is-invalid' : '' ?>"
                                       placeholder="Ej. Control de signos vitales">
                                <?php if (isset($erroresFila['nombre'])): ?>
                                    <div class="invalid-feedback"><?= esc($erroresFila['nombre']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Fecha inicio <span class="text-danger">*</span></label>
                                <input type="date" name="actividad_fecha_inicio[]"
                                       value="<?= esc($actividad['fecha_inicio']) ?>"
                                       class="form-control <?= isset($erroresFila['fecha_inicio']) ? 'is-invalid' : '' ?>">
                                <?php if (isset($erroresFila['fecha_inicio'])): ?>
                                    <div class="invalid-feedback"><?= esc($erroresFila['fecha_inicio']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Fecha fin <span class="text-danger">*</span></label>
                                <input type="date" name="actividad_fecha_fin[]"
                                       value="<?= esc($actividad['fecha_fin']) ?>"
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
                                      placeholder="Detalle de la actividad"><?= esc($actividad['descripcion']) ?></textarea>
                            <?php if (isset($erroresFila['descripcion'])): ?>
                                <div class="invalid-feedback"><?= esc($erroresFila['descripcion']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center px-3 pb-3">
            <button type="button" class="btn btn-primary mb-3" id="btn-agregar-actividad">
                <i class="fas fa-plus mr-1"></i> Agregar actividad
            </button>
            <p class="text-muted mb-0">
                Añade al menos una actividad. Todas comienzan en estado <strong>sin iniciar</strong>.
            </p>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save mr-1"></i> <?= esc($submitLabel) ?>
        </button>
    </div>
</form>

<template id="actividad-template">
    <div class="card mb-3 actividad-item" data-index="__index__">
        <div class="card-body pb-2">
            <input type="hidden" name="actividad_id[]" value="">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0">Actividad __numero__</h5>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remover-actividad">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="actividad_nombre[]" maxlength="120" class="form-control"
                           placeholder="Ej. Control de signos vitales">
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
                <textarea name="actividad_descripcion[]" rows="3" maxlength="2000" class="form-control"
                          placeholder="Detalle de la actividad"></textarea>
            </div>
        </div>
    </div>
</template>
