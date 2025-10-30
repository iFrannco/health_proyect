
# 🩺 Historia de Usuario — MED-DIAG-001

## Alta de diagnóstico para un paciente

### **ID**

`MED-DIAG-001`

### **Título**

*Como médico, quiero dar de alta un diagnóstico para un paciente, para registrar el motivo clínico y vincularlo posteriormente con planes de cuidado.*

---

### **Descripción / Contexto**

El diagnóstico constituye el punto de partida del proceso clínico.
Permite al médico registrar formalmente una evaluación o condición del paciente, especificando el tipo de diagnóstico (por ejemplo: operación, tratamiento, control, etc.), su descripción y la fecha de creación.

Cada diagnóstico:

* pertenece a **un paciente**,
* es emitido por **un médico responsable**,
* tiene un **tipo de diagnóstico** definido en un catálogo fijo,
* y puede tener asociados **planes de cuidado** (0..n, ahora o en un futuro).

---

### **Alcance**

**Incluye:**

* Alta de diagnósticos desde el módulo del médico.
* Validaciones de integridad (paciente, tipo de diagnóstico, campos obligatorios).
* Registro automático de `fechaCreacion` al momento del alta.
* Asociación automática del diagnóstico con el **médico logueado**.
* Feedback en la interfaz con confirmación de registro exitoso.
* Persistencia del diagnóstico en la base de datos (`diagnosticos`).

**No incluye:**

* Edición, baja o visualización detallada del diagnóstico.
* Adjuntar documentación o estudios médicos.
* Creación simultánea de planes de cuidado (solo se registra el diagnóstico).
* Gestión de tipos de diagnóstico (catálogo fijo, sin CRUD).

---

### **Actores y Permisos**

* **Médico**: puede crear diagnósticos.
* **Administrador**: puede visualizar diagnósticos (fuera de esta historia).
* **Paciente**: no puede crear ni modificar diagnósticos.

---

### **Dependencias / Supuestos**

* Catálogo `TipoDiagnostico` precargado y accesible.
* Existen usuarios con rol `médico` y `paciente`.
* El médico se encuentra autenticado y autorizado.
* No hay restricciones sobre cuántos diagnósticos puede tener un paciente.

---

### **Flujo principal**

1. El médico accede al módulo **Diagnósticos → Nuevo**.
2. Selecciona un **paciente existente**.
3. Selecciona un **tipo de diagnóstico** (del catálogo).
4. Escribe una **descripción clínica**.
5. Confirma la operación.
6. El sistema:

   * valida los datos,
   * asocia el médico actual como `medicoResponsable`,
   * genera automáticamente `fechaCreacion`,
   * persiste el registro en la tabla `diagnosticos`.
7. Se muestra un mensaje de confirmación y el médico puede volver al listado.

---

### **Validaciones de negocio**

* `paciente` debe existir y estar activo.
* `tipoDiagnostico` debe existir en el catálogo.
* `descripcion` obligatoria, longitud 10–2000 caracteres.
* Un médico puede emitir múltiples diagnósticos para un mismo paciente.
* `fechaCreacion` se asigna automáticamente (no editable).
* Integridad referencial: todas las FKs deben ser válidas.

---

### **Criterios de aceptación**

**CA-1.** El médico autenticado puede registrar un diagnóstico completando paciente, tipo y descripción.
**CA-2.** Al guardar, se genera `fechaCreacion` automáticamente.
**CA-3.** El diagnóstico queda vinculado al **médico responsable actual** y al **paciente seleccionado**.
**CA-4.** Solo usuarios con rol `medico` pueden acceder a la creación (otros → acceso denegado).
**CA-5.** Si faltan campos requeridos, se muestran errores y no se persiste nada.
**CA-6.** Tipos de diagnóstico inválidos generan error controlado.
**CA-7.** La descripción debe cumplir con la longitud mínima/máxima configurada.
**CA-8.** Tras el alta, se muestra un mensaje de éxito y el registro aparece en el listado del médico.
**CA-9.** Los datos guardados respetan integridad referencial (FKs válidas).

---

### **Casos borde y errores**

* Paciente inexistente o eliminado → mensaje de error “El paciente seleccionado no existe.”
* Tipo de diagnóstico no encontrado → error “Tipo de diagnóstico inválido.”
* Campos vacíos → validaciones visuales y bloqueante en backend.
* Intento de acceso sin rol `medico` → HTTP 403 o redirección al inicio.
* Error en la BD → rollback + mensaje genérico “No se pudo registrar el diagnóstico.”

---

### **Datos mínimos / Modelo**

**Entidad: Diagnóstico**

| Atributo            | Tipo                        | Descripción                    |
| ------------------- | --------------------------- | ------------------------------ |
| `id`                | INT (PK)                    | Identificador único            |
| `medicoResponsable` | FK→Usuario                  | Médico que crea el diagnóstico |
| `paciente`          | FK→Usuario                  | Paciente diagnosticado         |
| `tipoDiagnostico`   | FK→TipoDiagnostico          | Clasificación del diagnóstico  |
| `descripcion`       | STRING / TEXT               | Detalle clínico                |
| `fechaCreacion`     | DATE                        | Se genera automáticamente      |
| `planesDeCuidado`   | PlanCuidado[] (0..n)         | Planes asociados, si existen   |
