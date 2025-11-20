<?= $this->extend('layouts/base') ?>

<?= $this->section('styles') ?>
<style>
    .tabla-validado-col {
        min-width: 240px;
        width: 240px;
    }

    .tabla-validado-col .validado-detalle {
        display: block;
        margin-top: .25rem;
        font-size: .85rem;
        color: #6c757d;
        min-height: 1.6rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tabla-validado-col .validado-detalle--placeholder {
        visibility: hidden;
    }

    .tabla-accion-col {
        width: 190px;
        min-width: 190px;
        text-align: right;
        white-space: nowrap;
    }

    .tabla-accion-col .btn:not(.tabla-comentario-btn) {
        min-width: 120px;
        width: 120px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tabla-accion-grupo {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: .4rem;
    }

    .tabla-comentario-btn {
        width: 36px;
        min-width: 36px;
        height: 32px;
        padding: 0;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .modal-comentario-texto {
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 1rem;
    }

    #tabla-actividades-medico tbody tr td {
        vertical-align: middle;
    }
</style>
<?= $this->endSection() ?>

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
                        <span class="info-box-number" data-resumen="total"><?= esc($resumen['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <?php foreach ($resumen['porEstado'] ?? [] as $estadoResumen): ?>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-flag"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= esc($estadoResumen['nombre'] ?? 'Estado') ?></span>
                            <span class="info-box-number" data-resumen-estado="<?= esc($estadoResumen['slug'] ?? '') ?>"><?= esc($estadoResumen['total'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Validadas</span>
                        <span class="info-box-number" data-resumen="validadas"><?= esc($resumen['validadas'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pendientes de validación</span>
                        <span class="info-box-number" data-resumen="noValidadas"><?= esc($resumen['noValidadas'] ?? 0) ?></span>
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
                        <table class="table table-striped table-hover mb-0" id="tabla-actividades-medico">
                            <thead>
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col" class="text-nowrap">Inicio</th>
                                    <th scope="col" class="text-nowrap">Fin</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="text-nowrap tabla-validado-col">Validado</th>
                                    <th scope="col" class="text-nowrap tabla-accion-col">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actividades as $actividad): ?>
                                    <?php
                                    $descripcion = trim((string) ($actividad['descripcion'] ?? ''));
                                    if (mb_strlen($descripcion) > 140) {
                                        $descripcion = mb_substr($descripcion, 0, 137) . '...';
                                    }

                                    $actividadId        = (int) ($actividad['id'] ?? 0);
                                    $estadoSlug         = (string) ($actividad['estado_slug'] ?? '');
                                    $estadoNombre       = $actividad['estado_nombre'] ?? 'Estado sin nombre';
                                    $estadoBadge        = 'badge-secondary';

                                    switch ($estadoSlug) {
                                        case 'completada':
                                            $estadoBadge = 'badge-success';
                                            break;
                                        case 'vencida':
                                            $estadoBadge = 'badge-danger';
                                            break;
                                    }

                                    $validadoValor      = $actividad['validado'] ?? null;
                                    $validadoPendiente  = $validadoValor === null;
                                    $estaValidado       = filter_var($validadoValor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
                                    $fechaValidacion    = $actividad['fecha_validacion'] ?? null;
                                    $puedeValidar       = $estadoSlug === 'completada' && ! $estaValidado;
                                    $puedeDesvalidar    = $estaValidado || $estadoSlug !== 'completada';
                                    $comentarioPaciente = trim((string) ($actividad['paciente_comentario'] ?? ''));
                                    $tieneComentario    = $comentarioPaciente !== '';
                                    $comentarioBtnClass = $tieneComentario ? 'btn-outline-info' : 'btn-outline-secondary';
                                    ?>
                                    <tr data-actividad-id="<?= esc((string) $actividadId) ?>"
                                        data-estado="<?= esc($estadoSlug) ?>"
                                        data-validado="<?= $estaValidado ? '1' : '0' ?>"
                                        data-fecha-validacion="<?= esc($fechaValidacion ?? '') ?>"
                                        data-comentario="<?= esc($comentarioPaciente, 'attr') ?>">
                                        <td><?= esc($actividad['nombre'] ?? 'Actividad') ?></td>
                                        <td><?= esc($descripcion) ?></td>
                                        <td class="text-nowrap"><?= esc($formatearFecha($actividad['fecha_inicio'] ?? null)) ?></td>
                                        <td class="text-nowrap"><?= esc($formatearFecha($actividad['fecha_fin'] ?? null)) ?></td>
                                        <td>
                                            <span class="badge <?= esc($estadoBadge) ?>"><?= esc($estadoNombre) ?></span>
                                        </td>
                                        <td data-role="validado" class="tabla-validado-col">
                                            <?php if ($estaValidado): ?>
                                                <span class="badge badge-success">Validada</span>
                                                <?php if ($fechaValidacion): ?>
                                                    <div class="validado-detalle">
                                                        Validado el <?= esc($formatearFecha($fechaValidacion, true)) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="validado-detalle validado-detalle--placeholder">Validado el 00/00/0000 00:00</div>
                                                <?php endif; ?>
                                            <?php elseif ($validadoPendiente): ?>
                                                <span class="badge badge-warning">Pendiente</span>
                                                <div class="validado-detalle validado-detalle--placeholder">Validado el 00/00/0000 00:00</div>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">No</span>
                                                <div class="validado-detalle validado-detalle--placeholder">Validado el 00/00/0000 00:00</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-nowrap tabla-accion-col" data-role="accion">
                                            <div class="tabla-accion-grupo">
                                                <button type="button"
                                                    class="btn btn-sm tabla-comentario-btn <?= esc($comentarioBtnClass) ?>"
                                                    data-action="mostrar-comentario"
                                                    data-actividad-id="<?= esc((string) $actividadId) ?>"
                                                    data-comentario="<?= esc($comentarioPaciente, 'attr') ?>"
                                                    title="<?= esc($tieneComentario ? 'Ver comentario del paciente' : 'Sin comentario del paciente') ?>"
                                                    aria-label="<?= esc($tieneComentario ? 'Ver comentario del paciente' : 'Sin comentario del paciente') ?>"
                                                    <?= $tieneComentario ? '' : 'disabled' ?>>
                                                    <i class="far fa-comment-dots"></i>
                                                </button>
                                                <?php if ($puedeValidar): ?>
                                                    <button type="button"
                                                        class="btn btn-success btn-sm"
                                                        data-action="validar"
                                                        data-actividad-id="<?= esc((string) $actividadId) ?>">
                                                        Validar
                                                    </button>
                                                <?php elseif ($puedeDesvalidar): ?>
                                                    <button type="button"
                                                        class="btn btn-outline-warning btn-sm"
                                                        data-action="desvalidar"
                                                        data-actividad-id="<?= esc((string) $actividadId) ?>">
                                                        Desvalidar
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled>Validar</button>
                                                <?php endif; ?>
                                            </div>
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
<div class="modal fade" id="modal-ver-comentario" tabindex="-1" role="dialog" aria-labelledby="modal-ver-comentario-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-ver-comentario-label">
                    <i class="far fa-comment-dots mr-2"></i> Comentario del paciente
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2">Mensaje registrado para esta actividad.</p>
                <div id="modal-comentario-contenido" class="modal-comentario-texto d-none"></div>
                <p id="modal-comentario-placeholder" class="text-muted mb-0">El paciente no dejó un comentario en esta actividad.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        var tabla = document.getElementById('tabla-actividades-medico');
        if (!tabla) {
            return;
        }

        var modalComentarioJQ = window.jQuery ? window.jQuery('#modal-ver-comentario') : null;
        var modalComentarioContenido = document.getElementById('modal-comentario-contenido');
        var modalComentarioPlaceholder = document.getElementById('modal-comentario-placeholder');

        var validarBaseUrl = '<?= site_url('medico/planes/actividades') ?>';

        function endpointValidar(id) {
            return validarBaseUrl + '/' + id + '/validar';
        }

        function endpointDesvalidar(id) {
            return validarBaseUrl + '/' + id + '/desvalidar';
        }

        function escapeHtml(texto) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(texto == null ? '' : String(texto)));
            return div.innerHTML;
        }

        function formatearFechaHora(fechaIso) {
            if (!fechaIso) {
                return '';
            }
            var normalizada = String(fechaIso).replace(' ', 'T');
            var date = new Date(normalizada);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            return date.toLocaleDateString('es-AR') + ' ' + date.toLocaleTimeString('es-AR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function abrirModalComentario(texto) {
            var contenido = (texto || '').trim();
            var tieneContenido = contenido !== '';

            if (modalComentarioContenido) {
                modalComentarioContenido.textContent = tieneContenido ? contenido : '';
                modalComentarioContenido.classList.toggle('d-none', !tieneContenido);
            }

            if (modalComentarioPlaceholder) {
                modalComentarioPlaceholder.classList.toggle('d-none', tieneContenido);
            }

            if (modalComentarioJQ && typeof modalComentarioJQ.modal === 'function') {
                modalComentarioJQ.modal('show');
                return;
            }

            var mensajeFallback = tieneContenido ? contenido : 'El paciente no dejó un comentario en esta actividad.';
            window.alert(mensajeFallback);
        }

        function mostrarToast(mensaje, tipo) {
            var clase = 'bg-success';
            if (tipo === 'warning') {
                clase = 'bg-warning';
            } else if (tipo === 'danger') {
                clase = 'bg-danger';
            }

            if (window.jQuery && typeof window.jQuery(document).Toasts === 'function') {
                window.jQuery(document).Toasts('create', {
                    class: clase,
                    title: 'Validación',
                    body: mensaje,
                    autohide: true,
                    delay: 4000,
                });
                return;
            }

            console.log(mensaje);
        }

        function buildValidadoHtml(actividad) {
            var plantillaDetalle = '<div class="validado-detalle validado-detalle--placeholder">Validado el 00/00/0000 00:00</div>';

            if (actividad.validado === true) {
                var detalle = actividad.fecha_validacion ? formatearFechaHora(actividad.fecha_validacion) : '';
                var cuerpoDetalle = detalle !== '' ?
                    '<div class="validado-detalle">Validado el ' + escapeHtml(detalle) + '</div>' :
                    plantillaDetalle;

                return '<span class="badge badge-success">Validada</span>' + cuerpoDetalle;
            }

            if (actividad.validado === null) {
                return '<span class="badge badge-warning">Pendiente</span>' + plantillaDetalle;
            }

            return '<span class="badge badge-secondary">No</span>' + plantillaDetalle;
        }

        function buildAccionHtml(actividad) {
            var idTexto = actividad.id != null ? String(actividad.id) : '';
            var idSeguro = escapeHtml(idTexto);
            var estado = actividad.estado_slug || '';
            var validado = actividad.validado === true;
            var comentarioBtn = buildBotonComentarioHtml(actividad, idSeguro);
            var principalBtn;

            if (estado === 'completada' && !validado) {
                principalBtn = '<button type="button" class="btn btn-success btn-sm" data-action="validar" data-actividad-id="' + idSeguro + '">Validar</button>';
            } else if (validado || estado !== 'completada') {
                principalBtn = '<button type="button" class="btn btn-outline-warning btn-sm" data-action="desvalidar" data-actividad-id="' + idSeguro + '">Desvalidar</button>';
            } else {
                principalBtn = '<button type="button" class="btn btn-outline-secondary btn-sm" disabled>Validar</button>';
            }

            return '<div class="tabla-accion-grupo">' + comentarioBtn + principalBtn + '</div>';
        }

        function buildBotonComentarioHtml(actividad, idSeguro) {
            var comentario = actividad.paciente_comentario ? escapeHtml(actividad.paciente_comentario) : '';
            var tieneComentario = comentario !== '';
            var clases = 'btn btn-sm tabla-comentario-btn ' + (tieneComentario ? 'btn-outline-info' : 'btn-outline-secondary');
            var titulo = tieneComentario ? 'Ver comentario del paciente' : 'Sin comentario del paciente';
            var disabledAttr = tieneComentario ? '' : ' disabled';

            return '<button type="button" class="' + clases + '" data-action="mostrar-comentario" data-actividad-id="' + idSeguro + '" data-comentario="' + comentario + '" title="' + escapeHtml(titulo) + '" aria-label="' + escapeHtml(titulo) + '"' + disabledAttr + '><i class="far fa-comment-dots"></i></button>';
        }

        function actualizarResumen(resumen) {
            if (!resumen) {
                return;
            }

            var totales = {
                total: resumen.total,
                validadas: resumen.validadas,
                noValidadas: resumen.noValidadas
            };

            Object.keys(totales).forEach(function(clave) {
                if (typeof totales[clave] === 'undefined') {
                    return;
                }
                var elemento = document.querySelector('[data-resumen=\"' + clave + '\"]');
                if (elemento) {
                    elemento.textContent = totales[clave];
                }
            });

            if (Array.isArray(resumen.porEstado)) {
                resumen.porEstado.forEach(function(estado) {
                    if (!estado || !estado.slug) {
                        return;
                    }
                    var elemento = document.querySelector('[data-resumen-estado=\"' + estado.slug + '\"]');
                    if (elemento) {
                        elemento.textContent = estado.total;
                    }
                });
            }
        }

        function actualizarFila(actividad) {
            var idTexto = actividad.id != null ? String(actividad.id) : '';
            var fila = tabla.querySelector('tbody tr[data-actividad-id=\"' + idTexto + '\"]');
            if (!fila) {
                return;
            }

            fila.setAttribute('data-estado', actividad.estado_slug || '');
            fila.setAttribute('data-validado', actividad.validado === true ? '1' : '0');
            fila.setAttribute('data-fecha-validacion', actividad.fecha_validacion || '');
            fila.setAttribute('data-comentario', actividad.paciente_comentario || '');

            var celdaValidado = fila.querySelector('[data-role=\"validado\"]');
            if (celdaValidado) {
                celdaValidado.innerHTML = buildValidadoHtml(actividad);
            }

            var celdaAccion = fila.querySelector('[data-role=\"accion\"]');
            if (celdaAccion) {
                celdaAccion.innerHTML = buildAccionHtml(actividad);
            }
        }

        function manejarRespuesta(json, accion) {
            if (json && json.data && json.data.actividad) {
                actualizarFila(json.data.actividad);
            }

            if (json && json.data && json.data.resumen) {
                actualizarResumen(json.data.resumen);
            }

            if (!json) {
                mostrarToast('Respuesta inválida del servidor.', 'danger');
                return;
            }

            var estado = json.status || '';
            var warningStatuses = ['already_validated', 'already_unvalidated'];
            var mensajeExito = accion === 'desvalidar' ?
                'Validación revertida.' :
                'Actividad validada.';
            var mensajeError = accion === 'desvalidar' ?
                'No se pudo desvalidar la actividad.' :
                'No se pudo validar la actividad.';

            if (json.success) {
                var tipo = warningStatuses.indexOf(estado) !== -1 ? 'warning' : 'success';
                mostrarToast(json.message || mensajeExito, tipo);
            } else {
                var tipoError = warningStatuses.indexOf(estado) !== -1 ? 'warning' : 'danger';
                mostrarToast(json.message || mensajeError, tipoError);
            }
        }

        function enviarSolicitud(accion, actividadId) {
            var endpoint = accion === 'desvalidar' ?
                endpointDesvalidar(actividadId) :
                endpointValidar(actividadId);

            return fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({})
                })
                .then(function(response) {
                    return response.json().catch(function() {
                        return {
                            success: false,
                            message: 'Respuesta inválida del servidor.'
                        };
                    });
                })
                .then(function(json) {
                    manejarRespuesta(json, accion);
                    return json;
                })
                .catch(function() {
                    var mensaje = accion === 'desvalidar' ?
                        'No se pudo contactar al servidor. Inténtalo nuevamente.' :
                        'No se pudo contactar al servidor. Inténtalo nuevamente.';
                    mostrarToast(mensaje, 'danger');
                    throw new Error('network');
                });
        }

        tabla.addEventListener('click', function(event) {
            var boton = event.target.closest('[data-action]');
            if (!boton || boton.disabled) {
                return;
            }

            var accion = boton.getAttribute('data-action');
            if (!accion) {
                return;
            }

            if (accion === 'mostrar-comentario') {
                var comentario = boton.getAttribute('data-comentario') || '';
                abrirModalComentario(comentario);
                return;
            }

            var actividadId = boton.getAttribute('data-actividad-id');
            if (!actividadId) {
                return;
            }

            if (accion !== 'validar' && accion !== 'desvalidar') {
                return;
            }

            var contenidoOriginal = boton.innerHTML;
            var textoProceso = accion === 'desvalidar' ? 'Desvalidando...' : 'Validando...';

            boton.disabled = true;
            boton.setAttribute('data-loading', 'true');
            boton.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>' + escapeHtml(textoProceso);

            enviarSolicitud(accion, actividadId).then(function(json) {
                if (!json || !json.data || !json.data.actividad) {
                    boton.disabled = false;
                    boton.innerHTML = contenidoOriginal;
                    boton.removeAttribute('data-loading');
                }
            }).catch(function() {
                boton.disabled = false;
                boton.innerHTML = contenidoOriginal;
                boton.removeAttribute('data-loading');
            });
        });
    })();
</script>
<script>
    (function() {
        const formEliminar = document.getElementById('form-eliminar-plan');
        const modalElement = $('#modal-confirmar-eliminar');
        const botonConfirmar = document.getElementById('btn-confirmar-eliminar');

        if (!formEliminar || modalElement.length === 0 || !botonConfirmar) {
            return;
        }

        let submitPendiente = false;

        formEliminar.addEventListener('submit', function(event) {
            if (submitPendiente) {
                submitPendiente = false;
                return;
            }

            event.preventDefault();
            modalElement.modal('show');
        });

        botonConfirmar.addEventListener('click', function() {
            submitPendiente = true;
            modalElement.modal('hide');
            formEliminar.submit();
        });
    })();
</script>
<?= $this->endSection() ?>
