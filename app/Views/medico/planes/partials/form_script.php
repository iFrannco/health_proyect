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
        const cardActividadesManuales = document.getElementById('card-actividades-manuales');
        const cardActividadesEstandar = document.getElementById('card-actividades-estandar');
        const alertaActividadesEstandar = document.getElementById('alerta-actividades-estandar');
        const listaActividadesEstandar = document.getElementById('lista-actividades-estandar');
        const planEstandarSelector = document.getElementById('plan_estandar_selector');
        const planEstandarHidden = document.getElementById('plan_estandar_id_hidden');
        const planResumen = document.getElementById('plan-estandar-resumen');
        const planResumenNombre = document.getElementById('plan-estandar-nombre');
        const planResumenVersion = document.getElementById('plan-estandar-version');
        const planResumenDescripcion = document.getElementById('plan-estandar-descripcion');
        const planResumenEstado = document.getElementById('plan-estandar-estado');
        const fechaInicioInput = document.getElementById('fecha_inicio');
        const fechaFinInput = document.getElementById('fecha_fin');
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

            if (!hayDiagnosticos) {
                resetPlanEstandar();
            }
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

        function resetPlanEstandar() {
            if (planEstandarHidden) {
                planEstandarHidden.value = '';
            }
            if (planEstandarSelector) {
                planEstandarSelector.value = '';
                planEstandarSelector.innerHTML = '<option value=\"\">Plan personalizado (sin plantilla)</option>';
                planEstandarSelector.disabled = true;
            }
            if (planResumen) {
                planResumen.classList.add('d-none');
            }
            if (planResumenNombre) {
                planResumenNombre.textContent = '';
            }
            if (planResumenDescripcion) {
                planResumenDescripcion.textContent = '';
            }
            if (planResumenVersion) {
                planResumenVersion.textContent = '';
            }
            if (planResumenEstado) {
                planResumenEstado.textContent = '';
            }
            if (cardActividadesManuales) {
                cardActividadesManuales.classList.remove('d-none');
            }
            if (cardActividadesEstandar) {
                cardActividadesEstandar.classList.add('d-none');
            }
            if (listaActividadesEstandar) {
                listaActividadesEstandar.innerHTML = '';
            }
            if (alertaActividadesEstandar) {
                alertaActividadesEstandar.classList.remove('d-none');
            }
        }

        function activarModoPlantilla(plan) {
            if (planEstandarHidden) {
                planEstandarHidden.value = plan.id || '';
            }

            if (planResumen) {
                planResumen.classList.remove('d-none');
            }
            if (planResumenNombre) {
                planResumenNombre.textContent = plan.nombre || 'Plan estándar';
            }
            if (planResumenDescripcion) {
                planResumenDescripcion.textContent = plan.descripcion || '';
            }
            if (planResumenVersion) {
                planResumenVersion.textContent = plan.version ? 'Versión ' + plan.version : '';
            }
            if (planResumenEstado) {
                planResumenEstado.textContent = 'Plantilla vigente';
            }
            if (cardActividadesManuales) {
                cardActividadesManuales.classList.add('d-none');
            }
            if (cardActividadesEstandar) {
                cardActividadesEstandar.classList.remove('d-none');
            }
            if (fechaFinInput) {
                fechaFinInput.readOnly = true;
            }
        }

        function activarModoPersonalizado() {
            if (planEstandarHidden) {
                planEstandarHidden.value = '';
            }
            if (planResumen) {
                planResumen.classList.add('d-none');
            }
            if (cardActividadesManuales) {
                cardActividadesManuales.classList.remove('d-none');
            }
            if (cardActividadesEstandar) {
                cardActividadesEstandar.classList.add('d-none');
            }
            if (listaActividadesEstandar) {
                listaActividadesEstandar.innerHTML = '';
            }
            if (alertaActividadesEstandar) {
                alertaActividadesEstandar.classList.remove('d-none');
            }
            if (fechaFinInput) {
                fechaFinInput.readOnly = false;
            }
        }

        function renderActividadesEstandar(actividades) {
            if (!listaActividadesEstandar) {
                return;
            }

            listaActividadesEstandar.innerHTML = '';

            if (!Array.isArray(actividades) || actividades.length === 0) {
                if (alertaActividadesEstandar) {
                    alertaActividadesEstandar.classList.remove('d-none');
                }
                return;
            }

            if (alertaActividadesEstandar) {
                alertaActividadesEstandar.classList.add('d-none');
            }

            actividades.forEach((actividad, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'border rounded p-3 mb-2';
                wrapper.innerHTML = `
                    <div class=\"d-flex justify-content-between align-items-start\">
                        <div>
                            <strong>${actividad.nombre || 'Actividad'}</strong>
                            <div class=\"small text-muted mb-1\">${actividad.descripcion || ''}</div>
                        </div>
                        <span class=\"badge badge-light\">Actividad ${index + 1}</span>
                    </div>
                    <div class=\"text-muted small mb-0\">
                        ${(actividad.fecha_inicio || '-')} → ${(actividad.fecha_fin || '-')}
                    </div>
                `;
                listaActividadesEstandar.appendChild(wrapper);
            });
        }

        async function cargarPlanesEstandarPorDiagnostico() {
            if (!planEstandarSelector) {
                return;
            }

            const diagnosticoId = diagnosticoSelect ? diagnosticoSelect.value : '';
            const pacienteId = pacienteIdInput ? pacienteIdInput.value : '';
            const planPersistido = planEstandarHidden ? planEstandarHidden.value : '';

            planEstandarSelector.innerHTML = '<option value=\"\">Plan personalizado (sin plantilla)</option>';

            if (!diagnosticoId) {
                planEstandarSelector.disabled = true;
                if (planPersistido) {
                    activarModoPlantilla({ id: planPersistido, nombre: 'Plan estándar' });
                } else {
                    activarModoPersonalizado();
                }
                return;
            }

            try {
                const url = '<?= route_to('medico_planes_plantillas_diagnostico') ?>?diagnostico_id=' + encodeURIComponent(diagnosticoId) + '&paciente_id=' + encodeURIComponent(pacienteId);
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    planEstandarSelector.disabled = true;
                    if (planPersistido) {
                        activarModoPlantilla({ id: planPersistido, nombre: 'Plan estándar' });
                    } else {
                        activarModoPersonalizado();
                    }
                    return;
                }

                const seleccionado = planEstandarHidden ? planEstandarHidden.value : '';

                (payload.data && payload.data.planes ? payload.data.planes : []).forEach((plan) => {
                    const option = document.createElement('option');
                    option.value = plan.id;
                    option.textContent = plan.nombre || 'Plan estándar';
                    option.dataset.descripcion = plan.descripcion || '';
                    option.dataset.version = plan.version || '';
                    if (seleccionado && String(seleccionado) === String(plan.id)) {
                        option.selected = true;
                    }
                    planEstandarSelector.appendChild(option);
                });

                planEstandarSelector.disabled = false;

                if (planEstandarSelector.value) {
                    await previsualizarPlanEstandar();
                } else if (planPersistido) {
                    activarModoPlantilla({ id: planPersistido, nombre: 'Plan estándar' });
                }

                if (planEstandarSelector.dataset.locked === '1') {
                    planEstandarSelector.disabled = true;
                }
            } catch (error) {
                planEstandarSelector.disabled = true;
                if (planPersistido) {
                    activarModoPlantilla({ id: planPersistido, nombre: 'Plan estándar' });
                } else {
                    activarModoPersonalizado();
                }
            }
        }

        async function previsualizarPlanEstandar() {
            const planId = planEstandarSelector && planEstandarSelector.value
                ? planEstandarSelector.value
                : (planEstandarHidden ? planEstandarHidden.value : '');
            const diagnosticoId = diagnosticoSelect ? diagnosticoSelect.value : '';
            const pacienteId = pacienteIdInput ? pacienteIdInput.value : '';
            const fechaInicio = fechaInicioInput ? fechaInicioInput.value : '';
            const fechaFin = fechaFinInput ? fechaFinInput.value : '';

            if (!planId) {
                activarModoPersonalizado();
                return;
            }

            if (!fechaInicio) {
                activarModoPlantilla({ id: planId, nombre: planEstandarSelector.options[planEstandarSelector.selectedIndex]?.textContent || 'Plan estándar' });
                renderActividadesEstandar([]);
                if (alertaActividadesEstandar) {
                    alertaActividadesEstandar.classList.remove('d-none');
                    alertaActividadesEstandar.textContent = 'Selecciona la fecha de inicio para calcular las actividades y la fecha fin.';
                }
                return;
            }

            try {
                const response = await fetch('<?= route_to('medico_planes_previsualizar') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        plan_estandar_id: planId,
                        diagnostico_id: diagnosticoId,
                        paciente_id: pacienteId,
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin
                    }).toString()
                });

                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    activarModoPlantilla({ id: planId, nombre: planEstandarSelector.options[planEstandarSelector.selectedIndex]?.textContent || 'Plan estándar' });
                    renderActividadesEstandar([]);
                    if (alertaActividadesEstandar) {
                        alertaActividadesEstandar.textContent = (payload && payload.message) ? payload.message : 'No se pudieron generar las actividades.';
                        alertaActividadesEstandar.classList.remove('d-none');
                    }
                    return;
                }

                const plan = payload.data && payload.data.plan ? payload.data.plan : { id: planId };
                activarModoPlantilla(plan);
                renderActividadesEstandar(payload.data && payload.data.actividades ? payload.data.actividades : []);
                const fechaFinCalculada = payload.data && payload.data.fecha_fin_calculada ? payload.data.fecha_fin_calculada : '';
                if (fechaFinInput && fechaFinCalculada) {
                    fechaFinInput.value = fechaFinCalculada;
                }
            } catch (error) {
                activarModoPlantilla({ id: planId, nombre: planEstandarSelector && planEstandarSelector.options[planEstandarSelector.selectedIndex] ? planEstandarSelector.options[planEstandarSelector.selectedIndex].textContent : 'Plan estándar' });
                renderActividadesEstandar([]);
                if (alertaActividadesEstandar) {
                    alertaActividadesEstandar.textContent = 'No se pudieron generar las actividades. Intenta nuevamente.';
                    alertaActividadesEstandar.classList.remove('d-none');
                }
            }
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
            resetPlanEstandar();
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

            resetPlanEstandar();
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
            actividad.querySelectorAll('input, textarea, select').forEach((campo) => {
                if (campo.tagName === 'SELECT') {
                    campo.selectedIndex = 0;
                    return;
                }

                campo.value = '';
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

        if (diagnosticoSelect) {
            diagnosticoSelect.addEventListener('change', () => {
                cargarPlanesEstandarPorDiagnostico();
            });
        }

        if (planEstandarSelector) {
            planEstandarSelector.addEventListener('change', () => {
                const valor = planEstandarSelector.value;
                if (planEstandarHidden) {
                    planEstandarHidden.value = valor;
                }
                if (!valor) {
                    activarModoPersonalizado();
                    return;
                }
                previsualizarPlanEstandar();
            });
        }

        if (fechaInicioInput) {
            fechaInicioInput.addEventListener('change', previsualizarPlanEstandar);
        }

        if (fechaFinInput) {
            fechaFinInput.addEventListener('change', previsualizarPlanEstandar);
        }

        if (planEstandarHidden && planEstandarHidden.value) {
            cargarPlanesEstandarPorDiagnostico();
        }
    })();
</script>
