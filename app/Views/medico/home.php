<?= $this->extend('layouts/base') ?>

<?= $this->section('styles') ?>
<style>
.dashboard-kpi-col {
    display: flex;
}
.dashboard-kpi-box {
    width: 100%;
    min-height: 140px;
}
.dashboard-kpi-box .info-box-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$medico     = $medico ?? null;
$dashboard  = $dashboard ?? [];
$kpis       = $dashboard['kpis'] ?? [];
$charts     = $dashboard['charts'] ?? [];
$ultimos    = $dashboard['diagnosticosRecientes'] ?? [];

$nombreMedico = '';
if ($medico !== null) {
    $nombreMedico = trim((string) ($medico->nombre ?? '') . ' ' . ($medico->apellido ?? ''));
}
if ($nombreMedico === '') {
    $nombreMedico = 'Profesional';
}

$formatEntero = static function ($valor): string {
    return number_format((float) $valor, 0, ',', '.');
};
$formatDecimal = static function ($valor): string {
    return number_format((float) $valor, 1, ',', '.');
};

$hayDatosDiagnosticosTipo = array_sum($charts['diagnosticosPorTipo']['values'] ?? []) > 0;
$hayDatosPlanesEstado     = array_sum($charts['planesPorEstado']['values'] ?? []) > 0;
$hayDatosDiagnosticosMes  = array_sum($charts['diagnosticosPorMes']['values'] ?? []) > 0;

$totalActividades        = $kpis['actividadesValidadas']['totales'] ?? 0;
$actividadesValidadas    = $kpis['actividadesValidadas']['total'] ?? 0;
$porcActividadesValidadas = $kpis['actividadesValidadas']['porcentaje'] ?? 0.0;
$planesFinalizadosTotal  = $kpis['planesFinalizados']['total'] ?? 0;
$planesFinalizadosPorc   = $kpis['planesFinalizados']['porcentaje'] ?? 0.0;

$diagnosticosTotales = $kpis['totalDiagnosticos'] ?? 0;
$diagnosticosActivos = $kpis['diagnosticosActivos'] ?? 0;
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-1"><?= esc($title ?? 'Dashboard del Médico') ?></h1>
        <p class="text-muted mb-0">Hola <?= esc($nombreMedico) ?>, aquí tienes una vista general de tus pacientes y planes.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
        <a href="<?= route_to('medico_diagnosticos_index') ?>" class="btn btn-outline-primary">
            <i class="fas fa-stethoscope mr-1"></i> Ver diagnósticos
        </a>
        <a href="<?= route_to('medico_planes_index') ?>" class="btn btn-primary">
            <i class="fas fa-clipboard-list mr-1"></i> Ver planes de cuidado
        </a>
    </div>
</div>

<?= view('layouts/partials/alerts') ?>

<div class="row">
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-info"><i class="fas fa-user-injured"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pacientes diagnosticados</span>
                <span class="info-box-number"><?= esc($formatEntero($kpis['pacientesDiagnosticados'] ?? 0)) ?></span>
                <small class="text-muted">Distinctos en tus diagnósticos</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-heartbeat"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Diagnósticos activos</span>
                <span class="info-box-number"><?= esc($formatEntero($diagnosticosActivos)) ?></span>
                <small class="text-muted">de <?= esc($formatEntero($diagnosticosTotales)) ?> registrados</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-teal"><i class="fas fa-notes-medical"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Planes creados</span>
                <span class="info-box-number"><?= esc($formatEntero($kpis['planesCreados'] ?? 0)) ?></span>
                <small class="text-muted">Planes personalizados que has generado</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-purple"><i class="fas fa-user-md"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pacientes bajo cuidado</span>
                <span class="info-box-number"><?= esc($formatEntero($kpis['pacientesBajoCuidado'] ?? 0)) ?></span>
                <small class="text-muted">Con planes vigentes</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Actividades validadas</span>
                <span class="info-box-number"><?= esc($formatDecimal($porcActividadesValidadas)) ?>%</span>
                <small class="text-muted"><?= esc($formatEntero($actividadesValidadas)) ?> de <?= esc($formatEntero($totalActividades)) ?> actividades</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-tasks"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Promedio actividades/plan</span>
                <span class="info-box-number"><?= esc($formatDecimal($kpis['promedioActividadesPorPlan'] ?? 0.0)) ?></span>
                <small class="text-muted">Distribución media por plan activo o finalizado</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-flag-checkered"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Planes finalizados</span>
                <span class="info-box-number"><?= esc($formatEntero($planesFinalizadosTotal)) ?> (<?= esc($formatDecimal($planesFinalizadosPorc)) ?>%)</span>
                <small class="text-muted">Con estado cerrado o fecha concluida</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-lightblue"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Duración promedio</span>
                <span class="info-box-number"><?= esc($formatDecimal($kpis['duracionPromedioPlanes'] ?? 0.0)) ?> días</span>
                <small class="text-muted">Entre inicio y fin de los planes válidos</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 col-lg-3 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-user-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Adherencia del paciente</span>
                <span class="info-box-number"><?= esc($formatDecimal($kpis['adherenciaPacientes'] ?? 0.0)) ?>%</span>
                <small class="text-muted">Actividades marcadas como terminadas</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-primary h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Diagnósticos por tipo</h3>
            </div>
            <div class="card-body">
                <?php if ($hayDatosDiagnosticosTipo): ?>
                    <div class="chartjs-container" style="position: relative; min-height: 260px; height: 260px; width: 100%;">
                        <canvas id="chartDiagnosticosTipo"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Aún no hay diagnósticos clasificados para mostrar.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-success h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Planes por estado</h3>
            </div>
            <div class="card-body">
                <?php if ($hayDatosPlanesEstado): ?>
                    <div class="chartjs-container" style="position: relative; min-height: 260px; height: 260px; width: 100%;">
                        <canvas id="chartPlanesEstado"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Todavía no hay planes con estado definido.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title mb-0">Diagnósticos creados por mes</h3>
            </div>
            <div class="card-body">
                <?php if ($hayDatosDiagnosticosMes): ?>
                    <div class="chartjs-container" style="position: relative; min-height: 300px; height: 300px; width: 100%;">
                        <canvas id="chartDiagnosticosMes"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Sin diagnósticos registrados en los últimos seis meses.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title mb-0">Últimos diagnósticos registrados</h3>
            </div>
            <div class="card-body p-0">
                <?php if (! empty($ultimos)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Paciente</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ultimos as $diagnostico): ?>
                                <?php
                                $pacienteNombre = trim((string) (($diagnostico['paciente_apellido'] ?? '') . ', ' . ($diagnostico['paciente_nombre'] ?? '')));
                                if ($pacienteNombre === '') {
                                    $pacienteNombre = 'Paciente sin datos';
                                }

                                $tipoNombre = trim((string) ($diagnostico['tipo_nombre'] ?? ''));
                                if ($tipoNombre === '') {
                                    $tipoNombre = 'Sin clasificación';
                                }

                                $fecha = $diagnostico['fecha_creacion'] ?? null;
                                $fechaFormateada = '-';
                                if ($fecha) {
                                    $timestamp = strtotime((string) $fecha);
                                    if ($timestamp !== false) {
                                        $fechaFormateada = date('d/m/Y', $timestamp);
                                    }
                                }

                                $descripcion = trim((string) ($diagnostico['descripcion'] ?? ''));
                                if ($descripcion === '') {
                                    $descripcionResumida = 'Sin descripción';
                                } else {
                                    if (function_exists('mb_strlen')) {
                                        $descripcionResumida = mb_strlen($descripcion) > 120
                                            ? mb_substr($descripcion, 0, 117) . '...'
                                            : $descripcion;
                                    } else {
                                        $descripcionResumida = strlen($descripcion) > 120
                                            ? substr($descripcion, 0, 117) . '...'
                                            : $descripcion;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= esc($pacienteNombre) ?></td>
                                    <td><?= esc($tipoNombre) ?></td>
                                    <td><?= esc($fechaFormateada) ?></td>
                                    <td><?= esc($descripcionResumida) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4">
                        <p class="text-muted mb-0">No se encontraron diagnósticos recientes. Comienza creando uno para tus pacientes.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('adminlte/plugins/chart.js/Chart.min.js') ?>"></script>
<script>
(function () {
    const charts = <?= json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const chartVersionMajor = (window.Chart && typeof Chart.version === 'string')
        ? parseInt(Chart.version.split('.')[0], 10)
        : 0;
    const isChartV3Plus = chartVersionMajor >= 3;

    const crearChart = function (canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) {
            return null;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return null;
        }

        return new Chart(ctx, config);
    };

    const diagnosticosPorTipo = charts && charts.diagnosticosPorTipo ? charts.diagnosticosPorTipo : {labels: [], values: []};
    if (Array.isArray(diagnosticosPorTipo.values) && diagnosticosPorTipo.values.some(function (valor) { return valor > 0; })) {
        const escalaLineal = isChartV3Plus
            ? {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
            : {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }]
                }
            };

        crearChart('chartDiagnosticosTipo', {
            type: 'bar',
            data: {
                labels: diagnosticosPorTipo.labels,
                datasets: [{
                    label: 'Diagnósticos',
                    data: diagnosticosPorTipo.values,
                    backgroundColor: '#3c8dbc'
                }]
            },
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
            }, escalaLineal)
        });
    }

    const planesPorEstado = charts && charts.planesPorEstado ? charts.planesPorEstado : {labels: [], values: []};
    if (Array.isArray(planesPorEstado.values) && planesPorEstado.values.some(function (valor) { return valor > 0; })) {
        const colores = [
            '#28a745',
            '#007bff',
            '#ffc107',
            '#dc3545',
            '#6c757d',
            '#17a2b8'
        ];

        crearChart('chartPlanesEstado', {
            type: 'doughnut',
            data: {
                labels: planesPorEstado.labels,
                datasets: [{
                    data: planesPorEstado.values,
                    backgroundColor: colores
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    const diagnosticosPorMes = charts && charts.diagnosticosPorMes ? charts.diagnosticosPorMes : {labels: [], values: []};
    if (Array.isArray(diagnosticosPorMes.values) && diagnosticosPorMes.values.some(function (valor) { return valor > 0; })) {
        const escalaLineal = isChartV3Plus
            ? {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
            : {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }]
                }
            };

        crearChart('chartDiagnosticosMes', {
            type: 'line',
            data: {
                labels: diagnosticosPorMes.labels,
                datasets: [{
                    label: 'Diagnósticos creados',
                    data: diagnosticosPorMes.values,
                    fill: false,
                    borderColor: '#17a2b8',
                    backgroundColor: '#17a2b8',
                    tension: 0.2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
            }, escalaLineal)
        });
    }
})();
</script>
<?= $this->endSection() ?>
