# 🩺 Historia de Usuario — MED-DIAG-002

## Buscador de paciente reutilizable en nuevo diagnóstico

### **ID**

`MED-DIAG-002`

### **Título**

*Como médico, quiero buscar y seleccionar un paciente al crear un nuevo diagnóstico usando el mismo buscador que se utiliza para vincular un plan de cuidado, para evitar depender de un listado desplegable y reducir errores de selección.*

---

### **Descripción / Contexto**

En la pestaña **Diagnósticos**, el flujo **“Nuevo diagnóstico”** reemplaza el selector desplegable de pacientes por el **mismo buscador** ya usado al vincular un paciente con un plan de cuidado. El médico puede ingresar parte del nombre o DNI y ver coincidencias con la misma lógica, layout y mensajes actuales del componente reutilizado. Los resultados incluyen a **todos los pacientes activos con rol paciente**. Si no hay coincidencias, se muestra el mensaje estándar “Sin resultados para el criterio ingresado.” sin ofrecer creación de nuevos pacientes.

---

### **Alcance**

**Incluye:**

* Sustitución del selector de paciente en **Diagnósticos → Nuevo** por el buscador reutilizado de planes de cuidado (misma API, normalización y presentación).
* Búsqueda por los mismos criterios soportados en el componente reutilizado (nombre y/o DNI con coincidencia parcial e insensible a mayúsculas).
* Resultados con **todos los pacientes activos con rol paciente**.
* Mensaje de **“Sin resultados para el criterio ingresado.”** cuando no hay coincidencias, idéntico al componente original.
* Selección de un paciente desde resultados para completar el formulario de diagnóstico sin alterar el resto del flujo.
* Accesibilidad y navegación por teclado equivalentes al componente existente.

**No incluye:**

* Alta o edición de pacientes desde este flujo.
* Cambios en la lógica de guardado del diagnóstico (campos, validaciones o persistencia ya definidos en MED-DIAG-001).
* Ajustes al buscador en el módulo de planes de cuidado más allá de su reutilización.
* Búsquedas por atributos adicionales no soportados por el componente (edad, cobertura, etc.).

---

### **Actores y Permisos**

* **Médico** autenticado: puede iniciar un diagnóstico y buscar entre todos los pacientes activos.
* **Administrador / Paciente**: fuera de alcance para este flujo.

---

### **Dependencias / Supuestos**

* Existe el componente de búsqueda de pacientes utilizado al vincular un plan de cuidado (ver MED-PLAN-003) y su API está disponible.
* El formulario de **nuevo diagnóstico** ya existe y mantiene sus validaciones y campos actuales.
* El médico está autenticado y autorizado para crear diagnósticos.
* Los pacientes listados están activos y con rol paciente.

---

### **Flujo principal**

1. El médico navega a **Diagnósticos → Nuevo** desde la pestaña de diagnósticos.
2. El formulario muestra el campo de paciente como buscador reutilizado del flujo de plan de cuidado, en lugar de un selector desplegable.
3. El médico escribe parte del nombre o DNI del paciente.
4. El sistema valida el término según las reglas del componente y consulta pacientes con rol paciente y `activo = true`.
5. Se muestran las coincidencias con el mismo formato (nombre completo, DNI, datos mínimos) usado en el buscador de planes de cuidado.
6. El médico selecciona un paciente de la lista de resultados.
7. El paciente queda asignado en el formulario de diagnóstico y se habilita continuar con el resto de campos (tipo de diagnóstico, descripción, etc.).
8. Si no hay coincidencias, se muestra “Sin resultados para el criterio ingresado.” y el médico puede ajustar el criterio sin salir del formulario.
9. Al confirmar el diagnóstico, se reutiliza el flujo de guardado existente de MED-DIAG-001.

---

### **Validaciones de negocio**

* El buscador aplica exactamente las mismas reglas de normalización, coincidencia parcial y manejo de mayúsculas que el componente de planes de cuidado.
* Solo se listan usuarios con **rol paciente** y `activo = true`.
* El término de búsqueda debe cumplir la longitud mínima y formato admitido por el componente reutilizado (ej. mínimo de caracteres para nombre o patrón numérico para DNI).
* Debe seleccionarse **un único paciente** antes de permitir guardar el diagnóstico.
* Si el paciente se vuelve inactivo entre la búsqueda y la selección, se invalida la selección y se solicita nueva búsqueda.
* El mensaje de “Sin resultados para el criterio ingresado.” se muestra solo cuando la consulta válida no retorna coincidencias.

---

### **Criterios de aceptación**

**CA-1.** El campo de paciente en **Nuevo diagnóstico** usa el mismo componente de búsqueda que el flujo de vincular paciente a plan de cuidado (misma UI, mensajes y API).  
**CA-2.** La búsqueda permite los mismos criterios que el componente original (nombre y/o DNI, coincidencia parcial, insensible a mayúsculas).  
**CA-3.** Los resultados muestran pacientes con rol paciente y `activo = true`.  
**CA-4.** Al seleccionar un paciente, el valor queda fijado en el formulario y habilita continuar con el registro del diagnóstico sin pasos adicionales.  
**CA-5.** Si no hay coincidencias, se muestra exactamente el mensaje “Sin resultados para el criterio ingresado.” y el médico puede reintentar.  
**CA-6.** No se ofrece creación ni edición de pacientes desde este campo.  
**CA-7.** Navegación y selección por teclado funcionan igual que en el componente reutilizado (enfoque, desplazamiento por resultados, confirmación).  
**CA-8.** El resto del flujo de alta de diagnóstico (validaciones y persistencia) permanece sin cambios respecto a MED-DIAG-001.

---

### **Casos borde y errores**

* No hay pacientes activos → la búsqueda retorna vacío y muestra “Sin resultados para el criterio ingresado.” hasta que existan pacientes elegibles.
* Entrada con menos caracteres que el mínimo permitido o DNI con formato inválido → feedback inmediato y no se ejecuta la consulta.
* Paciente mostrado pero que pasa a inactivo antes de la selección → la selección se invalida y se solicita nueva búsqueda.
* Falla en la consulta al servicio de pacientes → mensaje genérico de error sin borrar datos ya cargados en el formulario.

### **Conclusión**

La historia sustituye el selector estático de paciente en **Nuevo diagnóstico** por el buscador reutilizado de planes de cuidado, asegurando consistencia UX y reducción de errores de selección con acceso a todos los pacientes activos, sin alterar el flujo de alta ya definido.
