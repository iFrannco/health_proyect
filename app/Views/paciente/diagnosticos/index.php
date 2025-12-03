<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$diagnosticos       = $diagnosticos ?? [];
$conteos            = $conteos ?? [];
$filtrosDisponibles = $filtrosDisponibles ?? [];
$filtroActual       = $filtroActual ?? 'activos';

$formatearFecha = static function (?string $fecha): string {
    if (! $fecha) {
        return '-';
    }

    $timestamp = strtotime($fecha);

    return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
};

$totalDiagnosticos   = (int) ($conteos['todos'] ?? 0);
$diagnosticosFiltrados = count($diagnosticos);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-1">Diagnósticos</h1>
        <p class="text-muted mb-0">Consultá tus diagnósticos registrados, revisá quién los emitió y los planes asociados.</p>
    </div>
    <div class="text-muted">
        <small>Total registrados: <?= esc($totalDiagnosticos) ?></small>
    </div>
</div>

<?= view('layouts/partials/alerts') ?>

<div class="mb-4">
    <div class="btn-group" role="group" aria-label="Filtros de diagnósticos">
        <?php foreach ($filtrosDisponibles as $slug => $label): ?>
            <?php
            $isActive = $slug === $filtroActual;
            $conteo   = (int) ($conteos[$slug] ?? 0);
            ?>
            <a href="<?= site_url('paciente/diagnosticos?estado=' . urlencode($slug)) ?>"
               class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= esc($label) ?>
                <span class="badge badge-light ml-1"><?= esc($conteo) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($diagnosticosFiltrados === 0): ?>
    <div class="alert alert-info">
        <?php if ($totalDiagnosticos === 0): ?>
            No tenés diagnósticos registrados por el momento.
        <?php else: ?>
            No se encontraron diagnósticos para el filtro seleccionado.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Listado de diagnósticos</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Médico</th>
                            <th class="text-center">Planes (A/F/T)</th>
                            <th class="text-nowrap">Estado</th>
                            <th class="text-nowrap text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diagnosticos as $diagnostico): ?>
                            <?php
                            $descripcion   = trim((string) ($diagnostico['descripcion'] ?? ''));
                            $descripcionCorta = $descripcion;
                            if (mb_strlen($descripcionCorta) > 150) {
                                $descripcionCorta = mb_substr($descripcionCorta, 0, 147) . '...';
                            }

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

                            $medico = $diagnostico['medico'] ?? [];
                            $medicoNombre = trim((string) ($medico['nombre_completo'] ?? ''));
                            $fechaCreacion = $formatearFecha($diagnostico['fecha_creacion'] ?? null);
                            $planesActivos = (int) ($diagnostico['planes_activos'] ?? 0);
                            $planesFinalizados = (int) ($diagnostico['planes_finalizados'] ?? 0);
                            $planesTotales = (int) ($diagnostico['planes_totales'] ?? 0);
                            ?>
                            <tr>
                                <td class="align-middle">
                                    <strong><?= esc($tipoNombre) ?></strong>
                                </td>
                                <td class="align-middle">
                                    <?= esc($descripcionCorta !== '' ? $descripcionCorta : 'Sin descripción disponible.') ?>
                                </td>
                                <td class="align-middle text-nowrap">
                                    <i class="far fa-calendar-alt mr-1 text-muted"></i><?= esc($fechaCreacion) ?>
                                </td>
                                <td class="align-middle">
                                    <?= $medicoNombre !== '' ? esc($medicoNombre) : '<span class="text-muted">Médico no disponible</span>' ?>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-success font-weight-bold"><?= esc($planesActivos) ?></span> /
                                    <span class="text-muted"><?= esc($planesFinalizados) ?></span> /
                                    <span><?= esc($planesTotales) ?></span>
                                </td>
                                <td class="align-middle">
                                    <span class="badge <?= esc($badgeClass) ?>"><?= esc($estadoEtiqueta) ?></span>
                                </td>
                                <td class="align-middle text-center">
                                    <a href="<?= route_to('paciente_diagnosticos_show', $diagnostico['id']) ?>" class="btn btn-outline-primary btn-sm">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
