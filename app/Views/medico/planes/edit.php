<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$errores         = $errors ?? [];
$actividadErrors = $actividadErrors ?? [];
$plan            = $plan ?? [];

$planTitulo = trim((string) ($plan['nombre'] ?? ''));
if ($planTitulo === '') {
    $planTitulo = 'Plan sin nombre';
}
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Editar plan personalizado</h1>
                <p class="text-muted mb-0">
                    Estás actualizando <strong><?= esc($planTitulo) ?></strong>.
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: .5rem;">
                <a href="<?= route_to('medico_planes_show', $plan['id']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al detalle
                </a>
                <a href="<?= route_to('medico_planes_index') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-list mr-1"></i> Listado de planes
                </a>
            </div>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <?php if (! empty($errores)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle mr-2"></i>
                Revisa los datos del formulario. Algunos campos requieren tu atención.
            </div>
        <?php endif; ?>

        <?= view('medico/planes/partials/form', [
            'formAction'      => route_to('medico_planes_update', $plan['id']),
            'formMethod'      => 'put',
            'isEdit'          => true,
            'submitLabel'     => 'Actualizar plan de cuidado',
            'plan'            => $plan,
            'pacientes'       => $pacientes,
            'diagnosticos'    => $diagnosticos,
            'categoriasActividad' => $categoriasActividad ?? [],
            'errors'          => $errores,
            'actividadErrors' => $actividadErrors,
            'actividades'     => $actividades,
        ]) ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('medico/planes/partials/form_script') ?>
<?= $this->endSection() ?>
