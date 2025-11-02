# 🩺 Historia de Usuario — MED-PLAN-001

## Crear y asignar un plan de cuidado **personalizado** a un paciente

### **ID**

`MED-PLAN-001`

### **Título**

*Como médico, quiero crear y asignar un plan de cuidado personalizado a un paciente, para definir manualmente las actividades clínicas asociadas a un diagnóstico específico.*

---

### **Descripción / Contexto**

Un **Plan de Cuidado personalizado** permite al médico definir manualmente el contenido del cuidado del paciente (fechas, nombre y descripción de cada actividad), sin usar una plantilla estandarizada.
El plan se **vincula obligatoriamente a un Diagnóstico existente** del paciente.
Las actividades creadas comienzan con **estado `sin_iniciar`** y el médico podrá **validarlas** más adelante cuando el paciente las marque como completas.

---

### **Alcance**

**Incluye:**

* Creación de **Plan de Cuidado** manual (no basado en plantilla).
* Selección de **Paciente** y luego de un **Diagnóstico** de ese paciente para asociar el plan.
* Carga manual de **actividades** (nombre, descripción, fechas de inicio/fin).
* Registro del **médico creador** del plan para controlar su visualización y mantenimiento.
* Asignación automática de `fechaCreacion` del plan y de cada actividad.
* Estado inicial de cada actividad: `sin_iniciar` (FK a `estado_actividad`).
* Campo `validado` de actividad como **nullable** y **false** por defecto.
* Persistencia y feedback de confirmación.

**No incluye:**

* Uso de **plantillas estándar** para pre-cargar actividades.
* Validación de que el **médico creador** sea el autor del diagnóstico.
* Validación de **paciente activo**.
* Restricción de **único plan por diagnóstico** (se permiten múltiples).
* Edición/eliminación de planes o actividades (otras historias).
* Validación clínica de secuencias/condiciones de fechas entre actividades.

---

### **Actores y Permisos**

* **Médico**: crea planes personalizados, selecciona paciente y diagnóstico.
* **Paciente**: no crea planes.
* **Administrador**: visualización/gestión fuera de esta historia.

---

### **Dependencias / Supuestos**

* Existe **catálogo** `estado_actividad` con al menos: `sin_iniciar`, `iniciada`, `terminada`.
* El médico está **autenticado** y autorizado.
* Existen **Usuarios** con rol **paciente** y **médico**; existen **Diagnósticos** del paciente.
* El modelo de datos vigente usa:

  * `Actividad.estado_id` (FK a `estado_actividad`)
  * `Actividad.validado` (boolean nullable, default `NULL`/`false`)

---

### **Flujo principal**

1. El médico accede a **Planes → Nuevo (personalizado)**.
2. Selecciona un **Paciente**.
3. El sistema muestra los **Diagnósticos** del paciente seleccionado; el médico elige uno.
4. Informa **nombre** (opcional), **fechaInicio**, **fechaFin** y **descripción** del Plan (opcional).
5. Agrega **una o más actividades** manuales, cada una con:

   * `nombre`, `descripcion`, `fechaInicio`, `fechaFin`.
6. El sistema:

   * valida datos del plan y actividades,
   * asigna `fechaCreacion` del plan y de cada actividad,
   * setea `estado_id` de cada actividad = `sin_iniciar`,
   * persiste Plan y Actividades vinculadas al **Diagnóstico** seleccionado.
7. Muestra **confirmación** y redirige al listado/detalle del plan.

---

### **Validaciones de negocio**

* Debe seleccionarse **Paciente** y **Diagnóstico** (del paciente).
* `fechaInicio` ≤ `fechaFin` en Plan y en cada Actividad.
* Cada **Actividad** requiere `nombre` (1–120), `descripcion` (1–2000), `fechaInicio`, `fechaFin`.
* Un plan puede crearse **con al menos una** actividad. 
* `estado_id` inicial de cada actividad es el valor del catálogo **sin_iniciar**.
* `validado` inicia **NULL/false** y solo puede volverse **true** cuando el paciente marca la actividad como completada (otra historia).
* El plan se persiste con el **médico autenticado** como creador.

---

### **Criterios de aceptación (CA)**

**CA-1.** El médico puede seleccionar **Paciente** y luego un **Diagnóstico** del paciente.
**CA-2.** El sistema permite ingresar **fechas** y **descripción** del Plan; `fechaCreacion` se genera automáticamente.
**CA-3.** El médico puede **agregar actividades** manuales; cada una se guarda con `estado_id = sin_iniciar`.
**CA-4.** Al guardar, Plan y Actividades quedan **asociados** al Diagnóstico seleccionado.
**CA-5.** Si faltan datos obligatorios en actividades o plan, se muestran errores y **no** se persiste nada.
**CA-6.** `fechaInicio` no puede ser posterior a `fechaFin` (válido para el plan y para cada actividad).
**CA-7.** Tras la creación, se muestra **mensaje de éxito** y el plan aparece en el **listado** del médico.
**CA-8.** Pueden existir **múltiples planes** para un mismo diagnóstico sin bloquear la operación.
**CA-9.** No se exige que el médico creador del plan sea el autor del diagnóstico.
**CA-10.** Las actividades del plan aparecen con **estado inicial `sin_iniciar`** y `validado = NULL/false`.
**CA-11.** El plan queda asociado al médico que lo crea y solo aparece en su listado de gestión.

---

### **Casos borde y errores**

* Paciente sin diagnósticos → mensaje “El paciente seleccionado no posee diagnósticos.” (no bloquea la selección de otro paciente).
* Diagnóstico no pertenece al paciente seleccionado → error bloqueante.
* Fechas inválidas (inicio > fin) → error bloqueante a nivel plan/actividad.
* Fallo de persistencia → rollback y mensaje genérico “No se pudo crear el plan de cuidado.”

---

### **Datos mínimos / Modelo**

**PlanDeCuidado**

| Campo            | Tipo           | Descripción                                |
| ---------------- | -------------- | ------------------------------------------ |
| `id`             | INT (PK)       | Identificador                              |
| `diagnostico_id` | FK→Diagnostico | Diagnóstico asociado                       |
| `creadorUserId`  | FK→Usuario     | Médico que crea el plan                    |
| `plan_estandar_id` | FK→PlanEstandar | Plantilla origen (nullable)                 |
| `nombre`         | VARCHAR(180)   | Título opcional definido por el médico     |
| `descripcion`    | TEXT NULL      | Descripción clínica opcional               |
| `fechaCreacion`  | DATETIME       | Asignada automáticamente                   |
| `fechaInicio`    | DATE           | Inicio del plan                            |
| `fechaFin`       | DATE           | Fin del plan                               |
| `estado`         | VARCHAR        | Estado general (opcional según tu dominio) |

**Actividad**

| Campo           | Tipo                | Descripción                                         |
| --------------- | ------------------- | --------------------------------------------------- |
| `id`            | INT (PK)            | Identificador                                       |
| `plan_id`       | FK→PlanDeCuidado    | Plan al que pertenece                               |
| `fechaCreacion` | DATETIME            | Autogenerada                                        |
| `fechaInicio`   | DATE                | Inicio de la actividad                              |
| `fechaFin`      | DATE                | Fin de la actividad                                 |
| `nombre`        | VARCHAR(120)        | Nombre                                              |
| `descripcion`   | VARCHAR/TEXT        | Descripción                                         |
| `estado_id`     | FK→estado_actividad | `sin_iniciar` al crear                              |
| `validado`      | BOOLEAN NULL        | `NULL/false` al crear; true cuando el médico valida |
