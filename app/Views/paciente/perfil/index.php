<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php
/** @var App\Entities\User|null $paciente */
$paciente        = $paciente ?? null;
$errorsDatos     = $errorsDatos ?? [];
$errorsPassword  = $errorsPassword ?? [];
$nombreCompleto  = '';

if ($paciente !== null) {
    $nombreCompleto = trim((string) ($paciente->nombre ?? '') . ' ' . ((string) ($paciente->apellido ?? '')));
}

$fechaNac = '';
if ($paciente !== null && ! empty($paciente->fecha_nac)) {
    $fechaNac = $paciente->fecha_nac instanceof \DateTimeInterface
        ? $paciente->fecha_nac->format('Y-m-d')
        : (string) $paciente->fecha_nac;
}

$oldFechaNac = old('fecha_nac', $fechaNac);

$errorDato = static function (array $errors, string $campo): ?string {
    return $errors[$campo] ?? null;
};
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h1 class="mb-1">Mi perfil</h1>
        <p class="text-muted mb-0">Actualizá tus datos personales y gestioná la seguridad de tu cuenta.</p>
    </div>
    <div class="col-auto text-right">
        <span class="badge badge-primary py-2 px-3">
            <i class="fas fa-user mr-1"></i> <?= esc($nombreCompleto ?: 'Paciente') ?>
        </span>
    </div>
</div>

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
                <form action="<?= route_to('paciente_perfil_actualizar_datos') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control<?= $errorDato($errorsDatos, 'nombre') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('nombre', $paciente->nombre ?? '')) ?>" maxlength="120" required>
                            <?php if ($errorDato($errorsDatos, 'nombre')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'nombre')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="apellido">Apellido *</label>
                            <input type="text" id="apellido" name="apellido" class="form-control<?= $errorDato($errorsDatos, 'apellido') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('apellido', $paciente->apellido ?? '')) ?>" maxlength="120" required>
                            <?php if ($errorDato($errorsDatos, 'apellido')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'apellido')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control<?= $errorDato($errorsDatos, 'email') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('email', $paciente->email ?? '')) ?>" maxlength="180" required>
                            <?php if ($errorDato($errorsDatos, 'email')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'email')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" class="form-control<?= $errorDato($errorsDatos, 'telefono') ? ' is-invalid' : '' ?>"
                                   value="<?= esc(old('telefono', $paciente->telefono ?? '')) ?>" maxlength="50" placeholder="Ej: +54 11 5555-1234">
                            <?php if ($errorDato($errorsDatos, 'telefono')): ?>
                                <div class="invalid-feedback"><?= esc($errorDato($errorsDatos, 'telefono')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
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
        <div class="card card-outline card-primary h-100">
            <div class="card-header border-0">
                <h3 class="card-title mb-0">
                    <i class="fas fa-shield-alt mr-2 text-primary"></i>Seguridad de la cuenta
                </h3>
            </div>
            <div class="card-body">
                <form action="<?= route_to('paciente_perfil_actualizar_password') ?>" method="post" novalidate>
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
    </div>
</div>

<?= $this->endSection() ?>
