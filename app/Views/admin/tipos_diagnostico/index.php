<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$tipos       = $tipos ?? [];
$busqueda    = $busqueda ?? '';
$pager       = $pager ?? null;
$pagerGroup  = $pagerGroup ?? 'admin_tipos_diagnostico';
$formErrors  = is_array($formErrors ?? null) ? $formErrors : [];
$formMode    = (string) ($formMode ?? '');
$formEditId  = $formEditId ?? null;
$shouldOpenModal = in_array($formMode, ['create', 'edit'], true);
$modalInitialMode = $shouldOpenModal ? $formMode : '';
$modalInitialUpdateUrl = '';
if ($shouldOpenModal && $formMode === 'edit' && $formEditId !== null) {
    $modalInitialUpdateUrl = route_to('admin_tipos_diagnostico_update', $formEditId);
}
$modalOldNombre      = $shouldOpenModal ? (string) old('nombre') : '';
$modalOldDescripcion = $shouldOpenModal ? (string) old('descripcion') : '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Tipos de diagnóstico</h1>
                <p class="text-muted mb-0">
                    Gestiona el catálogo utilizado por los médicos para clasificar sus diagnósticos.
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <button
                    type="button"
                    class="btn btn-success"
                    data-action="nuevo-tipo"
                    data-toggle="modal"
                    data-target="#modalTipoDiagnostico"
                >
                    <i class="fas fa-plus mr-1"></i> Nuevo tipo
                </button>
            </div>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <div class="card card-outline card-primary mb-4">
            <div class="card-body">
                <form method="get" action="<?= site_url('admin/tipos-diagnostico') ?>" class="form-row align-items-end">
                    <div class="col-12 col-sm-8 col-md-6 col-lg-5">
                        <label for="busqueda" class="d-block text-muted mb-1">Buscar por nombre</label>
                        <input
                            type="text"
                            id="busqueda"
                            name="q"
                            value="<?= esc($busqueda) ?>"
                            class="form-control"
                            placeholder="Ej: Cardiología"
                            autocomplete="off"
                        >
                    </div>
                    <div class="col-12 col-sm-auto d-flex align-items-end mt-3 mt-sm-0">
                        <button type="submit" class="btn btn-primary mr-sm-2">
                            <i class="fas fa-search mr-1"></i> Buscar
                        </button>
                        <?php if ($busqueda !== ''): ?>
                            <a href="<?= site_url('admin/tipos-diagnostico') ?>" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($tipos)): ?>
            <div class="card card-outline card-secondary">
                <div class="card-body text-center text-muted">
                    <?php if ($busqueda === ''): ?>
                        No hay tipos de diagnóstico registrados.
                    <?php else: ?>
                        No se encontraron tipos que coincidan con la búsqueda ingresada.
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php $resultadosPagina = count($tipos); ?>
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Listado de tipos
                        <small class="text-muted font-weight-normal ml-2">
                            <?= $resultadosPagina ?> resultado<?= $resultadosPagina === 1 ? '' : 's' ?> en esta página
                        </small>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">Nombre</th>
                                    <th scope="col" class="text-nowrap">Descripción</th>
                                    <th scope="col" class="text-nowrap">Estado</th>
                                    <th scope="col" class="text-nowrap text-center">Usado en</th>
                                    <th scope="col" class="text-center text-nowrap">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tipos as $tipo): ?>
                                    <?php
                                    $tipoId       = (int) ($tipo['id'] ?? 0);
                                    $nombre       = trim((string) ($tipo['nombre'] ?? '')); 
                                    $descripcion  = trim((string) ($tipo['descripcion'] ?? ''));
                                    $estaActivo   = (bool) ($tipo['activo'] ?? false);
                                    $totalUsos    = (int) ($tipo['total_usos'] ?? 0);
                                    $descripcionBreve = $descripcion;
                                    if ($descripcionBreve !== '') {
                                        if (function_exists('mb_strlen')) {
                                            if (mb_strlen($descripcionBreve) > 140) {
                                                $descripcionBreve = mb_substr($descripcionBreve, 0, 140) . '...';
                                            }
                                        } elseif (strlen($descripcionBreve) > 140) {
                                            $descripcionBreve = substr($descripcionBreve, 0, 140) . '...';
                                        }
                                    }
                                    $updateUrl = route_to('admin_tipos_diagnostico_update', $tipoId);
                                    $toggleUrl = route_to('admin_tipos_diagnostico_toggle', $tipoId);
                                    $toggleAccion = $estaActivo ? 'desactivar' : 'activar';
                                    $toggleLabel  = $estaActivo ? 'Desactivar' : 'Reactivar';
                                    $toggleIcon   = $estaActivo ? 'fas fa-lock' : 'fas fa-unlock';
                                    $toggleConfirm = $estaActivo
                                        ? '¿Seguro que deseas desactivar este tipo?'
                                        : '¿Seguro que deseas reactivar este tipo?';
                                    ?>
                                    <tr>
                                    <td><?= esc($nombre) ?></td>
                                    <td>
                                        <?php if ($descripcionBreve === ''): ?>
                                            <span class="text-muted">Sin descripción</span>
                                        <?php else: ?>
                                            <?= esc($descripcionBreve) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $estaActivo ? 'badge-success' : 'badge-secondary' ?>">
                                            <?= $estaActivo ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">
                                            <?= $totalUsos ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary mr-2"
                                                data-action="editar-tipo"
                                                data-toggle="modal"
                                                data-target="#modalTipoDiagnostico"
                                                data-update-url="<?= esc($updateUrl, 'attr') ?>"
                                                data-nombre="<?= esc($nombre, 'attr') ?>"
                                                data-descripcion="<?= esc($descripcion, 'attr') ?>"
                                            >
                                                <i class="fas fa-edit mr-1"></i> Editar
                                            </button>
                                            <form
                                                action="<?= esc($toggleUrl) ?>"
                                                method="post"
                                                class="d-inline"
                                                onsubmit="return confirm('<?= esc($toggleConfirm, 'attr') ?>');"
                                            >
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="accion" value="<?= esc($toggleAccion) ?>">
                                                <button type="submit" class="btn btn-sm <?= $estaActivo ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                    <i class="<?= esc($toggleIcon) ?> mr-1"></i> <?= $toggleLabel ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($pager !== null && $pager->getPageCount($pagerGroup) > 1): ?>
                    <?php
                    $paginaActual = $pager->getCurrentPage($pagerGroup);
                    $totalPaginas = $pager->getPageCount($pagerGroup);
                    $urlAnterior  = $pager->getPreviousPageURI($pagerGroup);
                    $urlSiguiente = $pager->getNextPageURI($pagerGroup);
                    $hayAnterior  = $urlAnterior !== null && $urlAnterior !== '';
                    $haySiguiente = $urlSiguiente !== null && $urlSiguiente !== '';
                    ?>
                    <div class="card-footer bg-white">
                        <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center">
                            <a
                                href="<?= $hayAnterior ? esc($urlAnterior) : '#' ?>"
                                class="btn btn-outline-secondary mb-2 mb-sm-0 mr-sm-3<?= $hayAnterior ? '' : ' disabled' ?>"
                                <?= $hayAnterior ? '' : 'tabindex="-1" aria-disabled="true"' ?>
                            >
                                <i class="fas fa-chevron-left mr-1"></i> Anterior
                            </a>
                            <span class="text-muted mb-2 mb-sm-0">
                                Página <?= $paginaActual ?><?= $totalPaginas !== null ? ' de ' . $totalPaginas : '' ?>
                            </span>
                            <a
                                href="<?= $haySiguiente ? esc($urlSiguiente) : '#' ?>"
                                class="btn btn-outline-secondary mt-2 mt-sm-0 ml-sm-3<?= $haySiguiente ? '' : ' disabled' ?>"
                                <?= $haySiguiente ? '' : 'tabindex="-1" aria-disabled="true"' ?>
                            >
                                Siguiente <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div
    class="modal fade"
    id="modalTipoDiagnostico"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalTipoDiagnosticoLabel"
    aria-hidden="true"
    data-initial-mode="<?= esc($modalInitialMode, 'attr') ?>"
    data-initial-edit-id="<?= $shouldOpenModal && $formEditId !== null ? esc((string) $formEditId, 'attr') : '' ?>"
    data-initial-update-url="<?= esc($modalInitialUpdateUrl, 'attr') ?>"
    data-old-nombre="<?= esc($modalOldNombre, 'attr') ?>"
    data-old-descripcion="<?= esc($modalOldDescripcion, 'attr') ?>"
>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form
                method="post"
                action="<?= esc(route_to('admin_tipos_diagnostico_store')) ?>"
                data-create-action="<?= esc(route_to('admin_tipos_diagnostico_store'), 'attr') ?>"
            >
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTipoDiagnosticoLabel" data-modal-title>Nuevo tipo de diagnóstico</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (isset($formErrors['general'])): ?>
                        <div class="alert alert-danger" data-validation-error="general">
                            <?= esc($formErrors['general']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="tipo-nombre">Nombre</label>
                        <input
                            type="text"
                            id="tipo-nombre"
                            name="nombre"
                            class="form-control<?= isset($formErrors['nombre']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('nombre')) ?>"
                            minlength="2"
                            maxlength="120"
                            required
                        >
                        <?php if (isset($formErrors['nombre'])): ?>
                            <span class="invalid-feedback d-block" data-validation-error="nombre">
                                <?= esc($formErrors['nombre']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="tipo-descripcion">Descripción (opcional)</label>
                        <textarea
                            id="tipo-descripcion"
                            name="descripcion"
                            class="form-control<?= isset($formErrors['descripcion']) ? ' is-invalid' : '' ?>"
                            rows="4"
                            maxlength="500"
                        ><?= esc(old('descripcion')) ?></textarea>
                        <?php if (isset($formErrors['descripcion'])): ?>
                            <span class="invalid-feedback d-block" data-validation-error="descripcion">
                                <?= esc($formErrors['descripcion']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" data-submit-label>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function ($) {
    if (! $) {
        return;
    }

    var $modal = $('#modalTipoDiagnostico');
    if (! $modal.length) {
        return;
    }

    var $form = $modal.find('form');
    var createAction = $form.attr('data-create-action') || $form.attr('action');
    var $nombre = $form.find('input[name="nombre"]');
    var $descripcion = $form.find('textarea[name="descripcion"]');
    var $modalTitle = $modal.find('[data-modal-title]');
    var $submitButton = $modal.find('[data-submit-label]');
    var initialMode = ($modal.attr('data-initial-mode') || '').toString();
    var initialUpdateUrl = $modal.attr('data-initial-update-url') || '';
    var initialNombre = $modal.attr('data-old-nombre') || '';
    var initialDescripcion = $modal.attr('data-old-descripcion') || '';

    function limpiarErrores() {
        $form.find('.is-invalid').removeClass('is-invalid');
        $modal.find('[data-validation-error]').remove();
    }

    function prepararModal(modo, payload, limpiar) {
        if (limpiar !== false) {
            limpiarErrores();
        }

        if (modo === 'edit') {
            $form.attr('action', payload.updateUrl || createAction);
            $modalTitle.text('Editar tipo de diagnóstico');
            $submitButton.text('Actualizar');
        } else {
            $form.attr('action', createAction);
            $modalTitle.text('Nuevo tipo de diagnóstico');
            $submitButton.text('Guardar');
        }

        $nombre.val(payload.nombre || '');
        $descripcion.val(payload.descripcion || '');
    }

    $('[data-action="nuevo-tipo"]').on('click', function () {
        prepararModal('create', { nombre: '', descripcion: '' });
        $modal.modal('show');
    });

    $('[data-action="editar-tipo"]').on('click', function () {
        var $btn = $(this);
        prepararModal('edit', {
            nombre: $btn.data('nombre') || '',
            descripcion: $btn.data('descripcion') || '',
            updateUrl: $btn.data('update-url') || createAction
        });
        $modal.modal('show');
    });

    if (initialMode === 'create' || initialMode === 'edit') {
        prepararModal(initialMode, {
            nombre: initialNombre,
            descripcion: initialDescripcion,
            updateUrl: initialMode === 'edit' ? (initialUpdateUrl || createAction) : createAction
        }, false);
        $modal.modal('show');
    }
})(window.jQuery);
</script>
<?= $this->endSection() ?>
