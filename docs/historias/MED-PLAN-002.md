# 🩺 Historia de Usuario — **MED-PLAN-002**

## Visualizar y gestionar un plan de cuidado personalizado

### **ID**

`MED-PLAN-002`

### **Título**

*Como médico, quiero visualizar y gestionar (editar o eliminar) un plan de cuidado personalizado que creé, para mantener actualizados sus datos y actividades clínicas.*

---

### **Descripción / Contexto**

El médico accede al **detalle** de un plan de cuidado personalizado (propio) desde el **listado de planes**. La vista muestra los **datos clave** del plan (fechas, paciente, diagnóstico) y el **listado de actividades** con su estado y validación. Desde esta pantalla, el médico puede:

* **Editar** el plan, reutilizando el formulario de creación con los datos pre-cargados para modificar fechas, nombre/ descripción y gestionar sus actividades (agregar, editar o eliminar).
* **Eliminar** el plan, confirmando la baja definitiva en cascada de todas las actividades vinculadas.

---

### **Alcance**

**Incluye:**

* Visualización de **plan propio**: paciente, diagnóstico, fechas, KPIs de actividades.
* Listado de **actividades** del plan con columnas: nombre, descripción, inicio, fin, estado, validado.
* Botón **Editar** → abre el formulario de planificación con datos **pre-cargados** para actualizar plan y actividades.
* Edición de datos del plan: nombre (opcional), descripción (opcional), fecha de inicio y fin.
* Gestión de actividades dentro de la edición: crear nuevas, actualizar existentes y eliminar actividades.
* Reglas al editar actividades validadas: si se modifican, se restablece `validado = NULL/false` y `estado_id = pendiente`.
* Botón **Eliminar** → popup de confirmación estilizado con AdminLTE → baja **definitiva** con **cascada** de actividades.
* Navegación de retorno al listado y al detalle desde el formulario de edición.

**No incluye:**

* Cambiar el diagnóstico asociado al plan.
* Versionado/historial del plan o de sus actividades.
* Reglas clínicas avanzadas (dependencias entre actividades).

---

### **Actores y Permisos**

* **Médico**: puede visualizar y modificar **solo planes creados por él** (`creador_user_id` = médico en sesión).
* **Paciente/Administrador**: fuera de alcance.

---

### **Dependencias / Supuestos**

* El médico está autenticado y el sistema conoce su `user_id`.
* El plan existe y es **propiedad del médico**.
* Catálogo `estado_actividad` vigente (`pendiente`, `completada`, `vencida`).
* Botón **Editar** reutiliza la **pantalla de creación** de plan personalizado con datos existentes (plan + actividades).
* Botón **Eliminar** ejecuta baja definitiva con **eliminación en cascada** de actividades.
* Catálogo `estado_actividad` vigente (`pendiente`, `completada`, `vencida`).
* Persistencia sin versionado: la edición actualiza el registro existente.

---

### **Flujo principal**

1. El médico ingresa al **listado** de sus planes y selecciona un **plan**.
2. El sistema muestra la vista **“Ver plan”** con:

   * Encabezado (paciente, diagnóstico, fechas).
   * KPIs de actividades (totales por estado y validadas).
   * Tabla de **actividades** (nombre, descripción corta, fechas, estado, validado).
3. El médico puede:

   * Presionar **Editar**: se abre el formulario con datos **pre-cargados** del plan y sus actividades.
   * Presionar **Eliminar**: se abre una **confirmación modal estilizada**, y al aceptar se elimina el plan y sus actividades; el sistema retorna al listado con mensaje de éxito.
4. En la edición, el médico puede:

   * Actualizar los datos del plan (nombre opcional, descripción opcional, fecha de inicio y fin).
   * Agregar nuevas actividades (nombre, descripción, fechas) → se crean con estado `pendiente` y `validado = NULL/false`.
   * Editar actividades existentes. Si una actividad estaba validada y se modifica, el sistema restablece `validado = NULL/false` y `estado_id = pendiente`.
   * Eliminar actividades del plan.
5. Al guardar, el sistema valida los datos, persiste los cambios y muestra confirmación. Al cancelar, se regresa al detalle o listado sin modificar datos.
6. En cualquier momento, el médico puede **volver** al listado desde los botones de navegación.

---

### **Validaciones de negocio**

* El plan debe **pertenecer** al médico en sesión; si no, se deniega el acceso.
* Eliminar requiere **confirmación** explícita.
* La visualización debe reflejar correctamente los **estados** y **validaciones** de actividades (`estado_id`, `validado`).
* Durante la edición:
  * `fecha_inicio` ≤ `fecha_fin` en plan y actividades.
  * Nuevas actividades se crean con `estado_id = pendiente` y `validado = NULL/false`.
  * Si una actividad validada se modifica, se restablece `validado = NULL/false` y `estado_id = pendiente`.
  * Solo se admiten actividades del propio plan.

---

### **Criterios de aceptación**

**CA-1.** El médico solo puede visualizar y editar **planes propios**.
**CA-2.** La vista muestra paciente, diagnóstico, fechas del plan y KPIs de actividades.
**CA-3.** La tabla de actividades incluye: nombre, descripción (truncada), inicio, fin, estado, validado.
**CA-4.** El botón **Editar** abre el formulario con datos **pre-cargados** del plan y sus actividades, permitiendo agregar, modificar y eliminar actividades.
**CA-5.** Fechas inválidas (plan o actividades) bloquean el guardado mostrando mensajes claros.
**CA-6.** Al modificar una actividad previamente validada, su `validado` queda NULL/false y su `estado_id` pasa a `pendiente`.
**CA-7.** Nuevas actividades creadas al editar quedan con `estado_id = pendiente` y `validado = NULL/false`.
**CA-8.** El botón **Eliminar** pide confirmación modal y, al aceptar, elimina el plan y sus actividades en cascada, mostrando mensaje de éxito y regresando al listado.
**CA-9.** Si el plan no existe o es ajeno, se informa y se deniega acceso/retorna al listado.
**CA-10.** Los botones de navegación permiten volver al listado o cancelar la edición sin cambios.

---

### **Casos borde y errores**

* Plan inexistente o no pertenece al médico → error de autorización / retorno seguro al listado.
* Plan sin actividades → mostrar **empty-state** (“Este plan aún no tiene actividades”).
* Edición: eliminación de una actividad inexistente o ya eliminada → mensaje no bloqueante y refresco del listado.
* Fallo de base de datos al guardar o eliminar → mensaje de error, no dejar datos inconsistentes.
