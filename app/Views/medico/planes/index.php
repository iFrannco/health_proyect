<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Planes de cuidado</h1>
                <p class="text-muted mb-0">
                    Consulta y registra planes de cuidado personalizados o basados en plantillas estándar.
                </p>
            </div>
            <a href="<?= route_to('medico_planes_create') ?>" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nuevo plan
            </a>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <?php if (empty($planes)): ?>
            <div class="card card-outline card-secondary">
                <div class="card-body text-center text-muted">
                    Aún no registraste planes personalizados. Usa el botón "Nuevo plan" para comenzar.
                </div>
            </div>
        <?php else: ?>
            <?php $totalPlanes = count($planes); ?>
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Planes creados recientemente
                        <small class="text-muted font-weight-normal ml-2">
                            <?= $totalPlanes === 1 ? '1 plan' : $totalPlanes . ' planes' ?>
                        </small>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Paciente</th>
                                    <th scope="col">Diagnóstico</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col" class="text-nowrap">Vigencia</th>
                                    <th scope="col" class="text-nowrap">Creado</th>
                                    <th scope="col" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($planes as $plan): ?>
                                    <?php
                                    $pacienteNombre = trim(($plan['paciente_apellido'] ?? '') . ', ' . ($plan['paciente_nombre'] ?? ''));
                                    $diagnostico     = (string) ($plan['diagnostico_descripcion'] ?? '');
                                    if (mb_strlen($diagnostico) > 120) {
                                        $diagnostico = mb_substr($diagnostico, 0, 117) . '...';
                                    }
                                    $nombrePlan = trim((string) ($plan['nombre'] ?? ''));
                                    if ($nombrePlan === '') {
                                        $nombrePlan = 'Plan sin nombre';
                                    }
                                    $fechaInicio = $plan['fecha_inicio'] ? date('d/m/Y', strtotime($plan['fecha_inicio'])) : '-';
                                    $fechaFin    = $plan['fecha_fin'] ? date('d/m/Y', strtotime($plan['fecha_fin'])) : '-';
                                    $fechaCreacion = $plan['fecha_creacion'] ? date('d/m/Y H:i', strtotime($plan['fecha_creacion'])) : '-';
                                    $tipoBadge = $plan['plan_estandar_id'] ? '<span class="badge badge-info">Estándar</span>' : '<span class="badge badge-secondary">Personalizado</span>';
                                    ?>
                                    <tr>
                                        <td><?= esc($pacienteNombre ?: 'Paciente sin datos') ?></td>
                                        <td><?= esc($diagnostico) ?></td>
                                        <td>
                                            <a href="<?= route_to('medico_planes_show', $plan['id']) ?>">
                                                <?= esc($nombrePlan) ?>
                                            </a>
                                        </td>
                                        <td><?= $tipoBadge ?></td>
                                        <td class="text-nowrap"><?= esc($fechaInicio . ' → ' . $fechaFin) ?></td>
                                        <td class="text-nowrap"><?= esc($fechaCreacion) ?></td>
                                        <td class="text-right">
                                            <a href="<?= route_to('medico_planes_show', $plan['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
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
