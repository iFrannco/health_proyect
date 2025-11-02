# 🩺 Historia de Usuario — **MED-ACT-VAL-001**

## Validar la realización de una actividad del plan de cuidado

### **Título**

*Como médico, quiero validar directamente una actividad marcada como completada por el paciente, para confirmar su cumplimiento clínico sin pasos adicionales.*

---

### **Descripción / Contexto**

En la vista **“Detalle del plan”** (módulo médico), el listado de actividades debe incorporar una **columna de acción “Validar”**.
El médico hace clic y la actividad queda **validada al instante**, sin popup de confirmación.

---

### **Alcance**

**Incluye**

* Nueva **columna final** en la tabla de actividades: **Acción** → botón **“Validar”**.
* Validación **inmediata** (sin modal):

  * `validado = true`
  * `fecha_validacion = NOW()`
* Reglas de habilitación del botón:

  * **Habilitado** solo si `estado == completada` y `validado != true`.
  * **Deshabilitado** si `pendiente` o `vencida`.
* Feedback UI:

  * Toast “Actividad validada”.
  * La fila se actualiza: badge **Validada**, botón cambia a **“Validada”** (disabled).

**No incluye**

* Edición de la actividad desde esta columna.
* Acciones masivas.
* Cambiar estados (*pendiente/completada/vencida*) desde la UI del médico (se gestiona en otras historias).

---

### **Actores y Permisos**

* **Médico**: puede validar actividades de **planes propios**.
* **Paciente/Administrador**: fuera de alcance.

---

### **Dependencias / Supuestos**

* La actividad fue marcada **completada** por el paciente previamente.
* El plan pertenece al **médico en sesión** (`plan.medico_id`).
* Estados vigentes en `estado_actividad`: **pendiente**, **completada**, **vencida**.

---

### **Flujo principal**

1. El médico abre **Medico → Planes → Detalle** de un plan propio.
2. En la tabla de **Actividades**, ve la nueva columna **Acción** con el botón **Validar**.
3. Si la actividad está **completada** y **no validada**, hace clic en **Validar**.
4. El sistema registra la validación (fecha y médico), **actualiza la fila** y muestra un **toast de éxito**.
5. El botón pasa a estado **“Validada”** (disabled) y la columna de “Validado” muestra el badge correspondiente.

---

### **Validaciones de negocio**

* Solo validar actividades de **planes propios**.
* Solo validar si `estado == completada`.
* Operación **idempotente**: si dos clics concurrentes llegan al servidor, la segunda no debe duplicar ni fallar de forma ruidosa.
* Si la actividad cambió de estado entre lectura y validación (p. ej., volvió a **pendiente**), rechazar con mensaje “La actividad ya no está en estado ‘hecha’”.

---

### **Criterios de aceptación (CA)**

**CA-1.** La tabla de actividades incorpora una **columna final** con el botón **Validar**.
**CA-2.** El botón **solo está habilitado** cuando la actividad está **hecha** y **no validada**.
**CA-3.** Al hacer clic, **se valida sin confirmación** y la interfaz refleja el cambio (badge + botón deshabilitado + toast).
**CA-4.** Si la actividad ya fue validada o no está en estado **completada**, muestra un boton para desvalidar.
**CA-5.** La validación queda registrada con `fecha_validacion`.

---

### **Casos borde y errores**

* **Competencia**: otra acción valida la actividad mientras el médico tiene la tabla abierta → al hacer clic, mostrar aviso “Esta actividad ya fue validada” y refrescar estado.
* **Pérdida de conexión**: mostrar error y **no** cambiar el estado en UI.
* **Plan ajeno**: acceso denegado.

