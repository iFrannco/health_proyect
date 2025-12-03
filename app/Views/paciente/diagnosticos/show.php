<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$diagnostico = $diagnostico ?? [];
$planes      = $planes ?? [];

$formatearFecha = static function (?string $fecha): string {
    if (! $fecha) {
        return '-';
    }

    $timestamp = strtotime($fecha);

    return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
};

$descripcionCompleta = trim((string) ($diagnostico['descripcion_completa'] ?? ($diagnostico['descripcion'] ?? '')));
$tipoNombre = trim((string) ($diagnostico['tipo_nombre'] ?? ''));
if ($tipoNombre === '') {
    $tipoNombre = 'Tipo no especificado';
}

$estadoSlug     = $diagnostico['estado_slug'] ?? 'sin_plan';
$estadoEtiqueta = $diagnostico['estado_etiqueta'] ?? 'Sin plan asignado';
$badgeClass = match ($estadoSlug) {
    'activo'     => 'badge-success',
    'sin_activo' => 'badge-secondary',
    default      => 'badge-warning',
};

$medico       = $diagnostico['medico'] ?? [];
$medicoNombre = trim((string) ($medico['nombre_completo'] ?? ''));
$fechaCreacion = $formatearFecha($diagnostico['fecha_creacion'] ?? null);
$planesActivos = (int) ($diagnostico['planes_activos'] ?? 0);
$planesFinalizados = (int) ($diagnostico['planes_finalizados'] ?? 0);
$planesTotales = (int) ($diagnostico['planes_totales'] ?? 0);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-1">Detalle del diagnóstico</h1>
        <p class="text-muted mb-0">Revisá la información registrada y los planes de cuidado vinculados.</p>
    </div>
    <div>
        <a href="<?= route_to('paciente_diagnosticos_index') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver al listado
        </a>
    </div>
</div>

<div class="card card-outline card-primary mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
            <div>
                <h4 class="mb-1"><?= esc($tipoNombre) ?></h4>
                <span class="badge <?= esc($badgeClass) ?>"><?= esc($estadoEtiqueta) ?></span>
            </div>
            <div class="text-right">
                <small class="text-muted">
                    <i class="far fa-calendar-alt mr-1"></i>
                    <?= esc($fechaCreacion) ?>
                </small>
            </div>
        </div>

        <div class="mt-3">
            <small class="text-muted text-uppercase d-block">Descripción</small>
            <p class="mb-2"><?= esc($descripcionCompleta !== '' ? $descripcionCompleta : 'Sin descripción registrada.') ?></p>
        </div>

        <div class="row mt-3">
            <div class="col-md-4 mb-3">
                <small class="text-muted text-uppercase d-block">Médico responsable</small>
                <?php if ($medicoNombre !== ''): ?>
                    <strong><?= esc($medicoNombre) ?></strong>
                <?php else: ?>
                    <span class="text-muted">Médico no disponible</span>
                <?php endif; ?>
            </div>
            <div class="col-md-8 mb-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase d-block">Planes activos</small>
                        <strong><?= esc($planesActivos) ?></strong>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase d-block">Planes finalizados</small>
                        <strong><?= esc($planesFinalizados) ?></strong>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase d-block">Planes totales</small>
                        <strong><?= esc($planesTotales) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">Planes de cuidado vinculados</h3>
        <small class="text-muted">Incluye planes en curso, futuros y finalizados</small>
    </div>
    <div class="card-body">
        <?php if (empty($planes)): ?>
            <p class="text-muted mb-0">No hay planes de cuidado vinculados a este diagnóstico.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($planes as $plan): ?>
                    <?php
                    $nombre = trim((string) ($plan['nombre'] ?? ''));
                    if ($nombre === '') {
                        $nombre = 'Plan sin nombre';
                    }

                    $descripcion = trim((string) ($plan['descripcion'] ?? ''));
                    $estadoCategoria = $plan['estado_categoria'] ?? '';
                    $estadoEtiquetaPlan = $plan['estado_etiqueta'] ?? 'En curso';
                    $badgePlan = match ($estadoCategoria) {
                        'finalizado'  => 'badge-success',
                        'sin_iniciar' => 'badge-secondary',
                        default       => 'badge-info',
                    };

                    $porcentaje = (int) ($plan['porcentaje_completadas'] ?? 0);
                    $fechaInicio = $formatearFecha($plan['fecha_inicio'] ?? null);
                    $fechaFin    = $formatearFecha($plan['fecha_fin'] ?? null);
                    $totalActividades = (int) ($plan['total_actividades'] ?? 0);
                    $totalCompletadas = (int) ($plan['total_completadas'] ?? 0);

                    $medicoPlan = $plan['medico'] ?? [];
                    $medicoPlanNombre = trim((string) ($medicoPlan['nombre_completo'] ?? ''));
                    ?>
                    <div class="col-12 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <h5 class="mb-1"><?= esc($nombre) ?></h5>
                                        <span class="badge <?= esc($badgePlan) ?>"><?= esc($estadoEtiquetaPlan) ?></span>
                                    </div>
                                    <small class="text-muted text-right">
                                        <div><i class="far fa-calendar-alt mr-1"></i>Inicio: <?= esc($fechaInicio) ?></div>
                                        <div><i class="far fa-calendar-check mr-1"></i>Fin: <?= esc($fechaFin) ?></div>
                                    </small>
                                </div>

                                <?php if ($descripcion !== ''): ?>
                                    <p class="text-muted mb-3"><?= esc($descripcion) ?></p>
                                <?php else: ?>
                                    <p class="text-muted mb-3">Sin descripción registrada.</p>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Progreso</small>
                                        <small><?= esc($porcentaje) ?>%</small>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" role="progressbar"
                                             style="width: <?= esc($porcentaje) ?>%;"
                                             aria-valuenow="<?= esc($porcentaje) ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small>Completadas: <?= esc($totalCompletadas . '/' . $totalActividades) ?></small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted text-uppercase d-block">Médico responsable</small>
                                    <?php if ($medicoPlanNombre !== ''): ?>
                                        <strong><?= esc($medicoPlanNombre) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">Médico no disponible</span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-auto">
                                    <a href="<?= route_to('paciente_planes_show', $plan['id']) ?>" class="btn btn-outline-primary btn-sm btn-block">
                                        Ver detalle del plan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
