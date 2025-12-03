<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Restablecer contraseña') ?></title>
    <link rel="stylesheet" href="<?= base_url('adminlte/plugins/fontawesome-free/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('adminlte/dist/css/adminlte.min.css'); ?>">
</head>
<body class="hold-transition login-page bg-gradient-primary" style="background: linear-gradient(145deg, #0f6fdc, #0a58b7);">
<style>
    .reset-card-body {
        color: #1f1f1f;
    }
    .reset-card-body label {
        color: #2b2b2b;
        font-weight: 700;
    }
    .reset-helper {
        color: #4a4a4a;
    }
    .reset-link a {
        color: #0a4cbf;
        font-weight: 600;
    }
</style>
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
            <span class="h5 mb-0" style="color: black;">Restablecer contraseña</span>
        </div>
        <div class="card-body reset-card-body">
            <p class="login-box-msg" style="color: #2f2f2f !important;">
                Defina una nueva contraseña para su cuenta. La contraseña debe cumplir la política vigente.
            </p>

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

            <form action="<?= route_to('auth_password_process') ?>" method="post" novalidate autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                            minlength="8"
                            maxlength="64"
                            required
                            autocomplete="new-password"
                            pattern="^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$"
                            title="Debe tener al menos 8 caracteres, una letra, un número y un símbolo."
                        >
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostrar/Ocultar contraseña">
                            <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <span class="invalid-feedback d-block"><?= esc($errors['password']) ?></span>
                    <?php else: ?>
                        <small class="form-text reset-helper">
                            Debe incluir al menos 8 caracteres, una letra, un número y un símbolo.
                        </small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar contraseña</label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>"
                            minlength="8"
                            maxlength="64"
                            required
                            autocomplete="new-password"
                        >
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm" aria-label="Mostrar/Ocultar confirmación">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <span class="invalid-feedback d-block"><?= esc($errors['password_confirm']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-unlock-alt mr-1"></i> Actualizar contraseña
                </button>
            </form>

            <div class="mt-3 text-center">
                <a href="<?= route_to('auth_login') ?>" class="text-primary">Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('adminlte/plugins/jquery/jquery.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/dist/js/adminlte.min.js'); ?>"></script>
<script>
    (function () {
        var togglePassword = document.getElementById('togglePassword');
        var toggleConfirm = document.getElementById('togglePasswordConfirm');
        var passwordInput = document.getElementById('password');
        var confirmInput = document.getElementById('password_confirm');

        function toggleVisibility(button, input) {
            if (!button || !input) return;
            button.addEventListener('click', function () {
                var isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                var icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
            });
        }

        toggleVisibility(togglePassword, passwordInput);
        toggleVisibility(toggleConfirm, confirmInput);
    })();
</script>
</body>
</html>
