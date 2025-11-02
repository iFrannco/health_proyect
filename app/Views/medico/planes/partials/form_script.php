<script>
    (function () {
        const pacienteSelect = document.getElementById('paciente_id');
        const diagnosticoSelect = document.getElementById('diagnostico_id');
        const btnAgregarActividad = document.getElementById('btn-agregar-actividad');
        const contenedorActividades = document.getElementById('contenedor-actividades');
        const templateEl = document.getElementById('actividad-template');
        const template = templateEl ? templateEl.innerHTML : '';

        if (! contenedorActividades || ! template) {
            return;
        }

        function filtrarDiagnosticosPorPaciente() {
            if (! pacienteSelect || ! diagnosticoSelect) {
                return;
            }

            const pacienteId = pacienteSelect.value;
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

        function actualizarNumeracionActividades() {
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

            const total = contenedorActividades.querySelectorAll('.actividad-item').length;
            if (total === 1) {
                limpiarActividadCampos(actividad);

                return;
            }

            actividad.remove();
            actualizarNumeracionActividades();
        }

        if (pacienteSelect) {
            filtrarDiagnosticosPorPaciente();
            pacienteSelect.addEventListener('change', filtrarDiagnosticosPorPaciente);
        }

        if (btnAgregarActividad) {
            btnAgregarActividad.addEventListener('click', agregarActividad);
        }

        contenedorActividades.addEventListener('click', removerActividad);
    })();
</script>
