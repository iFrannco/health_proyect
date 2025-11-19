<?= $this->extend('layouts/base') ?>

<?= $this->section('styles') ?>
<style>
.dashboard-small-box .inner {
    min-height: 125px;
}

.dashboard-small-box .icon {
    top: 12px;
}

.chart-card .card-body {
    min-height: 280px;
}

.chart-container {
    position: relative;
    width: 100%;
    height: 260px;
}

@media (min-width: 992px) {
    .chart-container {
        height: 320px;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$dashboard        = $dashboard ?? [];
$kpisUsuarios     = $dashboard['kpisUsuarios'] ?? [];
$kpisClinicos     = $dashboard['kpisClinicos'] ?? [];
$graficos         = $dashboard['graficos'] ?? [];
$comparativa      = $dashboard['comparativaMedicos'] ?? [];
$resumenPacientes = $dashboard['resumenPacientes'] ?? [];
$periodoLabel     = $dashboard['periodoLabel'] ?? 'Últimos 30 días';

$usuariosChart    = $graficos['usuariosPorRol'] ?? ['labels' => [], 'values' => [], 'total' => 0];
$planesChart      = $graficos['planesPorEstado'] ?? ['labels' => [], 'values' => [], 'total' => 0];

$hayUsuariosChart = array_sum($usuariosChart['values'] ?? []) > 0;
$hayPlanesChart   = array_sum($planesChart['values'] ?? []) > 0;

$comparativaHayMedicos = (bool) ($comparativa['hayMedicos'] ?? false);
$comparativaHayDatos   = (bool) ($comparativa['hayDatos'] ?? false);

$formatearEntero = static fn ($valor): string => number_format((float) $valor, 0, ',', '.');

$usuariosChartPayload = json_encode([
    'labels' => $usuariosChart['labels'] ?? [],
    'values' => $usuariosChart['values'] ?? [],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$usuariosChartPayload = $usuariosChartPayload === false ? 'null' : $usuariosChartPayload;

$planesChartPayload = json_encode([
    'labels' => $planesChart['labels'] ?? [],
    'values' => $planesChart['values'] ?? [],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$planesChartPayload = $planesChartPayload === false ? 'null' : $planesChartPayload;

$comparativaChartPayload = json_encode([
    'labels'       => $comparativa['labels'] ?? [],
    'diagnosticos' => $comparativa['diagnosticos'] ?? [],
    'planes'       => $comparativa['planes'] ?? [],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$comparativaChartPayload = $comparativaChartPayload === false ? 'null' : $comparativaChartPayload;
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-1"><?= esc($title ?? 'Panel de Administración') ?></h1>
        <p class="text-muted mb-0">Visión global del sistema · <?= esc($periodoLabel) ?></p>
    </div>
</div>

<?= view('layouts/partials/alerts') ?>

<div class="row">
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="small-box bg-info dashboard-small-box">
            <div class="inner">
                <h3><?= esc($formatearEntero($kpisUsuarios['pacientes'] ?? 0)) ?></h3>
                <p>Pacientes registrados</p>
            </div>
            <div class="icon"><i class="fas fa-user-injured"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="small-box bg-teal dashboard-small-box">
            <div class="inner">
                <h3><?= esc($formatearEntero($kpisUsuarios['medicos'] ?? 0)) ?></h3>
                <p>Médicos registrados</p>
            </div>
            <div class="icon"><i class="fas fa-user-md"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="small-box bg-purple dashboard-small-box">
            <div class="inner">
                <h3><?= esc($formatearEntero($kpisUsuarios['administradores'] ?? 0)) ?></h3>
                <p>Administradores</p>
            </div>
            <div class="icon"><i class="fas fa-user-shield"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="small-box bg-secondary text-white dashboard-small-box">
            <div class="inner">
                <h3><?= esc($formatearEntero($kpisUsuarios['inactivos'] ?? 0)) ?></h3>
                <p>Usuarios inactivos</p>
            </div>
            <div class="icon"><i class="fas fa-user-slash"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="small-box bg-primary dashboard-small-box">
            <div class="inner">
                <h3><?= esc($formatearEntero($kpisClinicos['diagnosticos30'] ?? 0)) ?></h3>
                <p>Diagnósticos (últimos 30 días)</p>
            </div>
            <div class="icon"><i class="fas fa-stethoscope"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="small-box bg-success dashboard-small-box">
            <div class="inner">
                <h3><?= esc($formatearEntero($kpisClinicos['planes30'] ?? 0)) ?></h3>
                <p>Planes de cuidado creados (últimos 30 días)</p>
            </div>
            <div class="icon"><i class="fas fa-clipboard-check"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="small-box bg-warning dashboard-small-box">
            <div class="inner">
                <h3><?= esc($formatearEntero($kpisClinicos['actividadesCompletadas'] ?? 0)) ?></h3>
                <p>Actividades completadas (últimos 30 días)</p>
            </div>
            <div class="icon"><i class="fas fa-tasks"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="small-box bg-danger dashboard-small-box">
            <div class="inner">
                <h3><?= esc($formatearEntero($kpisClinicos['planesActivos'] ?? 0)) ?></h3>
                <p>Planes activos</p>
            </div>
            <div class="icon"><i class="fas fa-heartbeat"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-primary chart-card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Distribución de usuarios por rol</h3>
            </div>
            <div class="card-body">
                <?php if ($hayUsuariosChart): ?>
                    <div class="chart-container">
                        <canvas id="chartUsuariosRoles"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Todavía no hay usuarios suficientes para construir este gráfico.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-success chart-card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Planes por estado</h3>
            </div>
            <div class="card-body">
                <?php if ($hayPlanesChart): ?>
                    <div class="chart-container">
                        <canvas id="chartPlanesEstado"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No hay planes registrados o todos carecen de estados definidos.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card card-outline card-info chart-card">
            <div class="card-header">
                <h3 class="card-title mb-0">Comparativa entre médicos (diagnósticos vs planes)</h3>
            </div>
            <div class="card-body">
                <?php if (! $comparativaHayMedicos): ?>
                    <p class="text-muted mb-0">Aún no hay médicos activos registrados en el sistema.</p>
                <?php elseif (! $comparativaHayDatos): ?>
                    <p class="text-muted mb-0">No se registraron diagnósticos ni planes en <?= esc($periodoLabel) ?>.</p>
                <?php else: ?>
                    <div class="chart-container">
                        <canvas id="chartComparativaMedicos"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Resumen de pacientes</h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <p class="text-muted mb-1">Pacientes sin diagnóstico</p>
                        <p class="h3 mb-0"><?= esc($formatearEntero($resumenPacientes['sinDiagnostico'] ?? 0)) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-muted mb-1">Pacientes con plan activo</p>
                        <p class="h3 mb-0"><?= esc($formatearEntero($resumenPacientes['conPlanActivo'] ?? 0)) ?></p>
                    </div>
                </div>
                <p class="text-muted small mb-0 mt-3">Indicadores globales sin exponer información clínica individual.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('adminlte/plugins/chart.js/Chart.min.js') ?>"></script>
<script>
(function () {
    const ChartLib = window.Chart;
    if (!ChartLib) {
        return;
    }

    const version = typeof ChartLib.version === 'string'
        ? parseInt(ChartLib.version.split('.')[0], 10)
        : 2;
    const isV3Plus = version >= 3;

    const doughnutOptions = isV3Plus
        ? {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
        : {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: true,
                position: 'bottom'
            }
        };

    const barOptions = isV3Plus
        ? {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            },
            scales: {
                x: {
                    ticks: {
                        autoSkip: false
                    },
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true
                }
            }
        }
        : {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: true,
                position: 'bottom'
            },
            scales: {
                xAxes: [{
                    ticks: {
                        autoSkip: false
                    },
                    gridLines: {
                        display: false
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        };

    function crearChart(canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            return null;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return null;
        }

        return new ChartLib(ctx, config);
    }

    const usuariosData = <?= $usuariosChartPayload ?>;
    if (usuariosData && Array.isArray(usuariosData.labels) && usuariosData.labels.length > 0) {
        crearChart('chartUsuariosRoles', {
            type: 'doughnut',
            data: {
                labels: usuariosData.labels,
                datasets: [{
                    data: usuariosData.values || [],
                    backgroundColor: ['#3c8dbc', '#00a65a', '#605ca8'],
                    borderWidth: 1
                }]
            },
            options: doughnutOptions
        });
    }

    const planesEstadoData = <?= $planesChartPayload ?>;
    if (planesEstadoData && Array.isArray(planesEstadoData.labels) && planesEstadoData.labels.length > 0) {
        crearChart('chartPlanesEstado', {
            type: 'doughnut',
            data: {
                labels: planesEstadoData.labels,
                datasets: [{
                    data: planesEstadoData.values || [],
                    backgroundColor: ['#17a2b8', '#ffc107', '#dc3545'],
                    borderWidth: 1
                }]
            },
            options: doughnutOptions
        });
    }

    const comparativaData = <?= $comparativaChartPayload ?>;
    if (
        comparativaData &&
        Array.isArray(comparativaData.labels) &&
        comparativaData.labels.length > 0 &&
        document.getElementById('chartComparativaMedicos')
    ) {
        crearChart('chartComparativaMedicos', {
            type: 'bar',
            data: {
                labels: comparativaData.labels,
                datasets: [
                    {
                        label: 'Diagnósticos',
                        backgroundColor: '#007bff',
                        borderColor: '#007bff',
                        borderWidth: 1,
                        data: comparativaData.diagnosticos || []
                    },
                    {
                        label: 'Planes de cuidado',
                        backgroundColor: '#28a745',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        data: comparativaData.planes || []
                    }
                ]
            },
            options: barOptions
        });
    }
})();
</script>
<?= $this->endSection() ?>
