<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
$errorList   = $errors ?? [];
$roleOptions = $roleOptions ?? [];
$oldRol      = old('rol');
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">
        <div class="mb-3">
            <a href="<?= route_to('admin_usuarios_index') ?>" class="btn btn-link p-0 align-baseline">
                <i class="fas fa-arrow-left mr-1"></i> Volver al listado de usuarios
            </a>
        </div>

        <h1 class="mb-4">Nuevo usuario</h1>

        <?= view('layouts/partials/alerts') ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">Datos del usuario</h3>
            </div>
            <form action="<?= route_to('admin_usuarios_store') ?>" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?php if (isset($errorList['general'])): ?>
                        <div class="alert alert-danger">
                            <?= esc($errorList['general']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nombre">Nombre</label>
                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                value="<?= esc(old('nombre')) ?>"
                                class="form-control<?= isset($errorList['nombre']) ? ' is-invalid' : '' ?>"
                                maxlength="120"
                                minlength="2"
                                required
                                autocomplete="given-name"
                            >
                            <?php if (isset($errorList['nombre'])): ?>
                                <span class="invalid-feedback d-block">
                                    <?= esc($errorList['nombre']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="apellido">Apellido</label>
                            <input
                                type="text"
                                id="apellido"
                                name="apellido"
                                value="<?= esc(old('apellido')) ?>"
                                class="form-control<?= isset($errorList['apellido']) ? ' is-invalid' : '' ?>"
                                maxlength="120"
                                minlength="2"
                                required
                                autocomplete="family-name"
                            >
                            <?php if (isset($errorList['apellido'])): ?>
                                <span class="invalid-feedback d-block">
                                    <?= esc($errorList['apellido']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="fecha_nac">Fecha de nacimiento</label>
                            <input
                                type="date"
                                id="fecha_nac"
                                name="fecha_nac"
                                value="<?= esc(old('fecha_nac')) ?>"
                                class="form-control<?= isset($errorList['fecha_nac']) ? ' is-invalid' : '' ?>"
                                placeholder="AAAA-MM-DD"
                            >
                            <small class="form-text text-muted">Formato AAAA-MM-DD.</small>
                            <?php if (isset($errorList['fecha_nac'])): ?>
                                <span class="invalid-feedback d-block">
                                    <?= esc($errorList['fecha_nac']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="telefono">Teléfono</label>
                            <input
                                type="text"
                                id="telefono"
                                name="telefono"
                                value="<?= esc(old('telefono')) ?>"
                                class="form-control<?= isset($errorList['telefono']) ? ' is-invalid' : '' ?>"
                                maxlength="50"
                                autocomplete="tel"
                            >
                            <small class="form-text text-muted">Opcional.</small>
                            <?php if (isset($errorList['telefono'])): ?>
                                <span class="invalid-feedback d-block">
                                    <?= esc($errorList['telefono']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= esc(old('email')) ?>"
                                class="form-control<?= isset($errorList['email']) ? ' is-invalid' : '' ?>"
                                maxlength="180"
                                required
                                autocomplete="email"
                            >
                            <?php if (isset($errorList['email'])): ?>
                                <span class="invalid-feedback d-block">
                                    <?= esc($errorList['email']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="rol">Rol</label>
                            <select
                                id="rol"
                                name="rol"
                                class="form-control<?= isset($errorList['rol']) ? ' is-invalid' : '' ?>"
                                required
                            >
                                <option value="">Selecciona un rol</option>
                                <?php foreach ($roleOptions as $valor => $label): ?>
                                    <option value="<?= esc($valor) ?>"<?= (string) $oldRol === (string) $valor ? ' selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errorList['rol'])): ?>
                                <span class="invalid-feedback d-block">
                                    <?= esc($errorList['rol']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña inicial</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control<?= isset($errorList['password']) ? ' is-invalid' : '' ?>"
                            minlength="8"
                            maxlength="64"
                            required
                            autocomplete="new-password"
                        >
                        <small class="form-text text-muted">
                            Debe tener al menos 8 caracteres.
                        </small>
                        <?php if (isset($errorList['password'])): ?>
                            <span class="invalid-feedback d-block">
                                <?= esc($errorList['password']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 d-flex flex-column flex-sm-row justify-content-end">
                    <a href="<?= route_to('admin_usuarios_index') ?>" class="btn btn-outline-secondary mb-2 mb-sm-0 mr-sm-3">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Guardar usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
