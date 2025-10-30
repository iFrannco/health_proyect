<?= $this->extend('medico/layout') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Diagnosticos</h1>
                <p class="text-muted mb-0">
                    Registra y consulta los diagnosticos cargados para tus pacientes.
                </p>
            </div>
            <a href="<?= route_to('medico_diagnosticos_create') ?>" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nuevo diagnostico
            </a>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <?php if (empty($diagnosticos)): ?>
            <div class="card card-outline card-secondary">
                <div class="card-body text-center text-muted">
                    Aun no registraste diagnosticos. Usa el boton "Nuevo diagnostico" para comenzar.
                </div>
            </div>
        <?php else: ?>
            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Listado reciente</h3>
                    <span class="badge badge-primary badge-pill"><?= count($diagnosticos) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Paciente</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col" class="w-50">Descripcion</th>
                                    <th scope="col" class="text-nowrap">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diagnosticos as $diagnostico): ?>
                                    <?php
                                    $pacienteNombre = trim(($diagnostico['paciente_apellido'] ?? '') . ', ' . ($diagnostico['paciente_nombre'] ?? ''));
                                    $descripcion    = (string) ($diagnostico['descripcion'] ?? '');
                                    if (mb_strlen($descripcion) > 160) {
                                        $descripcion = mb_substr($descripcion, 0, 157) . '...';
                                    }
                                    $fechaRaw = $diagnostico['fecha_creacion'] ?? null;
                                    $fecha    = $fechaRaw ? date('d/m/Y H:i', strtotime($fechaRaw)) : '-';
                                    ?>
                                    <tr>
                                        <td><?= esc($pacienteNombre) ?></td>
                                        <td><?= esc($diagnostico['tipo_nombre'] ?? '-') ?></td>
                                        <td><?= esc($descripcion) ?></td>
                                        <td class="text-nowrap"><?= esc($fecha) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

