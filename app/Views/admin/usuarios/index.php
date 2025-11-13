<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$roleFiltro = $roleFiltro ?? 'todos';
$roleOptions = $roleOptions ?? [];
$busqueda = $busqueda ?? '';
$mostrarInactivos = $mostrarInactivos ?? false;
$pagerGroup = $pagerGroup ?? 'admin_usuarios';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Usuarios</h1>
                <p class="text-muted mb-0">
                    Administra los usuarios del sistema con filtros por rol y buscador de texto.
                </p>
            </div>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <div class="card card-outline card-primary mb-4">
            <div class="card-body">
                <form method="get" action="<?= site_url('admin/usuarios') ?>" class="form-row align-items-end">
                    <div class="col-12 col-md-5">
                        <label for="busqueda" class="d-block text-muted mb-1">Buscar por nombre, apellido o email</label>
                        <input
                            type="text"
                            id="busqueda"
                            name="q"
                            value="<?= esc($busqueda) ?>"
                            class="form-control"
                            placeholder="Ej: Ana, Paz, ana@example.com"
                            autocomplete="off"
                        >
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mt-3 mt-md-0">
                        <label for="role" class="d-block text-muted mb-1">Filtrar por rol</label>
                        <select name="role" id="role" class="form-control">
                            <?php foreach ($roleOptions as $valor => $label): ?>
                                <option value="<?= esc($valor) ?>"<?= $valor === $roleFiltro ? ' selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-2 mt-3 mt-md-0">
                        <div class="custom-control custom-switch">
                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="incluir_inactivos"
                                name="incluir_inactivos"
                                value="1"
                                <?= $mostrarInactivos ? 'checked' : '' ?>
                            >
                            <label class="custom-control-label text-muted" for="incluir_inactivos">
                                Incluir inactivos
                            </label>
                        </div>
                    </div>
                    <div class="col-12 col-sm-auto mt-3 mt-sm-0 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-sm-2">
                            <i class="fas fa-search mr-1"></i> Aplicar
                        </button>
                        <?php if ($busqueda !== '' || $roleFiltro !== 'todos' || $mostrarInactivos): ?>
                            <a href="<?= site_url('admin/usuarios') ?>" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($usuarios)): ?>
            <div class="card card-outline card-secondary">
                <div class="card-body text-center text-muted">
                    <?php if ($busqueda === '' && $roleFiltro === 'todos' && ! $mostrarInactivos): ?>
                        No se encontraron usuarios registrados.
                    <?php else: ?>
                        No se encontraron usuarios que coincidan con los filtros seleccionados.
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php $resultadosPagina = count($usuarios); ?>
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Listado de usuarios
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
                                    <th scope="col" class="text-nowrap">Apellido</th>
                                    <th scope="col" class="text-nowrap">Nombre</th>
                                    <th scope="col" class="text-nowrap">DNI</th>
                                    <th scope="col" class="text-nowrap">Email</th>
                                    <th scope="col" class="text-nowrap">Teléfono</th>
                                    <th scope="col" class="text-nowrap">Estado</th>
                                    <th scope="col" class="text-center text-nowrap">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <?php
                                    $usuarioId     = (int) ($usuario['id'] ?? 0);
                                    $apellido      = trim((string) ($usuario['apellido'] ?? ''));
                                    $nombre        = trim((string) ($usuario['nombre'] ?? ''));
                                    $dni           = $usuario['dni'] ?? null;
                                    $email         = (string) ($usuario['email'] ?? '-');
                                    $telefono      = (string) ($usuario['telefono'] ?? '');
                                    $estaActivo    = (bool) ($usuario['activo'] ?? false);
                                    $rolNombre     = trim((string) ($usuario['rol_nombre'] ?? ''));
                                    $dniTexto      = $dni !== null && $dni !== '' ? (string) $dni : 'No informado';
                                    $telefonoTexto = $telefono !== '' ? $telefono : '-';
                                    $verEditarUrl  = site_url('admin/usuarios/' . $usuarioId . '/editar');
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="d-block"><?= esc($apellido) ?></span>
                                            <?php if ($rolNombre !== ''): ?>
                                                <small class="text-muted text-uppercase"><?= esc($rolNombre) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($nombre) ?></td>
                                        <td><?= esc($dniTexto) ?></td>
                                        <td><?= esc($email) ?></td>
                                        <td><?= esc($telefonoTexto) ?></td>
                                        <td>
                                            <span class="badge <?= $estaActivo ? 'badge-success' : 'badge-secondary' ?>">
                                                <?= $estaActivo ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= esc($verEditarUrl) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-user-edit mr-1"></i> Ver/Editar
                                            </a>
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
<?= $this->endSection() ?>
