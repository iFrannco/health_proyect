# 🩺 Historia de Usuario — **MED-PAC-001**

## Listado de pacientes con filtro y acción “Asignar diagnóstico”

### **ID**

`MED-PAC-001`

### **Título**

*Como médico, quiero visualizar un listado de todos los pacientes y poder filtrarlos por nombre, para seleccionar uno y asignarle un diagnóstico rápidamente.*

---

### **Descripción / Contexto**

El médico accede al módulo **Pacientes** desde el menú principal.
La vista muestra una **lista completa de pacientes registrados** en el sistema (activos e inactivos).
Puede **buscar** por nombre o apellido (texto parcial, sin distinguir mayúsculas/minúsculas).
Cada paciente tiene un botón **“Asignar diagnóstico”** que lleva al formulario de **nuevo diagnóstico**, donde el paciente queda **preseleccionado pero editable**.

---

### **Alcance**

**Incluye:**

* Listado paginado de **todos los pacientes registrados**.
* **Búsqueda por nombre o apellido** (contiene, insensible a mayúsculas/minúsculas).
* Acción por fila **“Asignar diagnóstico”** → abre Diagnósticos → Crear (`/medico/diagnosticos/nuevo`) con `paciente_id` preseleccionado.
* El paciente se muestra en el combo de la pantalla de diagnóstico, pero el médico **puede cambiarlo** antes de guardar.
* Botón o breadcrumb para **volver al listado de pacientes**.

**No incluye:**

* Alta, baja o modificación de pacientes.
* Filtros por otros campos (DNI, email, fecha de registro, etc.).
* Auditoría, exportación o visualización de historial clínico.

---

### **Actores y Permisos**

* **Médico**: puede listar y filtrar pacientes, y asignar diagnósticos.
* **Paciente / Administrador**: fuera de alcance.

---

### **Dependencias / Supuestos**

* Existe la vista de **Diagnóstico → Crear** que acepta el parámetro `paciente_id`.
* El sistema puede recuperar todos los usuarios con rol “paciente”.
* Si el paciente fue eliminado o no existe, se muestra mensaje de error y se retorna al listado.

---

### **Flujo principal**

1. El médico selecciona **“Pacientes”** en la barra lateral.
2. El sistema muestra una tabla con los siguientes campos:

   * **Apellido**, **Nombre**, **DNI** (si existe), y un botón **“Asignar diagnóstico”**.
3. El médico escribe parte del nombre o apellido para **filtrar**.
4. Al hacer clic en **“Asignar diagnóstico”**, el sistema redirige a la pantalla de **nuevo diagnóstico**, con el **paciente preseleccionado** en el formulario.
5. El médico puede **modificar** el paciente, completar el diagnóstico y guardarlo.
6. Si cancela o guarda, puede **volver al listado de pacientes** fácilmente.

---

### **Validaciones de negocio**

* Solo usuarios con rol **Médico** pueden acceder a este módulo.
* El filtro debe funcionar por coincidencias parciales y no ser sensible a mayúsculas/minúsculas.
* Si el paciente no existe o fue eliminado antes de la redirección, mostrar mensaje de error y volver al listado.

---

### **Criterios de aceptación**

**CA-1.** El médico puede ver un **listado completo de pacientes** registrados.
**CA-2.** El médico puede **filtrar** por nombre o apellido con coincidencias parciales.
**CA-3.** Cada fila incluye un botón **“Asignar diagnóstico”** que lleva al formulario de creación.
**CA-4.** El **paciente** seleccionado queda **prellenado** en el formulario, pero puede **cambiarse**.
**CA-5.** Si el paciente no está disponible (eliminado/inexistente), se informa con error y se retorna al listado.
**CA-6.** Debe existir una **navegación clara** de retorno al listado de pacientes.

---

### **Casos borde y errores**

* No hay pacientes registrados → mostrar “No se encontraron pacientes registrados.”
* Error de conexión o carga de datos → mensaje general de error.
* Paginación y filtro deben mantener consistencia (si se filtra y se cambia de página, se mantiene el filtro).


