<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Iniciar sesión') ?></title>
    <link rel="stylesheet" href="<?= base_url('adminlte/plugins/fontawesome-free/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('adminlte/dist/css/adminlte.min.css'); ?>">
</head>
<body class="hold-transition login-page bg-gradient-primary">

<div class="login-box">
    <div class="login-logo">
        <a href="<?= base_url('/') ?>" class="text-white">
            <i class="fas fa-heartbeat mr-1"></i>
            <b>Health</b>Pro
        </a>
    </div>

    <div class="card card-outline card-primary elevation-2">
        <div class="card-header text-center">
            <span class="h5 mb-0" style="color: black;">¡Bienvenido/a!</span>
        </div>
        <div class="card-body">
            <p class="login-box-msg text-muted">Use sus credenciales para iniciar sesión.</p>

            <?php if (session()->getFlashdata('login_error')): ?>
                <div class="alert alert-danger" role="alert">
                    <?= esc(session()->getFlashdata('login_error')) ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-warning" role="alert">
                    <?php foreach ((array) session()->getFlashdata('errors') as $err): ?>
                        <div><?= esc($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('auth/login') ?>" method="post" novalidate autocomplete="on">
                <?= csrf_field() ?>
                <div class="input-group mb-3">
                    <?php $errors = (array) session()->getFlashdata('errors'); ?>
                    <input type="email" name="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" placeholder="Email"
                           value="<?= esc(old('email') ?? '') ?>" required autofocus>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" id="password" name="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" placeholder="Contraseña" required>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostrar/Ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt mr-1"></i> Ingresar
                        </button>
                    </div>
                </div>
            </form>
            <p class="mt-3 text-center text-muted small mb-0">Acceso restringido a usuarios registrados.</p>
        </div>
    </div>
</div>

<script src="<?= base_url('adminlte/plugins/jquery/jquery.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/dist/js/adminlte.min.js'); ?>"></script>
<script>
    (function () {
        var btn = document.getElementById('togglePassword');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var input = document.getElementById('password');
            if (!input) return;
            var isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            var icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    })();
</script>
</body>
</html>
