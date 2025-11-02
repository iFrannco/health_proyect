<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$plan           = $plan ?? [];
$actividades    = $actividades ?? [];
$resumen        = $resumen ?? ['total' => 0, 'porEstado' => [], 'validadas' => 0, 'noValidadas' => 0];

$planTitulo = trim((string) ($plan['nombre'] ?? ''));
if ($planTitulo === '') {
    $planTitulo = 'Plan sin nombre';
}

$descripcionDiagnostico = trim((string) ($plan['diagnostico_descripcion'] ?? ''));
if ($descripcionDiagnostico === '') {
    $descripcionDiagnostico = 'Diagnóstico sin descripción';
}

$pacienteNombre = trim((string) (($plan['paciente_apellido'] ?? '') . ', ' . ($plan['paciente_nombre'] ?? '')));
if ($pacienteNombre === '') {
    $pacienteNombre = 'Paciente sin datos';
}

$formatearFecha = static function (?string $fecha, bool $conHora = false): string {
    if (! $fecha) {
        return '-';
    }

    $timestamp = strtotime($fecha);
    if (! $timestamp) {
        return '-';
    }

    return $conHora ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
};

$fechaInicio   = $formatearFecha($plan['fecha_inicio'] ?? null);
$fechaFin      = $formatearFecha($plan['fecha_fin'] ?? null);
$fechaCreacion = $formatearFecha($plan['fecha_creacion'] ?? null, true);
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Detalle del plan</h1>
                <p class="text-muted mb-0">
                    <?= esc($planTitulo) ?> — Paciente <?= esc($pacienteNombre) ?>
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: .5rem;">
                <a href="<?= route_to('medico_planes_index') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al listado
                </a>
                <a href="<?= route_to('medico_planes_edit', $plan['id']) ?>" class="btn btn-primary">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
                <form action="<?= route_to('medico_planes_delete', $plan['id']) ?>" method="post" class="d-inline"
                      id="form-eliminar-plan">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Información general</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted text-uppercase mb-1">Paciente</h6>
                        <p class="mb-0 font-weight-bold"><?= esc($pacienteNombre) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted text-uppercase mb-1">Diagnóstico</h6>
                        <p class="mb-0"><?= esc('Diag #' . ($plan['diagnostico_id'] ?? '-') . ' — ' . $descripcionDiagnostico) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted text-uppercase mb-1">Creado</h6>
                        <p class="mb-0"><?= esc($fechaCreacion) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted text-uppercase mb-1">Vigencia</h6>
                        <p class="mb-0"><?= esc($fechaInicio . ' → ' . $fechaFin) ?></p>
                    </div>
                    <div class="col-md-8 mb-3">
                        <h6 class="text-muted text-uppercase mb-1">Descripción del plan</h6>
                        <?php if (! empty($plan['descripcion'])): ?>
                            <p class="mb-0"><?= nl2br(esc($plan['descripcion'])) ?></p>
                        <?php else: ?>
                            <p class="text-muted mb-0">Sin descripción registrada.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-tasks"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Actividades totales</span>
                        <span class="info-box-number"><?= esc($resumen['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <?php foreach ($resumen['porEstado'] ?? [] as $estadoResumen): ?>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-flag"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= esc($estadoResumen['nombre'] ?? 'Estado') ?></span>
                            <span class="info-box-number"><?= esc($estadoResumen['total'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Validadas</span>
                        <span class="info-box-number"><?= esc($resumen['validadas'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pendientes de validación</span>
                        <span class="info-box-number"><?= esc($resumen['noValidadas'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title mb-0">Actividades</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($actividades)): ?>
                    <div class="p-4 text-center text-muted">
                        Este plan aún no tiene actividades registradas.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col" class="text-nowrap">Inicio</th>
                                    <th scope="col" class="text-nowrap">Fin</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Validado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actividades as $actividad): ?>
                                    <?php
                                    $descripcion = trim((string) ($actividad['descripcion'] ?? ''));
                                    if (mb_strlen($descripcion) > 140) {
                                        $descripcion = mb_substr($descripcion, 0, 137) . '...';
                                    }
                                    $validado = $actividad['validado'];
                                    $estadoNombre = $actividad['estado_nombre'] ?? 'Estado sin nombre';
                                    ?>
                                    <tr>
                                        <td><?= esc($actividad['nombre'] ?? 'Actividad') ?></td>
                                        <td><?= esc($descripcion) ?></td>
                                        <td class="text-nowrap"><?= esc($formatearFecha($actividad['fecha_inicio'] ?? null)) ?></td>
                                        <td class="text-nowrap"><?= esc($formatearFecha($actividad['fecha_fin'] ?? null)) ?></td>
                                        <td>
                                            <span class="badge badge-secondary"><?= esc($estadoNombre) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($validado === null): ?>
                                                <span class="badge badge-warning">Pendiente</span>
                                            <?php elseif ($validado): ?>
                                                <span class="badge badge-success">Sí</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<div class="modal fade" id="modal-confirmar-eliminar" tabindex="-1" role="dialog" aria-labelledby="modal-confirmar-eliminar-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="transform: translateY(-10%);">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modal-confirmar-eliminar-label">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Confirmar eliminación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Eliminarás el plan <strong><?= esc($planTitulo) ?></strong> y todas sus actividades asociadas.</p>
                <p class="mb-0 text-muted">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer justify-content-center" style="gap: .75rem;">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-eliminar">Eliminar</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const formEliminar = document.getElementById('form-eliminar-plan');
        const modalElement = $('#modal-confirmar-eliminar');
        const botonConfirmar = document.getElementById('btn-confirmar-eliminar');

        if (!formEliminar || modalElement.length === 0 || !botonConfirmar) {
            return;
        }

        let submitPendiente = false;

        formEliminar.addEventListener('submit', function (event) {
            if (submitPendiente) {
                submitPendiente = false;
                return;
            }

            event.preventDefault();
            modalElement.modal('show');
        });

        botonConfirmar.addEventListener('click', function () {
            submitPendiente = true;
            modalElement.modal('hide');
            formEliminar.submit();
        });
    })();
</script>
<?= $this->endSection() ?>
