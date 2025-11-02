<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$plan        = $plan ?? [];
$metricas    = $metricas ?? [];
$actividades = $actividades ?? [];

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

$planNombre = trim((string) ($plan['nombre'] ?? ''));
if ($planNombre === '') {
    $planNombre = 'Plan sin nombre';
}

$diagnostico = trim((string) ($plan['diagnostico'] ?? ''));
if ($diagnostico === '') {
    $diagnostico = 'Diagnóstico sin descripción';
}

$descripcion = trim((string) ($plan['descripcion'] ?? ''));

$badgeClass = match ($plan['estado_categoria'] ?? '') {
    'finalizados' => 'badge-success',
    'futuros'     => 'badge-secondary',
    default       => 'badge-info',
};

$fechasVigencia = sprintf(
    '%s → %s',
    $formatearFecha($plan['fecha_inicio'] ?? null),
    $formatearFecha($plan['fecha_fin'] ?? null)
);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
        <h1 class="mb-1"><?= esc($planNombre) ?></h1>
        <p class="text-muted mb-0">
            Diagnóstico: <?= esc($diagnostico) ?>
        </p>
    </div>
    <div class="d-flex align-items-center" style="gap: .5rem;">
        <a href="<?= route_to('paciente_planes_index') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver al listado
        </a>
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
                <h6 class="text-muted text-uppercase mb-1">Vigencia</h6>
                <p class="mb-0 font-weight-bold"><?= esc($fechasVigencia) ?></p>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="text-muted text-uppercase mb-1">Fecha de creación</h6>
                <p class="mb-0"><?= esc($formatearFecha($plan['fecha_creacion'] ?? null, true)) ?></p>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="text-muted text-uppercase mb-1">Estado actual</h6>
                <p class="mb-0"><?= esc($plan['estado_etiqueta'] ?? 'Activo') ?></p>
            </div>
            <div class="col-12">
                <h6 class="text-muted text-uppercase mb-1">Descripción del plan</h6>
                <?php if ($descripcion !== ''): ?>
                    <p class="mb-0"><?= nl2br(esc($descripcion)) ?></p>
                <?php else: ?>
                    <p class="text-muted mb-0">Sin descripción registrada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <?php
    $metricasConfig = [
        ['clave' => 'total', 'label' => 'Total de actividades', 'icono' => 'fa-tasks', 'bg' => 'bg-primary'],
        ['clave' => 'pendientes', 'label' => 'Pendientes', 'icono' => 'fa-list', 'bg' => 'bg-secondary'],
        ['clave' => 'completadas', 'label' => 'Completadas', 'icono' => 'fa-check', 'bg' => 'bg-success'],
        ['clave' => 'vencidas', 'label' => 'Vencidas', 'icono' => 'fa-exclamation-triangle', 'bg' => 'bg-warning'],
        ['clave' => 'validadas', 'label' => 'Validadas', 'icono' => 'fa-user-check', 'bg' => 'bg-teal'],
        ['clave' => 'pendientes_validacion', 'label' => 'Pendientes de validación', 'icono' => 'fa-clipboard-check', 'bg' => 'bg-info'],
    ];
    ?>
    <?php foreach ($metricasConfig as $metrica): ?>
        <div class="col-sm-6 col-lg-4 col-xl-2 mb-3">
            <div class="info-box">
                <span class="info-box-icon <?= esc($metrica['bg']) ?>">
                    <i class="fas <?= esc($metrica['icono']) ?>"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text"><?= esc($metrica['label']) ?></span>
                    <span class="info-box-number" data-metric="<?= esc($metrica['clave']) ?>">
                        <?= esc($metricas[$metrica['clave']] ?? 0) ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="detalle-actividad-alert" class="alert alert-dismissible fade show d-none" role="alert">
    <span id="detalle-actividad-alert-text"></span>
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Actividades del plan</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabla-actividades">
                <thead class="thead-light">
                <tr>
                    <th>Actividad</th>
                    <th>Fechas</th>
                    <th>Estado</th>
                    <th>Validación</th>
                    <th>Comentario del paciente</th>
                    <th class="text-right">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($actividades)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No hay actividades registradas en este plan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($actividades as $actividad): ?>
                        <?php
                        $actividadId = (int) ($actividad['id'] ?? 0);
                        $comentario  = trim((string) ($actividad['paciente_comentario'] ?? ''));
                        $completada  = $actividad['paciente_completada_en'] ?? null;
                        $bloqueo     = $actividad['bloqueo_motivo'] ?? null;

                        $estadoBadge = match ($actividad['estado_slug'] ?? '') {
                            'completada' => 'badge-success',
                            'vencida'    => 'badge-danger',
                            default      => 'badge-secondary',
                        };

                        $validado = ! empty($actividad['validado']);
                        $validadoBadge = $validado ? 'badge-success' : 'badge-warning';
                        $validadoTexto = $validado ? 'Validada' : 'Pendiente';
                        ?>
                        <tr data-actividad-id="<?= esc($actividadId) ?>">
                            <td>
                                <strong><?= esc($actividad['nombre'] ?? 'Actividad sin nombre') ?></strong>
                                <p class="mb-1 text-muted"><?= esc($actividad['descripcion'] ?? '') ?></p>
                                <?php if ($bloqueo): ?>
                                    <div class="text-warning small mb-1" data-role="bloqueo"><?= esc($bloqueo) ?></div>
                                <?php else: ?>
                                    <div class="small text-muted" data-role="bloqueo"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><i class="far fa-calendar-alt mr-1"></i>Inicio: <?= esc($formatearFecha($actividad['fecha_inicio'] ?? null)) ?></div>
                                <div><i class="far fa-calendar-check mr-1"></i>Fin: <?= esc($formatearFecha($actividad['fecha_fin'] ?? null)) ?></div>
                                <?php if ($completada): ?>
                                    <div class="small text-success mt-1" data-role="completada-en">
                                        Marcada el <?= esc($formatearFecha($completada, true)) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="small text-muted mt-1" data-role="completada-en"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= esc($estadoBadge) ?>" data-role="estado">
                                    <?= esc($actividad['estado_nombre'] ?? 'Sin estado') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= esc($validadoBadge) ?>" data-role="validado">
                                    <?= esc($validadoTexto) ?>
                                </span>
                            </td>
                            <td data-role="comentario">
                                <?php if ($comentario !== ''): ?>
                                    <span><?= esc($comentario) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sin comentario</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right" data-role="acciones">
                                <div class="btn-group btn-group-sm" role="group">
                                    <?php if (($actividad['estado_slug'] ?? '') === 'completada'): ?>
                                        <button type="button"
                                                class="btn btn-outline-primary"
                                                data-action="editar"
                                                data-actividad-id="<?= esc($actividadId) ?>"
                                                data-comentario="<?= esc($comentario) ?>">
                                            Editar comentario
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-secondary"
                                                data-action="desmarcar"
                                                data-actividad-id="<?= esc($actividadId) ?>">
                                            Desmarcar
                                        </button>
                                    <?php elseif (! empty($actividad['puede_marcar'])): ?>
                                        <button type="button"
                                                class="btn btn-success"
                                                data-action="marcar"
                                                data-actividad-id="<?= esc($actividadId) ?>">
                                            Marcar como realizada
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Acción no disponible</span>
                                    <?php endif; ?>
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
                              placeholder="Escribí tu comentario sobre la actividad (máximo 1000 caracteres)"></textarea>
                    <small class="text-muted">Podés dejar este campo vacío si no necesitás agregar contexto.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="modal-comentario-confirmar">Guardar</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var tablaActividades = document.getElementById('tabla-actividades');
    if (!tablaActividades) {
        return;
    }

    var modalEl = document.getElementById('modal-comentario');
    var modalComentario = null;
    var comentarioTextarea = document.getElementById('comentario-actividad');
    var botonConfirmar = document.getElementById('modal-comentario-confirmar');
    var actividadObjetivoId = null;

    function ensureModal() {
        if (modalComentario || !window.jQuery) {
            return;
        }
        modalComentario = window.jQuery(modalEl);
    }

    function mostrarModal(actividadId, comentarioInicial) {
        ensureModal();
        actividadObjetivoId = actividadId;
        comentarioTextarea.value = comentarioInicial || '';
        if (modalComentario) {
            modalComentario.modal('show');
        }
    }

    function cerrarModal() {
        if (modalComentario) {
            modalComentario.modal('hide');
        }
        actividadObjetivoId = null;
        comentarioTextarea.value = '';
    }

    function endpointMarcar(actividadId) {
        return '<?= site_url('paciente/planes/actividades') ?>/' + actividadId + '/marcar';
    }

    function endpointDesmarcar(actividadId) {
        return '<?= site_url('paciente/planes/actividades') ?>/' + actividadId + '/desmarcar';
    }

    function escapeHtml(texto) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(texto));
        return div.innerHTML;
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

    function formatearFechaHora(fechaIso) {
        if (!fechaIso) {
            return '';
        }
        var date = new Date(fechaIso.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        return date.toLocaleDateString('es-AR') + ' ' + date.toLocaleTimeString('es-AR', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function badgeEstadoClase(slug) {
        switch (slug) {
            case 'completada':
                return 'badge-success';
            case 'vencida':
                return 'badge-danger';
            default:
                return 'badge-secondary';
        }
    }

    function buildAccionesHtml(actividad) {
        var id = actividad.id;
        var comentario = actividad.paciente_comentario ? escapeHtml(actividad.paciente_comentario) : '';

        if (actividad.estado_slug === 'completada') {
            return '' +
                '<div class="btn-group btn-group-sm" role="group">' +
                '<button type="button" class="btn btn-outline-primary" data-action="editar" data-actividad-id="' + id + '" data-comentario="' + comentario + '">Editar comentario</button>' +
                '<button type="button" class="btn btn-outline-secondary" data-action="desmarcar" data-actividad-id="' + id + '">Desmarcar</button>' +
                '</div>';
        }

        if (actividad.estado_slug === 'vencida') {
            return '<span class="text-muted small">Actividad vencida</span>';
        }

        if (actividad.puede_marcar) {
            return '' +
                '<div class="btn-group btn-group-sm" role="group">' +
                '<button type="button" class="btn btn-success" data-action="marcar" data-actividad-id="' + id + '">Marcar como realizada</button>' +
                '</div>';
        }

        return '<span class="text-muted small">Acción no disponible</span>';
    }

    function renderActividadRow(actividad) {
        var comentario = actividad.paciente_comentario ? escapeHtml(actividad.paciente_comentario) : '';
        var comentarioHtml = comentario !== ''
            ? '<span>' + comentario + '</span>'
            : '<span class="text-muted">Sin comentario</span>';

        var completada = actividad.paciente_completada_en
            ? '<div class="small text-success mt-1" data-role="completada-en">Marcada el ' + escapeHtml(formatearFechaHora(actividad.paciente_completada_en)) + '</div>'
            : '<div class="small text-muted mt-1" data-role="completada-en"></div>';

        var bloqueo = actividad.bloqueo_motivo
            ? '<div class="text-warning small mb-1" data-role="bloqueo">' + escapeHtml(actividad.bloqueo_motivo) + '</div>'
            : '<div class="small text-muted" data-role="bloqueo"></div>';

        var validado = actividad.validado ? 'Validada' : 'Pendiente';
        var validadoBadge = actividad.validado ? 'badge-success' : 'badge-warning';

        return '' +
            '<tr data-actividad-id="' + actividad.id + '">' +
            '<td>' +
                '<strong>' + escapeHtml(actividad.nombre) + '</strong>' +
                '<p class="mb-1 text-muted">' + escapeHtml(actividad.descripcion || '') + '</p>' +
                bloqueo +
            '</td>' +
            '<td>' +
                '<div><i class="far fa-calendar-alt mr-1"></i>Inicio: ' + escapeHtml(formatearFecha(actividad.fecha_inicio)) + '</div>' +
                '<div><i class="far fa-calendar-check mr-1"></i>Fin: ' + escapeHtml(formatearFecha(actividad.fecha_fin)) + '</div>' +
                completada +
            '</td>' +
            '<td>' +
                '<span class="badge ' + badgeEstadoClase(actividad.estado_slug) + '" data-role="estado">' +
                    escapeHtml(actividad.estado_nombre || 'Sin estado') +
                '</span>' +
            '</td>' +
            '<td>' +
                '<span class="badge ' + validadoBadge + '" data-role="validado">' + escapeHtml(validado) + '</span>' +
            '</td>' +
            '<td data-role="comentario">' + comentarioHtml + '</td>' +
            '<td class="text-right" data-role="acciones">' + buildAccionesHtml(actividad) + '</td>' +
            '</tr>';
    }

    function actualizarMetricas(metricas) {
        if (!metricas) {
            return;
        }
        Object.keys(metricas).forEach(function (clave) {
            var elemento = document.querySelector('[data-metric=\"' + clave + '\"]');
            if (elemento) {
                elemento.textContent = metricas[clave];
            }
        });
    }

    function mostrarMensaje(mensaje, tipo) {
        var alertEl = document.getElementById('detalle-actividad-alert');
        var textEl = document.getElementById('detalle-actividad-alert-text');

        if (!alertEl || !textEl) {
            return;
        }

        alertEl.classList.remove('alert-success', 'alert-danger', 'alert-warning');
        alertEl.classList.add('alert-' + (tipo || 'success'));

        textEl.textContent = mensaje;
        alertEl.classList.remove('d-none');
    }

    function ocultarMensaje() {
        var alertEl = document.getElementById('detalle-actividad-alert');
        if (alertEl) {
            alertEl.classList.add('d-none');
        }
    }

    function manejarRespuesta(json) {
        if (!json.success) {
            mostrarMensaje(json.message || 'No se pudo actualizar la actividad.', 'danger');
            return;
        }

        ocultarMensaje();
        mostrarMensaje(json.message || 'Actividad actualizada.', 'success');

        if (!json.data || !json.data.actividad) {
            return;
        }

        actualizarMetricas(json.data.metricas || {});

        var cuerpo = tablaActividades.querySelector('tbody');
        if (!cuerpo) {
            return;
        }

        var filaActual = cuerpo.querySelector('tr[data-actividad-id=\"' + json.data.actividad.id + '\"]');
        if (filaActual) {
            filaActual.outerHTML = renderActividadRow(json.data.actividad);
        }
    }

    function enviarSolicitud(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload || {}),
        }).then(function (response) {
            return response.json().catch(function () {
                return {success: false, message: 'Respuesta inválida del servidor.'};
            });
        }).then(function (json) {
            manejarRespuesta(json);
        }).catch(function () {
            mostrarMensaje('No se pudo contactar al servidor. Inténtalo nuevamente.', 'danger');
        });
    }

    tablaActividades.addEventListener('click', function (event) {
        var accionEl = event.target.closest('[data-action]');
        if (!accionEl) {
            return;
        }

        var accion = accionEl.getAttribute('data-action');
        var actividadId = accionEl.getAttribute('data-actividad-id');

        if (!accion || !actividadId) {
            return;
        }

        if (accion === 'marcar') {
            mostrarModal(actividadId, '');
        } else if (accion === 'editar') {
            var comentarioActual = accionEl.getAttribute('data-comentario') || '';
            mostrarModal(actividadId, comentarioActual);
        } else if (accion === 'desmarcar') {
            enviarSolicitud(endpointDesmarcar(actividadId));
        }
    });

    if (botonConfirmar) {
        botonConfirmar.addEventListener('click', function () {
            if (!actividadObjetivoId) {
                return;
            }
            var comentario = comentarioTextarea.value || null;
            enviarSolicitud(endpointMarcar(actividadObjetivoId), {comentario: comentario});
            cerrarModal();
        });
    }

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            actividadObjetivoId = null;
            comentarioTextarea.value = '';
        });
    }
})();
</script>
<?= $this->endSection() ?>
