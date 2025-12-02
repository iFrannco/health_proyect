# 🩺 Historia de Usuario — MED-PER-002

## Gestionar especialidades clínicas del médico

### **ID**

`MED-PER-002`

### **Título**

*Como médico, quiero gestionar (agregar o quitar) mis especialidades clínicas desde mi perfil, para que la plataforma refleje correctamente mis áreas de práctica y las demás partes puedan identificarlas.*

---

### **Descripción / Contexto**

Cada médico puede tener cero o más especialidades clínicas. El sistema debe permitir que el médico seleccione sus especialidades desde un catálogo fijo (hardcodeado) y mostrarlas en su perfil. Pacientes y administradores no tienen especialidades asignadas. La gestión se limita a seleccionar o deseleccionar opciones válidas en una tabla de relación; no hay alta o edición del catálogo.

---

### **Alcance**

**Incluye:**

* Visualización en el perfil de las especialidades actuales del médico (lista, puede estar vacía).
* Selector (multiselección) con la lista fija de especialidades disponibles.
* Alta y baja de especialidades asignadas al médico (agregar o quitar opciones del catálogo) con confirmación visible.
* Validación para impedir valores fuera del catálogo definido y duplicados.
* Preselección de las especialidades guardadas al volver a abrir el perfil.

**No incluye:**

* Alta, edición o baja de especialidades en el catálogo (hardcodeado, sin CRUD).
* Subespecialidades o jerarquías entre especialidades.
* Impacto en asignación de pacientes, turnos o planes de cuidado más allá de mostrar las especialidades.
* Carga de documentación de respaldo (títulos, matrículas) asociada a las especialidades.

---

### **Actores y Permisos**

* **Médico autenticado:** único actor que puede ver y actualizar sus especialidades.
* **Administrador / Paciente:** no gestionan especialidades (no tienen especialidades asignadas en esta historia).

---

### **Dependencias / Supuestos**

* El usuario está autenticado con rol `medico` y accede a su propio perfil.
* Existe un **catálogo fijo** de especialidades (ej.: clínica médica, pediatría, cardiología, traumatología, dermatología, ginecología, neurología), almacenado en la tabla `especialidades` (hardcode/seeds/config, sin CRUD en UI).
* Las asignaciones se guardan en una tabla de relación (p. ej. `usuario_especialidad`) entre médicos (`usuarios` rol medico) y `especialidades`.
* Las rutas y vistas de perfil del médico están protegidas por los filtros `auth` y `role:medico`.
* **Pendiente a implementar** (recordatorio): en el flujo de alta/login con rol médico se debe habilitar la selección/carga inicial de especialidades; esta historia se centra en la gestión desde el perfil.

---

### **Flujo principal**

1. El médico accede a **Mi Perfil → Especialidades**.
2. El sistema muestra las especialidades actualmente guardadas o un estado “Sin especialidades asignadas”.
3. El médico abre el selector múltiple y revisa la lista de especialidades disponibles del catálogo fijo.
4. Agrega y/o quita especialidades y confirma la actualización.
5. El sistema valida que todas las opciones pertenezcan al catálogo permitido y elimina duplicados.
6. Se persisten las asignaciones en la tabla de relación y se muestra mensaje de éxito.
7. En posteriores accesos al perfil, las especialidades guardadas aparecen preseleccionadas y visibles.

---

### **Validaciones de negocio**

* Se permiten **cero a N** especialidades por médico; la lista puede estar vacía.
* Toda especialidad seleccionada debe existir en el **catálogo hardcodeado**; no se aceptan valores libres.
* No se permiten duplicados en las asignaciones de un mismo médico.
* El médico solo puede modificar **sus propias** especialidades.
* Si no se realizan cambios, se preserva el conjunto existente sin recrear registros.

---

### **Criterios de aceptación**

**CA-1.** El médico accede a su perfil y visualiza la lista de especialidades asignadas o un estado sin asignar.  
**CA-2.** El selector de especialidades muestra únicamente las opciones del catálogo fijo y no permite texto libre.  
**CA-3.** Al guardar opciones válidas, el sistema persiste el conjunto completo (agregadas y removidas) y muestra confirmación de éxito.  
**CA-4.** Si se envían especialidades fuera del catálogo o duplicadas, se muestra un error y no se guarda nada.  
**CA-5.** Al volver a abrir el perfil, las especialidades previamente guardadas aparecen preseleccionadas.  
**CA-6.** La actualización solo está disponible para usuarios con rol médico y sobre su propio perfil; pacientes y administradores no gestionan especialidades.

---

### **Casos borde y errores**

* Catálogo vacío o no disponible → mensaje informando que no hay especialidades configuradas y se bloquea la actualización.
* Valor manipulado por cliente (slug inválido) → error de validación y rechazo de la operación.
* Envío con especialidades duplicadas → error de validación o deduplicación preventiva sin duplicar registros.
* Eliminación de todas las especialidades → se persiste el conjunto vacío y se refleja en el perfil.
* Fallo de persistencia → rollback y mensaje genérico “No se pudo actualizar las especialidades”.
* Intento de un usuario no médico de acceder o modificar → rechazo por permisos y redirección/autenticación según corresponda.

---

### **Datos mínimos / Modelo**

| Atributo                    | Tipo/Origen                 | Descripción                                                         |
|-----------------------------|-----------------------------|---------------------------------------------------------------------|
| `usuarios.id`               | INT (PK)                    | Identificador del médico.                                           |
| `usuarios.role_id`          | FK→roles.id                 | Rol asociado (debe ser `medico`).                                   |
| `especialidades.id`         | INT (PK)                    | Catálogo fijo de especialidades (hardcode/seeds/config).            |
| `especialidades.slug`       | VARCHAR(50) UNIQUE          | Clave/slug de la especialidad.                                      |
| `especialidades.nombre`     | VARCHAR(180)                | Nombre visible de la especialidad.                                  |
| `usuario_especialidad.id`   | INT (PK)                    | Identificador de la relación.                                       |
| `usuario_especialidad.user_id` | FK→usuarios.id          | Médico al que se asigna la especialidad.                            |
| `usuario_especialidad.especialidad_id` | FK→especialidades.id | Especialidad asignada (pertenece al catálogo).                  |
| `usuario_especialidad` (UNIQUE user_id + especialidad_id) | Restricción | Evita duplicados por médico/especialidad.                           |

---

### **Conclusión**

La historia incorpora al perfil médico la gestión de cero a N especialidades mediante un catálogo fijo y una tabla de relación, asegurando datos clínicos coherentes sin habilitar gestión dinámica del catálogo. **Recordatorio pendiente:** al implementar el alta/login con rol médico, habilitar la carga inicial de especialidades conforme al mismo catálogo.
