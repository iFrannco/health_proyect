<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Restaurar contraseña') ?></title>
    <link rel="stylesheet" href="<?= base_url('adminlte/plugins/fontawesome-free/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('adminlte/dist/css/adminlte.min.css'); ?>">
</head>
<body class="hold-transition login-page bg-gradient-primary" style="background: linear-gradient(145deg, #0f6fdc, #0a58b7);">
<?php
    $errors = $errors ?? (session()->getFlashdata('errors') ?? []);
?>
<div class="login-box">
    <div class="login-logo">
        <a href="<?= base_url('/') ?>" class="text-white">
            <i class="fas fa-heartbeat mr-1"></i>
            <b>Health</b>Pro
        </a>
    </div>

    <div class="card card-outline card-primary elevation-2" style="border-color: #0a58b7;">
        <div class="card-header text-center">
            <span class="h5 mb-0" style="color: black;">Restaurar contraseña</span>
        </div>
        <div class="card-body">
            <p class="login-box-msg text-muted" style="color: #3c3c3c !important;">
                Ingrese su correo y, si existe una cuenta asociada, se enviará un enlace para restablecer la contraseña.
                El enlace es válido por 15 minutos.
            </p>

            <?php if (! empty($reset_error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= esc($reset_error) ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($status)): ?>
                <div class="alert alert-success" role="alert">
                    <?= esc($status) ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($errors)): ?>
                <div class="alert alert-warning" role="alert">
                    <?php foreach ((array) $errors as $err): ?>
                        <div><?= esc($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="<?= route_to('auth_password_send') ?>" method="post" novalidate autocomplete="off">
                <?= csrf_field() ?>
                <div class="input-group mb-3">
                    <input
                        type="email"
                        name="email"
                        class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                        placeholder="Correo electrónico"
                        value="<?= esc(old('email') ?? '') ?>"
                        maxlength="180"
                        required
                        autofocus
                    >
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane mr-1"></i> Enviar enlace
                </button>
            </form>

            <?php if (! empty($reset_link)): ?>
                <div class="alert mt-3" role="alert" style="background-color: #fff9e6; border: 1px solid #f0c36d; color: #3d2c00;">
                    <div class="text-uppercase small font-weight-bold mb-2" style="color: #d48806;">Enlace temporal (solo mientras no hay envío por correo)</div>
                    <div class="text-break" style="word-break: break-word;">
                        <a href="<?= esc($reset_link) ?>" style="color: #0050b3; font-weight: 600; text-decoration: underline;">
                            <?= esc($reset_link) ?>
                        </a>
                    </div>
                    <p class="mb-0 mt-2 small" style="color: #5c4a0a;">Válido por 15 minutos. Úselo para definir una nueva contraseña.</p>
                </div>
            <?php endif; ?>

            <div class="mt-3 text-center">
                <a href="<?= route_to('auth_login') ?>" class="text-primary">Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('adminlte/plugins/jquery/jquery.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/dist/js/adminlte.min.js'); ?>"></script>
</body>
</html>
