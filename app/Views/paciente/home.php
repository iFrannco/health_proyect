<?= $this->extend('layouts/base') ?>

<?= $this->section('styles') ?>
<style>
.dashboard-kpi-col {
    display: flex;
}
.dashboard-kpi-box {
    width: 100%;
    min-height: 130px;
}
.dashboard-kpi-box .info-box-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.dashboard-chart-container {
    position: relative;
    min-height: 260px;
    height: 260px;
    width: 100%;
    overflow: hidden;
}
.dashboard-chart-container canvas {
    width: 100% !important;
    height: 100% !important;
    display: block;
}
.actividad-badge-hoy {
    font-size: .75rem;
    vertical-align: middle;
}
.actividad-descripcion {
    max-width: 420px;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$dashboard  = $dashboard ?? [];
$kpis       = $dashboard['kpis'] ?? [];
$graficos   = $dashboard['graficos'] ?? [];
$proximas   = $dashboard['actividadesProximas']['items'] ?? [];
$avisos     = $dashboard['avisos'] ?? [];
$paciente   = $paciente ?? null;

$nombrePaciente = '';
if ($paciente !== null) {
    $nombrePaciente = trim((string) ($paciente->nombre ?? '') . ' ' . ($paciente->apellido ?? ''));
}
if ($nombrePaciente === '') {
    $nombrePaciente = 'Paciente';
}

$formatEntero = static fn ($valor): string => number_format((float) $valor, 0, ',', '.');

$hayActividades = array_sum($graficos['actividadesDistribucion']['values'] ?? []) > 0;
$planesActivosParaGrafico = (int) ($graficos['progresoPlanes']['planesActivos'] ?? 0);

$formatearFecha = static function (?string $fecha): string {
    if (! $fecha) {
        return '-';
    }

    $timestamp = strtotime($fecha);

    return $timestamp ? date('d/m/Y', $timestamp) : '-';
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-1"><?= esc($title ?? 'Panel del Paciente') ?></h1>
        <p class="text-muted mb-0">Hola <?= esc($nombrePaciente) ?>, acá podés seguir tus diagnósticos, planes y actividades.</p>
    </div>
</div>

<?= view('layouts/partials/alerts') ?>

<div id="dashboard-alert" class="alert alert-dismissible fade show d-none" role="alert">
    <span data-role="alert-text"></span>
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<div id="aviso-hoy" class="alert alert-warning d-none" data-role="aviso-hoy">
    <i class="fas fa-bell mr-2"></i>
    <span data-role="aviso-hoy-text"></span>
</div>

<div class="row">
    <div class="col-sm-6 col-lg-4 col-xl-2 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-info"><i class="fas fa-stethoscope"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Diagnósticos activos</span>
                <span class="info-box-number" data-kpi="diagnosticosActivos">
                    <?= esc($formatEntero($kpis['diagnosticosActivos'] ?? 0)) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-heartbeat"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Planes activos</span>
                <span class="info-box-number" data-kpi="planesActivos">
                    <?= esc($formatEntero($kpis['planesActivos'] ?? 0)) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-success"><i class="fas fa-flag-checkered"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Planes completados</span>
                <span class="info-box-number" data-kpi="planesFinalizados">
                    <?= esc($formatEntero($kpis['planesFinalizados'] ?? 0)) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-teal"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Actividades completadas</span>
                <span class="info-box-number" data-kpi="actividadesCompletadas">
                    <?= esc($formatEntero($kpis['actividadesCompletadas'] ?? 0)) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Actividades pendientes</span>
                <span class="info-box-number" data-kpi="actividadesPendientes">
                    <?= esc($formatEntero($kpis['actividadesPendientes'] ?? 0)) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2 mb-3 dashboard-kpi-col">
        <div class="info-box dashboard-kpi-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Actividades vencidas</span>
                <span class="info-box-number" data-kpi="actividadesVencidas">
                    <?= esc($formatEntero($kpis['actividadesVencidas'] ?? 0)) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-primary h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Distribución de actividades</h3>
            </div>
            <div class="card-body">
                <div class="dashboard-chart-container <?= $hayActividades ? '' : 'd-none' ?>" data-role="distribucion-wrapper">
                    <canvas id="chartActividadesDistribucion"></canvas>
                </div>
                <p class="text-muted mb-0 <?= $hayActividades ? 'd-none' : '' ?>" data-role="distribucion-placeholder">
                    Aún no hay actividades registradas para mostrar.
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-success h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Progreso por plan</h3>
            </div>
            <div class="card-body">
                <div class="dashboard-chart-container <?= $planesActivosParaGrafico > 0 ? '' : 'd-none' ?>" data-role="progreso-wrapper">
                    <canvas id="chartProgresoPlanes"></canvas>
                </div>
                <p class="text-muted mb-0 <?= $planesActivosParaGrafico > 0 ? 'd-none' : '' ?>" data-role="progreso-placeholder">
                    No tenés planes activos en este momento.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card card-outline card-info h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Próximas actividades</h3>
                <small class="text-muted">Hasta 5 actividades ordenadas por fecha de inicio</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0" id="tabla-actividades-proximas">
                        <thead class="thead-light">
                            <tr>
                                <th>Actividad</th>
                                <th>Plan</th>
                                <th style="width: 200px;">Fechas</th>
                                <th style="width: 120px;">Estado</th>
                                <th style="width: 80px;" class="text-center">Completar</th>
                            </tr>
                        </thead>
                        <tbody data-role="actividades-body">
                        <?php if (empty($proximas)): ?>
                            <tr data-placeholder="actividades">
                                <td colspan="5" class="text-center text-muted py-4">No tenés actividades próximas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($proximas as $actividad): ?>
                                <?php
                                $id            = (int) ($actividad['id'] ?? 0);
                                $planNombre    = (string) ($actividad['plan_nombre'] ?? '');
                                $nombre        = (string) ($actividad['nombre'] ?? '');
                                $descripcion   = trim((string) ($actividad['descripcion'] ?? ''));
                                $fechaInicio   = $formatearFecha($actividad['fecha_inicio'] ?? null);
                                $fechaFin      = $formatearFecha($actividad['fecha_fin'] ?? null);
                                $estadoNombre  = (string) ($actividad['estado_nombre'] ?? 'Pendiente');
                                $puedeMarcar   = (bool) ($actividad['puede_marcar'] ?? false);
                                $bloqueo       = (string) ($actividad['bloqueo_motivo'] ?? '');
                                $esHoy         = (bool) ($actividad['es_hoy'] ?? false);
                                $planNombre    = trim($planNombre) === '' ? 'Plan sin nombre' : $planNombre;
                                ?>
                                <tr data-actividad-id="<?= esc($id) ?>">
                                    <td class="align-middle">
                                        <strong><?= esc($nombre) ?></strong>
                                        <?php if ($esHoy): ?>
                                            <span class="badge badge-info actividad-badge-hoy ml-1">Comienza hoy</span>
                                        <?php endif; ?>
                                        <p class="text-muted mb-0 actividad-descripcion">
                                            <?= $descripcion !== '' ? esc($descripcion) : '<span class="text-muted">Sin descripción</span>' ?>
                                        </p>
                                        <?php if ($bloqueo !== ''): ?>
                                            <div class="text-warning small mt-1" data-role="bloqueo"><?= esc($bloqueo) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <i class="fas fa-clipboard-list mr-1 text-muted"></i>
                                        <?= esc($planNombre) ?>
                                    </td>
                                    <td class="align-middle">
                                        <div><i class="far fa-calendar-alt mr-1"></i>Inicio: <?= esc($fechaInicio) ?></div>
                                        <div><i class="far fa-calendar-check mr-1"></i>Fin: <?= esc($fechaFin) ?></div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-secondary" data-role="estado"><?= esc($estadoNombre) ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   id="actividad-checkbox-<?= esc($id) ?>"
                                                   data-role="actividad-checkbox"
                                                   data-actividad-id="<?= esc($id) ?>"
                                                   <?= $puedeMarcar ? '' : 'disabled' ?>
                                            >
                                            <label class="custom-control-label" for="actividad-checkbox-<?= esc($id) ?>"></label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal comentario -->
<div class="modal fade" id="modal-comentario" tabindex="-1" role="dialog" aria-labelledby="modalComentarioLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalComentarioLabel">Agregar comentario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="comentario-actividad">Comentario (opcional)</label>
                    <textarea class="form-control" id="comentario-actividad" rows="4" maxlength="1000"
                              placeholder="Podés dejar algún detalle sobre cómo realizaste la actividad"></textarea>
                    <small class="text-muted">Si no necesitás agregar nada, dejá el campo vacío.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="modal-comentario-confirmar">Marcar como completada</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
window.__PACIENTE_DASHBOARD__ = <?= json_encode($dashboard, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}' ?>;
</script>
<script src="<?= base_url('adminlte/plugins/chart.js/Chart.min.js') ?>"></script>
<script>
(function () {
    var state = {
        data: window.__PACIENTE_DASHBOARD__ || {},
        charts: {
            distribucion: null,
            progreso: null,
        },
        actividadActual: null,
    };

    var elementos = {
        kpis: document.querySelectorAll('[data-kpi]'),
        avisoHoy: document.querySelector('[data-role="aviso-hoy"]'),
        avisoHoyText: document.querySelector('[data-role="aviso-hoy-text"]'),
        distribucionWrapper: document.querySelector('[data-role="distribucion-wrapper"]'),
        distribucionPlaceholder: document.querySelector('[data-role="distribucion-placeholder"]'),
        progresoWrapper: document.querySelector('[data-role="progreso-wrapper"]'),
        progresoPlaceholder: document.querySelector('[data-role="progreso-placeholder"]'),
        actividadesBody: document.querySelector('[data-role="actividades-body"]'),
        alerta: document.getElementById('dashboard-alert'),
        alertaTexto: document.querySelector('#dashboard-alert [data-role="alert-text"]'),
        modal: document.getElementById('modal-comentario'),
        modalConfirmar: document.getElementById('modal-comentario-confirmar'),
        modalTextarea: document.getElementById('comentario-actividad'),
    };

    var chartVersionMajor = (window.Chart && typeof Chart.version === 'string')
        ? parseInt(Chart.version.split('.')[0], 10)
        : 0;
    var isChartV3Plus = chartVersionMajor >= 3;

    function crearChart(canvasId, config) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) {
            return null;
        }

        config.options = config.options || {};
        config.options.maintainAspectRatio = false;

        if (!isChartV3Plus) {
            return new Chart(canvas.getContext('2d'), config);
        }

        var adaptado = Object.assign({}, config);
        adaptado.options = adaptado.options || {};
        adaptado.options.maintainAspectRatio = false;

        if (config.type === 'bar') {
            adaptado.options.scales = adaptado.options.scales || {
                x: {beginAtZero: true},
                y: {beginAtZero: true, suggestedMax: 100},
            };
        }

        if (config.options && config.options.scales && config.options.scales.yAxes) {
            adaptado.options.scales.y = config.options.scales.yAxes[0];
        }

        if (config.options && config.options.scales && config.options.scales.xAxes) {
            adaptado.options.scales.x = config.options.scales.xAxes[0];
        }

        return new Chart(canvas.getContext('2d'), adaptado);
    }

    function mostrarElemento(elemento, visible) {
        if (!elemento) {
            return;
        }
        elemento.classList.toggle('d-none', !visible);
    }

    function escapeHtml(texto) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(texto === undefined ? '' : String(texto)));
        return div.innerHTML;
    }

    function formatEntero(valor) {
        return Number(valor || 0).toLocaleString('es-AR');
    }

    function formatearFecha(fechaIso) {
        if (!fechaIso) {
            return '-';
        }
        var date = new Date(fechaIso.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return '-';
        }
        return date.toLocaleDateString('es-AR');
    }

    function renderKpis() {
        elementos.kpis.forEach(function (elem) {
            var clave = elem.getAttribute('data-kpi');
            if (!clave) {
                return;
            }
            var valor = 0;
            if (state.data && state.data.kpis && Object.prototype.hasOwnProperty.call(state.data.kpis, clave)) {
                valor = state.data.kpis[clave];
            }
            elem.textContent = formatEntero(valor);
        });
    }

    function renderAvisos() {
        if (!elementos.avisoHoy || !elementos.avisoHoyText) {
            return;
        }

        var totalHoy = 0;
        if (state.data.avisos && typeof state.data.avisos.actividadesHoy !== 'undefined') {
            totalHoy = state.data.avisos.actividadesHoy;
        }

        if (totalHoy > 0) {
            var mensaje = totalHoy === 1
                ? 'Hoy tenés una actividad que inicia.'
                : 'Hoy tenés ' + totalHoy + ' actividades que inician.';
            elementos.avisoHoyText.textContent = mensaje;
            elementos.avisoHoy.classList.remove('d-none');
        } else {
            elementos.avisoHoy.classList.add('d-none');
        }
    }

    function renderDistribucion() {
        var data = (state.data.graficos && state.data.graficos.actividadesDistribucion) || null;
        var total = data ? (Number(data.total) || 0) : 0;

        mostrarElemento(elementos.distribucionWrapper, total > 0);
        mostrarElemento(elementos.distribucionPlaceholder, total === 0);

        if (total === 0) {
            if (state.charts.distribucion) {
                state.charts.distribucion.destroy();
                state.charts.distribucion = null;
            }
            return;
        }

        var labels = data.labels || [];
        var values = data.values || [];

        if (!state.charts.distribucion) {
            var doughnutOptions = isChartV3Plus
                ? {cutout: '60%', plugins: {legend: {position: 'bottom'}}}
                : {cutoutPercentage: 70, legend: {position: 'bottom'}};
            doughnutOptions.maintainAspectRatio = false;

            state.charts.distribucion = crearChart('chartActividadesDistribucion', {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
                    }],
                },
                options: doughnutOptions,
            });
            return;
        }

        state.charts.distribucion.data.labels = labels;
        state.charts.distribucion.data.datasets[0].data = values;
        state.charts.distribucion.update();
    }

    function renderProgreso() {
        var data = (state.data.graficos && state.data.graficos.progresoPlanes) || null;
        var cantidad = data ? (Number(data.planesActivos) || 0) : 0;

        mostrarElemento(elementos.progresoWrapper, cantidad > 0);
        mostrarElemento(elementos.progresoPlaceholder, cantidad === 0);

        if (cantidad === 0) {
            if (state.charts.progreso) {
                state.charts.progreso.destroy();
                state.charts.progreso = null;
            }
            return;
        }

        var labels = data.labels || [];
        var values = data.values || [];

        var config = {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Progreso (%)',
                    data: values,
                    backgroundColor: '#17a2b8',
                }],
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            suggestedMax: 100,
                        },
                    }],
                },
            },
        };

        if (!state.charts.progreso) {
            state.charts.progreso = crearChart('chartProgresoPlanes', config);
            return;
        }

        state.charts.progreso.data.labels = labels;
        state.charts.progreso.data.datasets[0].data = values;
        state.charts.progreso.update();
    }

    function renderActividadesProximas() {
        if (!elementos.actividadesBody) {
            return;
        }

        var items = (state.data.actividadesProximas && state.data.actividadesProximas.items) || [];

        if (!Array.isArray(items) || items.length === 0) {
            elementos.actividadesBody.innerHTML = '<tr data-placeholder="actividades"><td colspan="5" class="text-center text-muted py-4">No tenés actividades próximas.</td></tr>';
            return;
        }

        var rows = items.map(function (actividad) {
            var id = actividad.id;
            var nombre = actividad.nombre || '';
            var plan = actividad.plan_nombre || 'Plan sin nombre';
            var descripcion = actividad.descripcion || '';
            var estado = actividad.estado_nombre || 'Pendiente';
            var puedeMarcar = Boolean(actividad.puede_marcar);
            var bloqueo = actividad.bloqueo_motivo ? '<div class="text-warning small mt-1">' + escapeHtml(actividad.bloqueo_motivo) + '</div>' : '';
            var badgeHoy = actividad.es_hoy ? '<span class="badge badge-info actividad-badge-hoy ml-1">Comienza hoy</span>' : '';

            var descripcionHtml = descripcion.trim() !== ''
                ? escapeHtml(descripcion)
                : '<span class="text-muted">Sin descripción</span>';

            return '' +
                '<tr data-actividad-id="' + escapeHtml(id) + '">' +
                    '<td class="align-middle">' +
                        '<strong>' + escapeHtml(nombre) + '</strong>' + badgeHoy +
                        '<p class="text-muted mb-0 actividad-descripcion">' + descripcionHtml + '</p>' +
                        bloqueo +
                    '</td>' +
                    '<td class="align-middle"><i class="fas fa-clipboard-list mr-1 text-muted"></i>' + escapeHtml(plan) + '</td>' +
                    '<td class="align-middle">' +
                        '<div><i class="far fa-calendar-alt mr-1"></i>Inicio: ' + escapeHtml(formatearFecha(actividad.fecha_inicio)) + '</div>' +
                        '<div><i class="far fa-calendar-check mr-1"></i>Fin: ' + escapeHtml(formatearFecha(actividad.fecha_fin)) + '</div>' +
                    '</td>' +
                    '<td class="align-middle"><span class="badge badge-secondary">' + escapeHtml(estado) + '</span></td>' +
                    '<td class="align-middle text-center">' +
                        '<div class="custom-control custom-checkbox">' +
                            '<input type="checkbox" class="custom-control-input" id="actividad-checkbox-' + escapeHtml(id) + '" ' +
                                'data-role="actividad-checkbox" data-actividad-id="' + escapeHtml(id) + '" ' +
                                (puedeMarcar ? '' : 'disabled') + '>' +
                            '<label class="custom-control-label" for="actividad-checkbox-' + escapeHtml(id) + '"></label>' +
                        '</div>' +
                    '</td>' +
                '</tr>';
        }).join('');

        elementos.actividadesBody.innerHTML = rows;
    }

    function renderAll(data) {
        state.data = data || {};
        renderKpis();
        renderAvisos();
        renderDistribucion();
        renderProgreso();
        renderActividadesProximas();
    }

    function mostrarMensaje(texto, tipo) {
        if (!elementos.alerta || !elementos.alertaTexto) {
            return;
        }

        elementos.alerta.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
        elementos.alerta.classList.add('alert-' + (tipo || 'info'));
        elementos.alertaTexto.textContent = texto || '';
    }

    function ocultarMensaje() {
        if (!elementos.alerta) {
            return;
        }
        elementos.alerta.classList.add('d-none');
    }

    function fetchJson(url, options) {
        var requestOptions = Object.assign({
            credentials: 'same-origin',
        }, options || {});

        return fetch(url, requestOptions).then(function (response) {
            return response.json().catch(function () {
                return {success: false, message: 'Respuesta no válida del servidor.'};
            });
        });
    }

    function refrescarDashboard() {
        return fetchJson('<?= route_to('paciente_dashboard_resumen') ?>', {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
        }).then(function (json) {
            if (!json || !json.success) {
                throw new Error((json && json.message) || 'No se pudo actualizar el dashboard.');
            }
            window.__PACIENTE_DASHBOARD__ = json.data || {};
            renderAll(window.__PACIENTE_DASHBOARD__);
        });
    }

    function marcarActividad(actividadId, comentario) {
        return fetchJson('<?= site_url('paciente/planes/actividades') ?>/' + actividadId + '/marcar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({comentario: comentario || null}),
        }).then(function (json) {
            if (!json || !json.success) {
                throw new Error((json && json.message) || 'No se pudo marcar la actividad.');
            }
            mostrarMensaje(json.message || 'Actividad marcada.', 'success');
            return refrescarDashboard().catch(function (error) {
                mostrarMensaje('La actividad se marcó, pero no pudimos actualizar el tablero automáticamente. Actualizá la página para ver los últimos datos.', 'warning');
            });
        }).catch(function (error) {
            mostrarMensaje(error.message || 'No se pudo marcar la actividad.', 'danger');
        });
    }

    var modalInstancia = null;

    function ensureModal() {
        if (!modalInstancia && window.jQuery && elementos.modal) {
            modalInstancia = window.jQuery(elementos.modal);
        }
    }

    function abrirModal(actividadId) {
        ensureModal();
        state.actividadActual = actividadId;
        if (elementos.modalTextarea) {
            elementos.modalTextarea.value = '';
        }
        if (modalInstancia) {
            modalInstancia.modal('show');
        }
    }

    function cerrarModal() {
        if (modalInstancia) {
            modalInstancia.modal('hide');
        }
        state.actividadActual = null;
        if (elementos.modalTextarea) {
            elementos.modalTextarea.value = '';
        }
    }

    if (elementos.modal) {
        elementos.modal.addEventListener('hidden.bs.modal', function () {
            state.actividadActual = null;
            if (elementos.modalTextarea) {
                elementos.modalTextarea.value = '';
            }
        });
    }

    if (elementos.modalConfirmar) {
        elementos.modalConfirmar.addEventListener('click', function () {
            if (!state.actividadActual) {
                return;
            }
            var actividadId = state.actividadActual;
            var comentario = elementos.modalTextarea ? elementos.modalTextarea.value : '';
            marcarActividad(actividadId, comentario);
            cerrarModal();
        });
    }

    if (elementos.actividadesBody) {
        elementos.actividadesBody.addEventListener('change', function (event) {
            var input = event.target;
            if (!input || !input.matches('[data-role="actividad-checkbox"]')) {
                return;
            }
            var actividadId = input.getAttribute('data-actividad-id');
            if (!actividadId) {
                return;
            }

            if (input.checked) {
                input.checked = false;
                abrirModal(actividadId);
            }
        });
    }

    var alertaCloseBtn = document.querySelector('#dashboard-alert .close');
    if (alertaCloseBtn && elementos.alerta) {
        alertaCloseBtn.addEventListener('click', function () {
            elementos.alerta.classList.add('d-none');
        });
    }

    renderAll(state.data);
})();
</script>
<?= $this->endSection() ?>
