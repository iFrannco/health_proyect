<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Crear cuenta') ?></title>
    <link rel="stylesheet" href="<?= base_url('adminlte/plugins/fontawesome-free/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('adminlte/dist/css/adminlte.min.css'); ?>">
    <style>
        .register-box {
            width: 440px;
            max-width: calc(100% - 32px);
        }
        .register-card label {
            color: #4a4a4a;
            font-weight: 600;
        }
    </style>
</head>
<body class="hold-transition register-page bg-gradient-primary">
<?php
    $errorList   = $errors ?? (session()->getFlashdata('errors') ?? []);
    $roleOptions = $roleOptions ?? [];
    $oldRol      = strtolower((string) old('rol'));
?>
<div class="register-box">
    <div class="register-logo">
        <a href="<?= base_url('/') ?>" class="text-white">
            <i class="fas fa-heartbeat mr-1"></i>
            <b>Health</b>Pro
        </a>
    </div>

    <div class="card card-outline card-primary elevation-2 register-card">
        <div class="card-header text-center">
            <span class="h5 mb-0" style="color: black;">Crear una cuenta</span>
        </div>
        <div class="card-body">
            <p class="login-box-msg text-muted">Completá el formulario para registrarte.</p>

            <?php if (isset($errorList['general'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= esc($errorList['general']) ?>
                </div>
            <?php endif; ?>

            <form action="<?= route_to('auth_register_post') ?>" method="post" novalidate autocomplete="off">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nombre">Nombre</label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            class="form-control<?= isset($errorList['nombre']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('nombre')) ?>"
                            minlength="2"
                            maxlength="120"
                            required
                            autocomplete="given-name"
                        >
                        <?php if (isset($errorList['nombre'])): ?>
                            <span class="invalid-feedback d-block"><?= esc($errorList['nombre']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="apellido">Apellido</label>
                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            class="form-control<?= isset($errorList['apellido']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('apellido')) ?>"
                            minlength="2"
                            maxlength="120"
                            required
                            autocomplete="family-name"
                        >
                        <?php if (isset($errorList['apellido'])): ?>
                            <span class="invalid-feedback d-block"><?= esc($errorList['apellido']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="dni">DNI</label>
                        <input
                            type="text"
                            id="dni"
                            name="dni"
                            class="form-control<?= isset($errorList['dni']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('dni')) ?>"
                            minlength="6"
                            maxlength="20"
                            placeholder="Ej: 25.123.456"
                            required
                        >
                        <?php if (isset($errorList['dni'])): ?>
                            <span class="invalid-feedback d-block"><?= esc($errorList['dni']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fecha_nac">Fecha de nacimiento</label>
                        <input
                            type="date"
                            id="fecha_nac"
                            name="fecha_nac"
                            class="form-control<?= isset($errorList['fecha_nac']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('fecha_nac')) ?>"
                            placeholder="AAAA-MM-DD"
                        >
                        <?php if (isset($errorList['fecha_nac'])): ?>
                            <span class="invalid-feedback d-block"><?= esc($errorList['fecha_nac']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="telefono">Teléfono (opcional)</label>
                        <input
                            type="text"
                            id="telefono"
                            name="telefono"
                            class="form-control<?= isset($errorList['telefono']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('telefono')) ?>"
                            maxlength="50"
                        >
                        <?php if (isset($errorList['telefono'])): ?>
                            <span class="invalid-feedback d-block"><?= esc($errorList['telefono']) ?></span>
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
                            <option value="">Seleccioná una opción</option>
                            <?php foreach ($roleOptions as $slug => $rol): ?>
                                <option value="<?= esc($slug) ?>" <?= $oldRol === strtolower((string) $slug) ? 'selected' : '' ?>>
                                    <?= esc($rol['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errorList['rol'])): ?>
                            <span class="invalid-feedback d-block"><?= esc($errorList['rol']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control<?= isset($errorList['email']) ? ' is-invalid' : '' ?>"
                        value="<?= esc(old('email')) ?>"
                        maxlength="180"
                        required
                        autocomplete="email"
                    >
                    <?php if (isset($errorList['email'])): ?>
                        <span class="invalid-feedback d-block"><?= esc($errorList['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control<?= isset($errorList['password']) ? ' is-invalid' : '' ?>"
                        minlength="8"
                        maxlength="64"
                        required
                        autocomplete="new-password"
                        pattern="^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$"
                        title="Debe tener al menos 8 caracteres, una letra, un número y un símbolo."
                    >
                    <?php if (isset($errorList['password'])): ?>
                        <span class="invalid-feedback d-block"><?= esc($errorList['password']) ?></span>
                    <?php else: ?>
                        <small class="form-text text-muted">
                            Debe incluir al menos 8 caracteres, una letra, un número y un símbolo.
                        </small>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus mr-1"></i> Crear cuenta
                </button>
            </form>

            <p class="mt-3 text-center mb-0">
                <a href="<?= route_to('auth_login') ?>">Ya tengo una cuenta</a>
            </p>
        </div>
    </div>
</div>

<script src="<?= base_url('adminlte/plugins/jquery/jquery.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/dist/js/adminlte.min.js'); ?>"></script>
</body>
</html>
