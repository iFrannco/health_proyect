<?= $this->extend('layouts/base') ?>

<?= $this->section('title') ?>Planes Estandarizados<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Planes Estandarizados</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= site_url('admin/home') ?>">Inicio</a></li>
                    <li class="breadcrumb-item active">Planes Estandarizados</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        
        <?php if (session()->has('message')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session('message') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Listado de Planes</h3>
                <div class="card-tools">
                    <a href="<?= route_to('admin_planes_estandar_create') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Plan
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="get" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..." value="<?= esc($search) ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-default" type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Versión</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th style="width: 150px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($planes)) : ?>
                            <tr>
                                <td colspan="6" class="text-center">No se encontraron planes.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($planes as $plan) : ?>
                                <tr>
                                    <td><?= $plan->id ?></td>
                                    <td><?= esc($plan->nombre) ?></td>
                                    <td>v<?= esc($plan->version) ?></td>
                                    <td><?= esc(substr($plan->descripcion ?? '', 0, 50)) ?>...</td>
                                    <td>
                                        <?php if (! $plan->deleted_at) : ?>
                                            <span class="badge badge-success">Vigente</span>
                                        <?php else : ?>
                                            <span class="badge badge-secondary">No Vigente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= route_to('admin_planes_estandar_edit', $plan->id) ?>" class="btn btn-info btn-sm" title="Editar">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        
                                        <form action="<?= route_to('admin_planes_estandar_toggle', $plan->id) ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm <?= ! $plan->deleted_at ? 'btn-warning' : 'btn-success' ?>" title="<?= ! $plan->deleted_at ? 'Inhabilitar' : 'Habilitar' ?>">
                                                <i class="fas <?= ! $plan->deleted_at ? 'fa-ban' : 'fa-check' ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <?= $pager->links() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
