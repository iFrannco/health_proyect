<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7 mx-auto">
        <div class="mb-3">
            <a href="<?= route_to('medico_pacientes_index') ?>" class="btn btn-link p-0 align-baseline">
                <i class="fas fa-arrow-left mr-1"></i> Volver al listado de pacientes
            </a>
        </div>
        <h1 class="mb-4">Nuevo diagnostico</h1>

        <?= view('layouts/partials/alerts') ?>

        <?php
        $errorList = $errors ?? [];
        $pacienteSeleccionado = $pacienteSeleccionado ?? null;
        $pacienteSeleccionadoId = $pacienteSeleccionadoId ?? null;
        $terminoBusquedaPaciente = $terminoBusquedaPaciente ?? '';
        $oldPacienteId = (int) old('paciente_id', $pacienteSeleccionadoId);
        $pacienteNombreSeleccionado = '';
        $pacienteDniSeleccionado = '';

        if ($pacienteSeleccionado !== null) {
            $pacienteNombreSeleccionado = trim(($pacienteSeleccionado->apellido ?? '') . ', ' . ($pacienteSeleccionado->nombre ?? ''));
            $pacienteDniSeleccionado = $pacienteSeleccionado->dni ?? '';
        }

        $tienePacienteSeleccionado = $oldPacienteId > 0 && $pacienteNombreSeleccionado !== '';
        ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">Registrar diagnostico</h3>
            </div>
            <form action="<?= route_to('medico_diagnosticos_store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="form-group">
                        <label for="busqueda_paciente">Paciente</label>
                        <input type="hidden" id="paciente_id" name="paciente_id" value="<?= $oldPacienteId > 0 ? esc($oldPacienteId) : '' ?>">
                        <div class="input-group">
                            <input
                                type="text"
                                name="busqueda_paciente"
                                id="busqueda_paciente"
                                class="form-control<?= isset($errorList['paciente_id']) ? ' is-invalid' : '' ?>"
                                placeholder="Ingresa nombre o DNI del paciente"
                                value="<?= esc($terminoBusquedaPaciente) ?>"
                                autocomplete="off"
                            >
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary" id="btn-buscar-paciente">
                                    <i class="fas fa-search mr-1"></i> Buscar
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Mínimo 2 caracteres para nombre o apellido, o al menos 4 dígitos para DNI. Se listan pacientes activos.
                        </small>
                        <?php if (isset($errorList['paciente_id'])): ?>
                            <span class="invalid-feedback d-block">
                                <?= esc($errorList['paciente_id']) ?>
                            </span>
                        <?php endif; ?>
                        <div id="paciente-seleccionado" class="alert alert-info bg-info text-white border-0 shadow-sm mt-3<?= $tienePacienteSeleccionado ? '' : ' d-none' ?>" role="alert">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong id="paciente-seleccionado-nombre"><?= esc($pacienteNombreSeleccionado) ?></strong>
                                    <div class="text-white small font-weight-semibold mb-0" id="paciente-seleccionado-dni">
                                        <?= $pacienteDniSeleccionado !== '' ? esc('DNI: ' . $pacienteDniSeleccionado) : '' ?>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light text-info font-weight-bold align-self-center" id="btn-limpiar-paciente">
                                    Cambiar paciente
                                </button>
                            </div>
                        </div>
                        <div id="pacientes-resultados" class="list-group mt-3"></div>
                    </div>

                    <div class="form-group">
                        <label for="tipo_diagnostico_id">Tipo de diagnostico</label>
                        <select
                            id="tipo_diagnostico_id"
                            name="tipo_diagnostico_id"
                            class="form-control<?= isset($errorList['tipo_diagnostico_id']) ? ' is-invalid' : '' ?>"
                            required
                        >
                            <option value="">Selecciona un tipo</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <?php
                                $tipoId     = (int) $tipo->id;
                                $isSelected = (int) old('tipo_diagnostico_id') === $tipoId;
                                ?>
                                <option value="<?= $tipoId ?>"<?= $isSelected ? ' selected' : '' ?>>
                                    <?= esc($tipo->nombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errorList['tipo_diagnostico_id'])): ?>
                            <span class="invalid-feedback d-block">
                                <?= esc($errorList['tipo_diagnostico_id']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripcion clinica</label>
                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="5"
                            class="form-control<?= isset($errorList['descripcion']) ? ' is-invalid' : '' ?>"
                            minlength="10"
                            maxlength="2000"
                            required
                        ><?= esc(old('descripcion')) ?></textarea>
                        <small class="form-text text-muted">
                            Debe contener entre 10 y 2000 caracteres.
                        </small>
                        <?php if (isset($errorList['descripcion'])): ?>
                            <span class="invalid-feedback d-block">
                                <?= esc($errorList['descripcion']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 d-flex flex-column flex-sm-row justify-content-end align-items-stretch align-items-sm-center">
                    <a
                        href="<?= route_to('medico_diagnosticos_index') ?>"
                        class="btn btn-outline-secondary w-100 w-sm-auto mb-2 mb-sm-0 me-sm-3 mr-sm-3"
                    >
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary w-100 w-sm-auto ms-sm-2 ml-sm-2">
                        Guardar diagnostico
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        const pacienteIdInput = document.getElementById('paciente_id');
        const busquedaPacienteInput = document.getElementById('busqueda_paciente');
        const btnBuscarPaciente = document.getElementById('btn-buscar-paciente');
        const resultadosPacientes = document.getElementById('pacientes-resultados');
        const pacienteSeleccionadoAlert = document.getElementById('paciente-seleccionado');
        const pacienteSeleccionadoNombre = document.getElementById('paciente-seleccionado-nombre');
        const pacienteSeleccionadoDni = document.getElementById('paciente-seleccionado-dni');
        const btnLimpiarPaciente = document.getElementById('btn-limpiar-paciente');
        const endpointPacientes = '<?= route_to('medico_planes_buscar_pacientes') ?>';
        const scopeBusqueda = 'diagnosticos';

        function limpiarResultados() {
            if (resultadosPacientes) {
                resultadosPacientes.innerHTML = '';
            }
        }

        function mostrarResultados(pacientes) {
            if (! resultadosPacientes) {
                return;
            }

            resultadosPacientes.innerHTML = '';

            if (! Array.isArray(pacientes) || pacientes.length === 0) {
                resultadosPacientes.innerHTML = '<div class="list-group-item text-muted">Sin resultados para el criterio ingresado.</div>';

                return;
            }

            pacientes.forEach((paciente) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                item.dataset.id = paciente.id;
                item.dataset.nombre = paciente.nombre;
                item.dataset.apellido = paciente.apellido;
                item.dataset.dni = paciente.dni || '';

                const nombreCompleto = (paciente.apellido ? paciente.apellido + ', ' : '') + (paciente.nombre || '');
                const dniTexto = paciente.dni ? 'DNI: ' + paciente.dni : 'DNI no informado';

                item.innerHTML = '<div><div class="font-weight-bold">' + nombreCompleto + '</div><div class="small text-muted mb-0">' + dniTexto + '</div></div><span class="badge badge-light">Seleccionar</span>';
                item.addEventListener('click', () => seleccionarPaciente(paciente));

                resultadosPacientes.appendChild(item);
            });
        }

        function seleccionarPaciente(paciente) {
            if (pacienteIdInput) {
                pacienteIdInput.value = paciente.id || '';
            }

            const nombreCompleto = (paciente.apellido ? paciente.apellido + ', ' : '') + (paciente.nombre || '');
            const dniTexto = paciente.dni ? 'DNI: ' + paciente.dni : '';

            if (busquedaPacienteInput) {
                busquedaPacienteInput.value = nombreCompleto;
            }

            if (pacienteSeleccionadoNombre) {
                pacienteSeleccionadoNombre.textContent = nombreCompleto;
            }

            if (pacienteSeleccionadoDni) {
                pacienteSeleccionadoDni.textContent = dniTexto;
            }

            if (pacienteSeleccionadoAlert) {
                pacienteSeleccionadoAlert.classList.remove('d-none');
            }

            limpiarResultados();
        }

        function limpiarPacienteSeleccionado() {
            if (pacienteIdInput) {
                pacienteIdInput.value = '';
            }

            if (busquedaPacienteInput) {
                busquedaPacienteInput.value = '';
            }

            if (pacienteSeleccionadoNombre) {
                pacienteSeleccionadoNombre.textContent = '';
            }

            if (pacienteSeleccionadoDni) {
                pacienteSeleccionadoDni.textContent = '';
            }

            if (pacienteSeleccionadoAlert) {
                pacienteSeleccionadoAlert.classList.add('d-none');
            }

            limpiarResultados();
        }

        async function buscarPacientes() {
            if (! busquedaPacienteInput) {
                return;
            }

            const termino = busquedaPacienteInput.value.trim();
            const soloDigitos = termino.replace(/\D+/g, '');

            if (termino.length < 2) {
                if (resultadosPacientes) {
                    resultadosPacientes.innerHTML = '<div class="list-group-item text-warning">Ingresa al menos 2 caracteres para buscar.</div>';
                }

                return;
            }

            if (soloDigitos !== '' && soloDigitos.length < 4) {
                if (resultadosPacientes) {
                    resultadosPacientes.innerHTML = '<div class="list-group-item text-warning">Ingresa al menos 4 dígitos para buscar por DNI.</div>';
                }

                return;
            }

            try {
                const url = endpointPacientes + '?q=' + encodeURIComponent(termino) + '&scope=' + encodeURIComponent(scopeBusqueda);
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const payload = await response.json();

                if (! response.ok || ! payload.success) {
                    limpiarResultados();

                    if (resultadosPacientes) {
                        const mensaje = payload && payload.message ? payload.message : 'No se pudo realizar la búsqueda.';
                        resultadosPacientes.innerHTML = '<div class="list-group-item text-danger">' + mensaje + '</div>';
                    }

                    return;
                }

                mostrarResultados(payload.data && payload.data.pacientes ? payload.data.pacientes : []);
            } catch (error) {
                limpiarResultados();

                if (resultadosPacientes) {
                    resultadosPacientes.innerHTML = '<div class="list-group-item text-danger">No se pudo realizar la búsqueda. Intenta nuevamente.</div>';
                }
            }
        }

        if (btnBuscarPaciente) {
            btnBuscarPaciente.addEventListener('click', buscarPacientes);
        }

        if (busquedaPacienteInput) {
            busquedaPacienteInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    buscarPacientes();
                }
            });
        }

        if (btnLimpiarPaciente) {
            btnLimpiarPaciente.addEventListener('click', limpiarPacienteSeleccionado);
        }
    })();
</script>
<?= $this->endSection() ?>
