# 👤 Historia de Usuario — **PAC-PLAN-001**

## Visualizar y actualizar planes de cuidado asignados al paciente

### **ID**

`PAC-PLAN-001`

### **Título**

*Como paciente, quiero visualizar mis planes de cuidado y marcar las actividades como realizadas, para llevar un seguimiento de mi progreso.*

---

### **Descripción / Contexto**

El paciente necesita poder acceder a los planes de cuidado que le fueron asignados por su médico.
Desde esta sección podrá visualizar los planes activos, finalizados y futuros, revisar sus detalles y marcar el progreso de las actividades asociadas, con el fin de mantener actualizada la información que luego será validada por el médico responsable.

---

### **Alcance**

**Incluye:**

* Visualización de todos los planes de cuidado del paciente.
* Filtro por estado: **Activos / Futuros / Finalizados / Todos**.
* Acceso al detalle del plan seleccionado (diagnóstico, descripción, fechas, métricas generales).
* Listado de actividades con los siguientes datos:

  * Nombre
  * Descripción
  * Fecha inicio y fin
  * Estado (pendiente / completada / vencida)
  * Estado de validación (pendiente / validada)
* Posibilidad de **marcar una actividad como realizada**, con opción de agregar un comentario.
* Posibilidad de **desmarcar una actividad** ya marcada como realizada.
* Bloqueo para marcar una actividad:

  * Antes de su fecha de inicio.
    *(// Comentario: Validación revisable si se desea permitir cumplimiento anticipado.)*
  * Después de su fecha de fin.
    *(// Comentario: Validación revisable si se desea permitir registro tardío.)*
* Visualización de **métricas de avance** del plan (actividades totales, pendientes, completadas, vencidas, validadas).
* Interfaz coherente con el diseño del médico, con checkbox/botones para marcar estado de actividades.

**No incluye:**

* Creación, edición o eliminación de planes o actividades.
* Validación de actividades (solo puede hacerlo el médico).
* Comentarios del médico sobre el cumplimiento (ver historia futura PAC-PLAN-002 si se agrega feedback bidireccional).

---

### **Actores y Permisos**

* **Paciente:** puede visualizar sus planes y actualizar el estado de sus actividades.
* **Médico / Administrador:** no acceden a esta vista desde el módulo del paciente.

---

### **Dependencias / Supuestos**

* Existe relación entre `Usuario (paciente)` → `Diagnóstico` → `PlanDeCuidado` → `Actividad`.
* Las validaciones de actividades realizadas son procesadas posteriormente por el médico.
* El sistema gestiona correctamente los estados de plan y actividad (`pendiente`, `completada`, `vencida`, `validada`).

---

### **Flujo principal**

1. El paciente accede al menú lateral → **“Planes de cuidado”**.
2. Se muestra un listado de todos los planes asignados con filtro por estado (activo / finalizado / futuro).
3. El paciente selecciona un plan → se abre la vista de **detalle del plan**.
4. Se muestran:

   * Información general (paciente, diagnóstico, fechas, descripción).
   * Métricas de avance (actividades totales, pendientes, completadas, vencidas, validadas).
   * Tabla/listado de actividades con checkbox o botón de “Marcar como realizada”.
5. El paciente puede:

   * Marcar una actividad como **realizada** (con o sin comentario).
   * Desmarcar una actividad marcada previamente.
6. El paciente puede volver al listado general de planes en cualquier momento.

---

### **Criterios de aceptación**

**CA-1.** El paciente puede visualizar todos los planes que le fueron asignados.
**CA-2.** Puede filtrar los planes por estado (activo, futuro, finalizado).
**CA-3.** En el detalle de un plan, se muestran las métricas generales (actividades totales, pendientes, completadas, vencidas, validadas).
**CA-4.** Cada actividad puede ser marcada como realizada o desmarcada, respetando las fechas de inicio y fin.
**CA-5.** El paciente puede agregar opcionalmente un comentario al marcar una actividad.
**CA-6.** Los cambios se reflejan inmediatamente en la interfaz y se guardan en el sistema.
**CA-7.** El diseño visual mantiene coherencia con la vista del médico, pero sin botones de edición o eliminación.
**CA-8.** Si no existen planes asignados, se muestra un mensaje “No tenés planes de cuidado activos por el momento.”

---

### **Casos borde y errores**

* Paciente sin planes asignados → mensaje vacío.
* Intento de marcar actividad fuera de rango de fechas → mostrar mensaje de error.
* Error al actualizar estado → revertir cambio visual y mostrar aviso.
* El médico elimina un plan → dejar de mostrarlo en la vista del paciente.
