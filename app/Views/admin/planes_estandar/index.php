<?= $this->extend('layouts/base') ?>

<?= $this->section('title') ?>Planes Estándar<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Listado de Planes Estándar</h3>
        <div class="card-tools">
            <a href="<?= base_url('admin/planes-estandar/new') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nuevo Plan
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('message') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo Diagnóstico</th>
                    <th>Versión</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($planes)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay planes estándar registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($planes as $plan): ?>
                        <tr>
                            <td><?= $plan->id ?></td>
                            <td><?= esc($plan->nombre) ?></td>
                            <td><?= esc($plan->tipo_diagnostico_nombre) ?></td>
                            <td><?= esc($plan->version) ?></td>
                            <td>
                                <?php if ($plan->vigente): ?>
                                    <span class="badge badge-success">Vigente</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No vigente</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $plan->fecha_creacion ?></td>
                            <td>
                                <a href="<?= base_url('admin/planes-estandar/edit/' . $plan->id) ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <?php if ($plan->vigente): ?>
                                    <a href="<?= base_url('admin/planes-estandar/toggle/' . $plan->id) ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-ban"></i> Deshabilitar
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('admin/planes-estandar/toggle/' . $plan->id) ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Habilitar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <?php // $pager->links() ?>
    </div>
</div>
<?= $this->endSection() ?>