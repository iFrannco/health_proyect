<?= $this->extend('layouts/base') ?>

<?= $this->section('styles') ?>
<style>
.dropzone-upload {
    border: 2px dashed #1a73e8;
    background: #e7f3ff;
    border-radius: .5rem;
    padding: 1.25rem;
    text-align: center;
    color: #1a73e8;
    cursor: pointer;
    transition: background-color .2s ease, border-color .2s ease;
}
.dropzone-upload:hover,
.dropzone-upload:focus,
.dropzone-upload.dragover {
    background: #d6e9ff;
    border-color: #0f5ab8;
}
.dropzone-upload .dz-icon {
    font-size: 1.4rem;
    display: block;
    margin-bottom: .35rem;
}
.dropzone-upload small {
    color: #4a4a4a;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
/** @var array<int, array<string,mixed>> $documentos */
$usuario       = $usuario ?? null;
$tipos         = $tipos ?? [];
$tipoActual    = trim((string) ($tipoActual ?? ''));
$errorsDatos   = $errorsDatos ?? [];
$nombreUsuario = '';
if ($usuario !== null) {
    $nombreUsuario = trim((string) ($usuario->nombre ?? '') . ' ' . ((string) ($usuario->apellido ?? '')));
}
$errorDato = static function (array $errors, string $campo): ?string {
    return $errors[$campo] ?? null;
};
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-1">Historial médico</h1>
        <p class="text-muted mb-0">Documentación de <?= esc($nombreUsuario ?: 'paciente') ?>.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNuevoDocumento">
            <i class="fas fa-upload mr-1"></i> Agregar documento
        </button>
    </div>
</div>

<?= view('layouts/partials/alerts') ?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <form class="form-inline">
            <label class="mr-2 mb-0">Filtrar por tipo:</label>
            <?php foreach ($tipos as $slug => $label): ?>
                <?php $checked = $tipoActual === $slug; ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tipo" id="filtro_<?= esc($slug) ?>" value="<?= esc($slug) ?>" <?= $checked ? 'checked' : '' ?> onchange="this.form.submit()">
                    <label class="form-check-label" for="filtro_<?= esc($slug) ?>"><?= esc($label) ?></label>
                </div>
            <?php endforeach; ?>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tipo" id="filtro_todos" value="" <?= $tipoActual === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="form-check-label" for="filtro_todos">Todos</label>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th style="width: 60px;">Tipo</th>
                    <th>Nombre</th>
                    <th style="width: 140px;">Fecha</th>
                    <th style="width: 140px;">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($documentos)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No hay documentos cargados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentos as $doc): ?>
                        <tr>
                            <td>
                                <span class="badge badge-secondary text-uppercase"><?= esc($doc['tipo'] ?? '') ?></span>
                            </td>
                            <td><?= esc($doc['nombre'] ?? '') ?></td>
                            <td><?= esc($doc['fecha_documento'] ?? '') ?></td>
                            <td class="text-right">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= route_to('paciente_documentacion_descargar', $doc['id']) ?>">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#modalEditarDocumento<?= (int) $doc['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="<?= route_to('paciente_documentacion_delete', $doc['id']) ?>" method="post" class="d-inline form-delete-doc">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-open-delete" data-form-id="form-delete-<?= (int) $doc['id'] ?>" data-action="<?= route_to('paciente_documentacion_delete', $doc['id']) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEditarDocumento<?= (int) $doc['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalEditarDocumentoLabel<?= (int) $doc['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalEditarDocumentoLabel<?= (int) $doc['id'] ?>">Editar documento</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <form action="<?= route_to('paciente_documentacion_update', $doc['id']) ?>" method="post" novalidate>
                                        <?= csrf_field() ?>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="nombre_<?= (int) $doc['id'] ?>">Nombre *</label>
                                                <input type="text" id="nombre_<?= (int) $doc['id'] ?>" name="nombre" class="form-control" value="<?= esc($doc['nombre'] ?? '') ?>" required maxlength="180">
                                            </div>
                                            <div class="form-group">
                                                <label for="tipo_<?= (int) $doc['id'] ?>">Tipo *</label>
                                                <select id="tipo_<?= (int) $doc['id'] ?>" name="tipo" class="form-control" required>
                                                    <?php foreach ($tipos as $slug => $label): ?>
                                                        <option value="<?= esc($slug) ?>" <?= (($doc['tipo'] ?? '') === $slug) ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="fecha_<?= (int) $doc['id'] ?>">Fecha del documento *</label>
                                                <input type="date" id="fecha_<?= (int) $doc['id'] ?>" name="fecha_documento" class="form-control" value="<?= esc($doc['fecha_documento'] ?? '') ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Guardar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoDocumento" tabindex="-1" role="dialog" aria-labelledby="modalNuevoDocumentoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoDocumentoLabel">Agregar documento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= route_to('paciente_documentacion_store') ?>" method="post" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" class="form-control<?= $errorDato($errorsDatos, 'nombre') ? ' is-invalid' : '' ?>" value="<?= esc(old('nombre', '')) ?>" maxlength="180" required>
                        <?php if ($errorDato($errorsDatos, 'nombre')): ?>
                            <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'nombre')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="tipo">Tipo *</label>
                        <select id="tipo" name="tipo" class="form-control<?= $errorDato($errorsDatos, 'tipo') ? ' is-invalid' : '' ?>" required>
                            <option value="">Seleccionar</option>
                            <?php foreach ($tipos as $slug => $label): ?>
                                <option value="<?= esc($slug) ?>" <?= old('tipo') === $slug ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($errorDato($errorsDatos, 'tipo')): ?>
                            <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'tipo')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="fecha_documento">Fecha del documento *</label>
                        <input type="date" id="fecha_documento" name="fecha_documento" class="form-control<?= $errorDato($errorsDatos, 'fecha_documento') ? ' is-invalid' : '' ?>" value="<?= esc(old('fecha_documento', '')) ?>" required>
                        <?php if ($errorDato($errorsDatos, 'fecha_documento')): ?>
                            <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'fecha_documento')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="archivo">Archivo *</label>
                        <input type="file" id="archivo" name="archivo" class="d-none" required>
                        <div id="dropzoneArchivo" class="dropzone-upload" tabindex="0">
                            <span class="dz-icon"><i class="fas fa-upload"></i></span>
                            <div>Soltá un archivo aquí para subirlo o <span class="font-weight-semibold">hacé clic para elegir</span></div>
                            <small class="d-block mt-1">PDF, JPG, JPEG, PNG — máximo 5 MB</small>
                            <small id="archivoSeleccionado" class="d-block mt-2 text-muted"></small>
                        </div>
                        <?php if ($errorDato($errorsDatos, 'archivo')): ?>
                            <div class="invalid-feedback d-block mt-2"><?= esc($errorDato($errorsDatos, 'archivo')) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($errorDato($errorsDatos, 'general')): ?>
                        <div class="alert alert-danger"><?= esc($errorDato($errorsDatos, 'general')) ?></div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ¿Seguro que querés eliminar este documento? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formEliminarDocumento" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
(function () {
    var dropzone = document.getElementById('dropzoneArchivo');
    var input = document.getElementById('archivo');
    var seleccionado = document.getElementById('archivoSeleccionado');

    if (!dropzone || !input) {
        return;
    }

    var setNombreArchivo = function (fileList) {
        if (!fileList || fileList.length === 0) {
            seleccionado.textContent = '';
            return;
        }
        seleccionado.textContent = 'Seleccionado: ' + fileList[0].name;
    };

    dropzone.addEventListener('click', function () {
        input.click();
    });

    dropzone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            input.click();
        }
    });

    input.addEventListener('change', function (e) {
        setNombreArchivo(e.target.files);
    });

    ['dragenter', 'dragover'].forEach(function (evtName) {
        dropzone.addEventListener(evtName, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function (evtName) {
        dropzone.addEventListener(evtName, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', function (e) {
        var files = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files : null;
        if (files && files.length > 0) {
            input.files = files;
            setNombreArchivo(files);
        }
    });
})();

(function () {
    var modal = $('#modalConfirmarEliminar');
    var formEliminar = document.getElementById('formEliminarDocumento');

    if (!formEliminar) {
        return;
    }

    $('.btn-open-delete').on('click', function () {
        var action = this.getAttribute('data-action');
        if (action) {
            formEliminar.setAttribute('action', action);
            modal.modal('show');
        }
    });
})();
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
