<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
/** @var App\Entities\User|null $usuario */
$usuario             = $usuario ?? null;
$errorsDatos         = $errorsDatos ?? [];
$errorsPassword      = $errorsPassword ?? [];
$rolLabel            = $rolLabel ?? 'Usuario';
$formRoutes          = $formRoutes ?? ['datos' => '#', 'password' => '#'];
$perfilTitulo        = $perfilTitulo ?? 'Mi perfil';
$perfilDescripcion   = $perfilDescripcion ?? 'Actualizá tus datos personales y gestioná la seguridad de tu cuenta.';
$mostrarPasswordForm = $mostrarPasswordForm ?? true;
$volverUrl           = $volverUrl ?? null;
$adminActions        = $adminActions ?? ['enabled' => false];
$nombreCompleto      = '';

if ($usuario !== null) {
    $nombreCompleto = trim((string) ($usuario->nombre ?? '') . ' ' . ((string) ($usuario->apellido ?? '')));
}

$fechaNac = '';
if ($usuario !== null && ! empty($usuario->fecha_nac)) {
    $fechaNac = $usuario->fecha_nac instanceof \DateTimeInterface
        ? $usuario->fecha_nac->format('Y-m-d')
        : (string) $usuario->fecha_nac;
}

$oldFechaNac = old('fecha_nac', $fechaNac);

$errorDato = static function (array $errors, string $campo): ?string {
    return $errors[$campo] ?? null;
};

$adminActionsEnabled      = (bool) ($adminActions['enabled'] ?? false);
$adminEstadoActual        = (bool) ($adminActions['estadoActual'] ?? false);
$adminEstadoRoute         = (string) ($adminActions['estadoRoute'] ?? '');
$adminResetRoute          = (string) ($adminActions['resetPasswordRoute'] ?? '');
$adminResetConfirmMessage = (string) ($adminActions['resetPasswordConfirm'] ?? '¿Deseás resetear la contraseña de este usuario?');
$accionEstadoValor        = $adminEstadoActual ? 'desactivar' : 'reactivar';
$accionEstadoLabel        = $adminEstadoActual ? 'Desactivar usuario' : 'Reactivar usuario';
$accionEstadoIcon         = $adminEstadoActual ? 'fa-user-slash' : 'fa-user-check';
$accionEstadoBtnClass     = $adminEstadoActual ? 'btn-outline-danger' : 'btn-outline-success';
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
                <h1 class="mb-1"><?= esc($perfilTitulo) ?></h1>
                <?php if ($perfilDescripcion !== ''): ?>
                    <p class="text-muted mb-0"><?= esc($perfilDescripcion) ?></p>
                <?php endif; ?>
            </div>
            <?php if (! empty($volverUrl)): ?>
                <a href="<?= esc($volverUrl) ?>" class="btn btn-link text-muted p-0 d-none d-md-inline">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-auto text-right mt-3 mt-md-0">
        <span class="badge badge-primary py-2 px-3">
            <i class="fas fa-user mr-1"></i> <?= esc($nombreCompleto ?: $rolLabel) ?>
        </span>
    </div>
</div>

<?php if (! empty($volverUrl)): ?>
    <div class="mb-3 d-md-none">
        <a href="<?= esc($volverUrl) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
<?php endif; ?>

<?= view('layouts/partials/alerts') ?>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card card-outline card-primary h-100">
            <div class="card-header border-0">
                <h3 class="card-title mb-0">
                    <i class="fas fa-id-card mr-2 text-primary"></i>Datos personales
                </h3>
            </div>
            <div class="card-body">
                <form action="<?= esc($formRoutes['datos'] ?? '#') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control<?= $errorDato($errorsDatos, 'nombre') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('nombre', $usuario->nombre ?? '')) ?>" maxlength="120" required>
                            <?php if ($errorDato($errorsDatos, 'nombre')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'nombre')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="apellido">Apellido *</label>
                            <input type="text" id="apellido" name="apellido" class="form-control<?= $errorDato($errorsDatos, 'apellido') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('apellido', $usuario->apellido ?? '')) ?>" maxlength="120" required>
                            <?php if ($errorDato($errorsDatos, 'apellido')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'apellido')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="dni">DNI *</label>
                            <input type="text" id="dni" name="dni" class="form-control<?= $errorDato($errorsDatos, 'dni') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('dni', $usuario->dni ?? '')) ?>" maxlength="20" minlength="6" required>
                            <?php if ($errorDato($errorsDatos, 'dni')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'dni')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control<?= $errorDato($errorsDatos, 'email') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('email', $usuario->email ?? '')) ?>" maxlength="180" required>
                            <?php if ($errorDato($errorsDatos, 'email')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'email')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" class="form-control<?= $errorDato($errorsDatos, 'telefono') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('telefono', $usuario->telefono ?? '')) ?>" maxlength="50" placeholder="Ej: +54 11 5555-1234">
                            <?php if ($errorDato($errorsDatos, 'telefono')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'telefono')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="fecha_nac">Fecha de nacimiento</label>
                            <input type="date" id="fecha_nac" name="fecha_nac" class="form-control<?= $errorDato($errorsDatos, 'fecha_nac') ? ' is-invalid' : '' ?>"
                                   value="<?= esc($oldFechaNac) ?>">
                            <?php if ($errorDato($errorsDatos, 'fecha_nac')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'fecha_nac')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <small class="text-muted">Los campos marcados con * son obligatorios.</small>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <?php if ($mostrarPasswordForm): ?>
            <div class="card card-outline card-primary h-100">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-shield-alt mr-2 text-primary"></i>Seguridad de la cuenta
                    </h3>
                </div>
                <div class="card-body">
                    <form action="<?= esc($formRoutes['password'] ?? '#') ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="password_actual">Contraseña actual *</label>
                            <input type="password" id="password_actual" name="password_actual" class="form-control<?= $errorDato($errorsPassword, 'password_actual') ? ' is-invalid' : '' ?>" required>
                            <?php if ($errorDato($errorsPassword, 'password_actual')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsPassword, 'password_actual')) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password_nueva">Nueva contraseña *</label>
                            <input type="password" id="password_nueva" name="password_nueva" class="form-control<?= $errorDato($errorsPassword, 'password_nueva') ? ' is-invalid' : '' ?>" minlength="8" maxlength="64" required>
                            <small class="form-text text-muted">Debe tener al menos 8 caracteres.</small>
                            <?php if ($errorDato($errorsPassword, 'password_nueva')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsPassword, 'password_nueva')) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmacion">Confirmar contraseña *</label>
                            <input type="password" id="password_confirmacion" name="password_confirmacion" class="form-control<?= $errorDato($errorsPassword, 'password_confirmacion') ? ' is-invalid' : '' ?>" required>
                            <?php if ($errorDato($errorsPassword, 'password_confirmacion')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsPassword, 'password_confirmacion')) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-lock mr-1"></i> Actualizar contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($adminActionsEnabled): ?>
            <div class="card card-outline card-secondary<?= $mostrarPasswordForm ? ' mt-4' : ' h-100' ?>">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-user-shield mr-2 text-secondary"></i>Gestión administrativa
                    </h3>
                    <span class="badge <?= $adminEstadoActual ? 'badge-success' : 'badge-secondary' ?>">
                        <?= $adminEstadoActual ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Solo los administradores pueden resetear la contraseña o cambiar el estado de esta cuenta.
                    </p>

                    <?php if ($adminResetRoute !== ''): ?>
                        <form action="<?= esc($adminResetRoute) ?>" method="post" class="mb-3">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-primary btn-block" title="<?= esc($adminResetConfirmMessage) ?>">
                                <i class="fas fa-key mr-1"></i> Resetear contraseña
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($adminEstadoRoute !== ''): ?>
                        <form action="<?= esc($adminEstadoRoute) ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="<?= esc($accionEstadoValor) ?>">
                            <button type="submit" class="btn <?= $accionEstadoBtnClass ?> btn-block">
                                <i class="fas <?= $accionEstadoIcon ?> mr-1"></i> <?= esc($accionEstadoLabel) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
