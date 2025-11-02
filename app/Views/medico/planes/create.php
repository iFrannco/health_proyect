<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$errores         = $errors ?? [];
$actividadErrors = $actividadErrors ?? [];
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="mb-1">Nuevo plan personalizado</h1>
                <p class="text-muted mb-0">
                    Define manualmente un plan de cuidado asociado a un diagnóstico existente.
                </p>
            </div>
            <a href="<?= route_to('medico_planes_index') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver al listado
            </a>
        </div>

        <?= view('layouts/partials/alerts') ?>

        <?php if (! empty($errores)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle mr-2"></i>
                Revisa los datos del formulario. Algunos campos requieren tu atención.
            </div>
        <?php endif; ?>

        <?= view('medico/planes/partials/form', [
            'formAction'      => route_to('medico_planes_store'),
            'formMethod'      => 'post',
            'isEdit'          => false,
            'submitLabel'     => 'Guardar plan de cuidado',
            'plan'            => [],
            'pacientes'       => $pacientes,
            'diagnosticos'    => $diagnosticos,
            'errors'          => $errores,
            'actividadErrors' => $actividadErrors,
            'actividades'     => [],
        ]) ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('medico/planes/partials/form_script') ?>
<?= $this->endSection() ?>
