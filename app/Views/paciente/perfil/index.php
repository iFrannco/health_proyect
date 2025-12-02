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
$mostrarEspecialidadesForm = (bool) ($mostrarEspecialidadesForm ?? false);
$especialidadesDisponibles = $especialidadesDisponibles ?? [];
$especialidadesSeleccionadas = $especialidadesSeleccionadas ?? [];
$especialidadesFormRoute = $especialidadesFormRoute ?? '#';
$errorsEspecialidades = $errorsEspecialidades ?? [];
$especialidadesDisponiblesJson = json_encode(array_values($especialidadesDisponibles), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
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

$especialidadError = static function (array $errors): ?string {
    return $errors['general'] ?? null;
};

$especialidadesSeleccionadasIds = array_map(
    static fn (array $esp): int => (int) ($esp['id'] ?? 0),
    $especialidadesSeleccionadas
);

$especialidadesPrevias = old('especialidades', $especialidadesSeleccionadasIds);
$especialidadesPrevias = array_map(static fn ($valor): int => (int) $valor, (array) $especialidadesPrevias);
$especialidadesPrevias = array_values(array_unique(array_filter($especialidadesPrevias, static fn (int $id): bool => $id > 0)));

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
        <?php if ($mostrarEspecialidadesForm): ?>
            <div class="card card-outline card-primary mb-4">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-stethoscope mr-2 text-primary"></i>Especialidades clínicas
                    </h3>
                    <span id="especialidades-count" class="badge badge-light text-muted"><?= count($especialidadesPrevias) ?> seleccionadas</span>
                </div>
                <div class="card-body">
                    <?php if ($especialidadError($errorsEspecialidades)): ?>
                        <div class="alert alert-danger">
                            <?= esc($especialidadError($errorsEspecialidades)) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($especialidadesDisponibles)): ?>
                        <div class="alert alert-warning mb-0">
                            No hay especialidades configuradas. Contactá a un administrador para habilitarlas.
                        </div>
                    <?php else: ?>
                        <form id="especialidades-form" action="<?= esc($especialidadesFormRoute) ?>" method="post" novalidate>
                            <?= csrf_field() ?>

                            <div class="form-group">
                                <label class="d-block mb-1">Especialidades actuales</label>
                                <div id="especialidades-lista" class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="text-muted">Sin especialidades asignadas.</span>
                                </div>
                                <small class="form-text text-muted mb-2">Usá el buscador para agregar o quitar especialidades del catálogo.</small>
                            </div>

                            <div class="form-group">
                                <label for="especialidades-busqueda">Buscar en catálogo</label>
                                <div class="input-group">
                                    <input type="text" id="especialidades-busqueda" class="form-control" list="especialidades-opciones" placeholder="Ej: Cardiología" autocomplete="off">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary" id="btn-especialidad-add">
                                            <i class="fas fa-plus mr-1"></i> Añadir
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" id="btn-especialidad-remove">
                                            <i class="fas fa-minus mr-1"></i> Quitar
                                        </button>
                                    </div>
                                </div>
                                <datalist id="especialidades-opciones">
                                    <?php foreach ($especialidadesDisponibles as $especialidad): ?>
                                        <option value="<?= esc($especialidad['nombre'] ?? '') ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="form-text text-muted">Escribí el nombre y usá los botones para añadir o quitar. Solo se aceptan opciones del catálogo.</small>
                            </div>

                            <div id="especialidades-hidden"></div>

                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-outline-primary btn-block">
                                    <i class="fas fa-save mr-1"></i> Actualizar especialidades
                                </button>
                            </div>
                        </form>
                        <script>
                            (() => {
                                const disponibles = <?= $especialidadesDisponiblesJson ?: '[]' ?>;
                                const seleccionInicial = <?= json_encode($especialidadesPrevias, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

                                const lista = document.getElementById('especialidades-lista');
                                const hidden = document.getElementById('especialidades-hidden');
                                const countBadge = document.getElementById('especialidades-count');
                                const inputBusqueda = document.getElementById('especialidades-busqueda');
                                const btnAdd = document.getElementById('btn-especialidad-add');
                                const btnRemove = document.getElementById('btn-especialidad-remove');

                                const mapNombreId = new Map(disponibles.map(item => [item.nombre.toLowerCase(), String(item.id)]));
                                const mapIdNombre = new Map(disponibles.map(item => [String(item.id), item.nombre]));

                                let seleccionadas = Array.from(new Set((seleccionInicial || []).map(id => String(id)).filter(id => mapIdNombre.has(id))));

                                const renderHidden = () => {
                                    hidden.innerHTML = '';
                                    seleccionadas.forEach(id => {
                                        const input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = 'especialidades[]';
                                        input.value = id;
                                        hidden.appendChild(input);
                                    });
                                };

                                const renderLista = () => {
                                    lista.innerHTML = '';
                                    if (seleccionadas.length === 0) {
                                        lista.innerHTML = '<span class="text-muted">Sin especialidades asignadas.</span>';
                                    } else {
                                        seleccionadas.forEach(id => {
                                            const nombre = mapIdNombre.get(id) || 'Especialidad';

                                            const wrapper = document.createElement('div');
                                            wrapper.className = 'd-flex align-items-center border rounded px-2 py-1 mr-2 mb-2';

                                            const texto = document.createElement('span');
                                            texto.textContent = nombre;
                                            texto.className = 'mr-2';

                                            const btn = document.createElement('button');
                                            btn.type = 'button';
                                            btn.className = 'btn btn-xs btn-outline-light text-danger';
                                            btn.innerHTML = '<i class="fas fa-times"></i>';
                                            btn.addEventListener('click', () => {
                                                seleccionadas = seleccionadas.filter(item => item !== id);
                                                renderTodo();
                                            });

                                            wrapper.appendChild(texto);
                                            wrapper.appendChild(btn);
                                            lista.appendChild(wrapper);
                                        });
                                    }

                                    if (countBadge) {
                                        countBadge.textContent = `${seleccionadas.length} seleccionadas`;
                                    }
                                };

                                const renderTodo = () => {
                                    renderHidden();
                                    renderLista();
                                };

                                const encontrarIdPorNombre = (nombreIngresado) => {
                                    const normalizado = String(nombreIngresado || '').trim().toLowerCase();
                                    if (normalizado === '') {
                                        return null;
                                    }

                                    return mapNombreId.get(normalizado) ?? null;
                                };

                                btnAdd?.addEventListener('click', () => {
                                    const id = encontrarIdPorNombre(inputBusqueda.value);
                                    if (!id) {
                                        inputBusqueda.classList.add('is-invalid');
                                        return;
                                    }
                                    inputBusqueda.classList.remove('is-invalid');
                                    if (!seleccionadas.includes(id)) {
                                        seleccionadas.push(id);
                                        seleccionadas = Array.from(new Set(seleccionadas));
                                        renderTodo();
                                    }
                                    inputBusqueda.value = '';
                                });

                                btnRemove?.addEventListener('click', () => {
                                    const id = encontrarIdPorNombre(inputBusqueda.value);
                                    if (!id) {
                                        inputBusqueda.classList.add('is-invalid');
                                        return;
                                    }
                                    inputBusqueda.classList.remove('is-invalid');
                                    const antes = seleccionadas.length;
                                    seleccionadas = seleccionadas.filter(item => item !== id);
                                    if (antes !== seleccionadas.length) {
                                        renderTodo();
                                    }
                                    inputBusqueda.value = '';
                                });

                                inputBusqueda?.addEventListener('input', () => {
                                    inputBusqueda.classList.remove('is-invalid');
                                });

                                renderTodo();
                            })();
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mostrarPasswordForm): ?>
            <div class="card card-outline card-primary<?= $mostrarEspecialidadesForm ? '' : ' h-100' ?>">
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
            <div class="card card-outline card-secondary<?= ($mostrarPasswordForm || $mostrarEspecialidadesForm) ? ' mt-4' : ' h-100' ?>">
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
