<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$planes             = $planes ?? [];
$conteos            = $conteos ?? [];
$filtrosDisponibles = $filtrosDisponibles ?? [];
$filtroActual       = $filtroActual ?? 'activos';

$formatearFecha = static function (?string $fecha): string {
    if (! $fecha) {
        return '-';
    }

    $timestamp = strtotime($fecha);

    return $timestamp ? date('d/m/Y', $timestamp) : '-';
};

$totalAsignados = (int) ($conteos['todos'] ?? 0);
$planesFiltrados = count($planes);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-1">Planes de cuidado</h1>
        <p class="text-muted mb-0">Visualizá tus planes asignados, filtralos por estado y hacé seguimiento de tu progreso.</p>
    </div>
    <div class="text-muted">
        <small>Planes totales asignados: <?= esc($totalAsignados) ?></small>
    </div>
</div>

<div class="mb-4">
    <div class="btn-group" role="group" aria-label="Filtros de planes">
        <?php foreach ($filtrosDisponibles as $slug => $label): ?>
            <?php
            $isActive = $slug === $filtroActual;
            $conteo   = (int) ($conteos[$slug] ?? 0);
            ?>
            <a href="<?= site_url('paciente/planes?estado=' . urlencode($slug)) ?>"
               class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= esc($label) ?>
                <span class="badge badge-light ml-1"><?= esc($conteo) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($planesFiltrados === 0): ?>
    <div class="alert alert-info">
        <?php if ($totalAsignados === 0): ?>
            No tenés planes de cuidado activos por el momento.
        <?php else: ?>
            No se encontraron planes para el filtro seleccionado.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($planes as $plan): ?>
            <?php
            $nombre = trim((string) ($plan['nombre'] ?? ''));
            if ($nombre === '') {
                $nombre = 'Plan sin nombre';
            }

            $descripcion = trim((string) ($plan['descripcion'] ?? ''));
            $diagnostico = trim((string) ($plan['diagnostico'] ?? ''));
            if ($diagnostico === '') {
                $diagnostico = 'Diagnóstico sin descripción';
            }

            $medicoDisponible   = ! empty($plan['medico_disponible']);
            $medicoNombre       = trim((string) ($plan['medico_nombre'] ?? ''));
            $medicoEspecialidad = trim((string) ($plan['medico_especialidad'] ?? ''));

            $badgeClass = match ($plan['estado_categoria'] ?? '') {
                'finalizados' => 'badge-success',
                'futuros'     => 'badge-secondary',
                default       => 'badge-info',
            };

            $porcentaje = (int) ($plan['porcentaje_completadas'] ?? 0);
            $totalActividades = (int) ($plan['total_actividades'] ?? 0);
            $totalCompletadas = (int) ($plan['total_completadas'] ?? 0);
            $totalPendientes = (int) ($plan['total_pendientes'] ?? 0);
            $totalVencidas = (int) ($plan['total_vencidas'] ?? 0);
            $totalValidadas = (int) ($plan['total_validadas'] ?? 0);
            $vigencia   = sprintf('%s → %s', $formatearFecha($plan['fecha_inicio'] ?? null), $formatearFecha($plan['fecha_fin'] ?? null));
            ?>
            <div class="col-12 col-md-6 col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <h5 class="mb-0"><?= esc($nombre) ?></h5>
                            <span class="badge <?= esc($badgeClass) ?>">
                                <?= esc($plan['estado_etiqueta'] ?? 'Activo') ?>
                            </span>
                        </div>
                        <p class="text-muted mb-2">
                            <i class="fas fa-stethoscope mr-1"></i>
                            <?= esc($diagnostico) ?>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-user-md mr-1"></i>
                            <?php if ($medicoDisponible && $medicoNombre !== ''): ?>
                                <?= esc($medicoNombre) ?>
                                <?php if ($medicoEspecialidad !== ''): ?>
                                    <small class="text-muted">(<?= esc($medicoEspecialidad) ?>)</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Médico responsable no disponible</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($descripcion !== ''): ?>
                            <p class="mb-3 text-truncate" title="<?= esc($descripcion) ?>">
                                <?= esc($descripcion) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted mb-3">Sin descripción registrada.</p>
                        <?php endif; ?>

                        <div class="mb-3">
                            <small class="text-muted text-uppercase d-block">Vigencia</small>
                            <strong><?= esc($vigencia) ?></strong>
                        </div>

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
                                <small>Pendientes: <?= esc($totalPendientes) ?></small>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small>Vencidas: <?= esc($totalVencidas) ?></small>
                                <small>Validadas: <?= esc($totalValidadas) ?></small>
                            </div>
                        </div>

                        <div class="mt-auto">
                            <a href="<?= route_to('paciente_planes_show', $plan['id']) ?>" class="btn btn-outline-primary btn-sm btn-block">
                                Ver detalle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
