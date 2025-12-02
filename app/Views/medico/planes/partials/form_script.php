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
        const diagnosticoSelect = document.getElementById('diagnostico_id');
        const btnAgregarActividad = document.getElementById('btn-agregar-actividad');
        const contenedorActividades = document.getElementById('contenedor-actividades');
        const templateEl = document.getElementById('actividad-template');
        const template = templateEl ? templateEl.innerHTML : '';

        function filtrarDiagnosticosPorPaciente() {
            if (! diagnosticoSelect) {
                return;
            }

            const pacienteId = pacienteIdInput ? pacienteIdInput.value : '';
            let hayDiagnosticos = false;

            Array.from(diagnosticoSelect.options).forEach((option) => {
                if (!option.dataset.pacienteId) {
                    return;
                }

                const coincide = pacienteId && option.dataset.pacienteId === pacienteId;
                option.hidden = !coincide;

                if (!coincide && option.selected) {
                    option.selected = false;
                }

                if (coincide) {
                    hayDiagnosticos = true;
                }
            });

            diagnosticoSelect.disabled = !hayDiagnosticos;
        }

        function limpiarResultados() {
            if (resultadosPacientes) {
                resultadosPacientes.innerHTML = '';
            }
        }

        function mostrarResultados(pacientes) {
            if (!resultadosPacientes) {
                return;
            }

            resultadosPacientes.innerHTML = '';

            if (!Array.isArray(pacientes) || pacientes.length === 0) {
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

            if (diagnosticoSelect) {
                diagnosticoSelect.value = '';
                diagnosticoSelect.disabled = false;
            }

            filtrarDiagnosticosPorPaciente();
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

            if (diagnosticoSelect) {
                diagnosticoSelect.value = '';
                diagnosticoSelect.disabled = true;
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
                const response = await fetch('<?= route_to('medico_planes_buscar_pacientes') ?>?q=' + encodeURIComponent(termino), {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const payload = await response.json();

                if (!response.ok || !payload.success) {
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

        function actualizarNumeracionActividades() {
            if (! contenedorActividades) {
                return;
            }

            const items = contenedorActividades.querySelectorAll('.actividad-item');
            items.forEach((item, index) => {
                item.dataset.index = index.toString();
                const titulo = item.querySelector('.card-title');
                if (titulo) {
                    titulo.textContent = 'Actividad ' + (index + 1);
                }
            });
        }

        function agregarActividad() {
            if (! contenedorActividades || ! template) {
                return;
            }

            const indice = contenedorActividades.querySelectorAll('.actividad-item').length;
            const html = template
                .replace(/__index__/g, indice.toString())
                .replace(/__numero__/g, (indice + 1).toString());

            const contenedor = document.createElement('div');
            contenedor.innerHTML = html.trim();
            const elemento = contenedor.firstChild;
            if (! elemento) {
                return;
            }

            contenedorActividades.appendChild(elemento);
            actualizarNumeracionActividades();
        }

        function limpiarActividadCampos(actividad) {
            actividad.querySelectorAll('input, textarea').forEach((campo) => {
                if (campo.type === 'hidden') {
                    campo.value = '';
                } else {
                    campo.value = '';
                }
            });
        }

        function removerActividad(event) {
            const boton = event.target.closest('.btn-remover-actividad');
            if (!boton) {
                return;
            }

            const actividad = boton.closest('.actividad-item');
            if (!actividad) {
                return;
            }

            const total = contenedorActividades ? contenedorActividades.querySelectorAll('.actividad-item').length : 0;
            if (total === 1) {
                limpiarActividadCampos(actividad);

                return;
            }

            actividad.remove();
            actualizarNumeracionActividades();
        }

        filtrarDiagnosticosPorPaciente();

        if (btnAgregarActividad) {
            btnAgregarActividad.addEventListener('click', agregarActividad);
        }

        if (contenedorActividades) {
            contenedorActividades.addEventListener('click', removerActividad);
        }

        if (btnBuscarPaciente) {
            btnBuscarPaciente.addEventListener('click', buscarPacientes);
        }

        if (busquedaPacienteInput) {
            busquedaPacienteInput.addEventListener('keypress', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    buscarPacientes();
                }
            });
        }

        if (btnLimpiarPaciente) {
            btnLimpiarPaciente.addEventListener('click', limpiarPacienteSeleccionado);
        }

        if (pacienteIdInput && pacienteIdInput.value && diagnosticoSelect) {
            diagnosticoSelect.disabled = false;
            filtrarDiagnosticosPorPaciente();
        }
    })();
</script>
