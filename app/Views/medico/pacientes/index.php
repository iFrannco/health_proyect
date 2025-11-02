<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Pacientes</h1>
                <p class="text-muted mb-0">
                    Consulta el padrón de pacientes y asigna diagnósticos rápidamente.
                </p>
            </div>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <div class="card card-outline card-primary mb-4">
            <div class="card-body">
                <form method="get" action="<?= site_url('medico/pacientes') ?>" class="form-row align-items-end">
                    <div class="col-12 col-sm-8 col-md-6 col-lg-5">
                        <label for="busqueda" class="d-block text-muted mb-1">Buscar por nombre o apellido</label>
                        <input
                            type="text"
                            id="busqueda"
                            name="q"
                            value="<?= esc($busqueda) ?>"
                            class="form-control"
                            placeholder="Ej: Ana, Paz, Rossi"
                            autocomplete="off"
                        >
                    </div>
                    <div class="col-12 col-sm-auto d-flex align-items-end mt-3 mt-sm-0">
                        <button type="submit" class="btn btn-primary mr-sm-2">
                            <i class="fas fa-search mr-1"></i> Buscar
                        </button>
                        <?php if ($busqueda !== ''): ?>
                            <a href="<?= site_url('medico/pacientes') ?>" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($pacientes)): ?>
            <div class="card card-outline card-secondary">
                <div class="card-body text-center text-muted">
                    <?php if ($busqueda === ''): ?>
                        No se encontraron pacientes registrados.
                    <?php else: ?>
                        No se encontraron pacientes que coincidan con la búsqueda realizada.
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php $resultadosPagina = count($pacientes); ?>
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Listado de pacientes
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
                                <?php foreach ($pacientes as $paciente): ?>
                                    <?php
                                    $pacienteId    = (int) ($paciente['id'] ?? 0);
                                    $apellido      = trim((string) ($paciente['apellido'] ?? ''));
                                    $nombre        = trim((string) ($paciente['nombre'] ?? ''));
                                    $dni           = $paciente['dni'] ?? null;
                                    $email         = (string) ($paciente['email'] ?? '-');
                                    $telefono      = (string) ($paciente['telefono'] ?? '-');
                                    $estaActivo    = (bool) ($paciente['activo'] ?? false);
                                    $dniTexto      = $dni !== null && $dni !== '' ? (string) $dni : 'No informado';
                                    $asignarUrl    = route_to('medico_diagnosticos_create') . '?paciente_id=' . urlencode((string) $pacienteId);
                                    ?>
                                    <tr>
                                        <td><?= esc($apellido) ?></td>
                                        <td><?= esc($nombre) ?></td>
                                        <td><?= esc($dniTexto) ?></td>
                                        <td><?= esc($email) ?></td>
                                        <td><?= $telefono !== '' ? esc($telefono) : '-' ?></td>
                                        <td>
                                            <span class="badge <?= $estaActivo ? 'badge-success' : 'badge-secondary' ?>">
                                                <?= $estaActivo ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= esc($asignarUrl) ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-notes-medical mr-1"></i> Asignar diagnóstico
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($pager !== null && $pager->getPageCount('pacientes') > 1): ?>
                    <?php
                    $grupo         = 'pacientes';
                    $paginaActual  = $pager->getCurrentPage($grupo);
                    $totalPaginas  = $pager->getPageCount($grupo);
                    $urlAnterior   = $pager->getPreviousPageURI($grupo);
                    $urlSiguiente  = $pager->getNextPageURI($grupo);
                    $hayAnterior   = $urlAnterior !== null && $urlAnterior !== '';
                    $haySiguiente  = $urlSiguiente !== null && $urlSiguiente !== '';
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
