# 🩺 Historia de Usuario — MED-PER-002

## Definir especialidad las especialidades del medico.

### **ID**

`MED-PER-002`

### **Título**

*Como médico, quiero una serie de especialidades (0 a N), para que la plataforma refleje mi área clínica y los pacientes y administradores puedan identificarla claramente.*

---

### **Descripción / Contexto**

Cada médico debe indicar su especialidad/es para que el sistema muestre la información correcta en el perfil y en las interacciones con pacientes y administradores. Las especialidades provienen de un catálogo fijo (hardcodeado) y no se gestionan desde la interfaz; el médico solo selecciona una opción válida y la guarda en su perfil.

---

### **Alcance**

**Incluye:**

* Visualización de la especialidad/es (si las tiene) actual del médico en su perfil.
* Selector desplegable con la lista fija de especialidades disponibles.
* Actualización y persistencia de la especialidad seleccionada, con confirmación visible.
* Validación para impedir valores fuera del catálogo definido.


**No incluye:**

* Alta, edición o baja de especialidades (catálogo hardcodeado, sin CRUD).
* Impacto en asignación de pacientes, turnos o planes de cuidado más allá de mostrar la especialidad.

---

### **Actores y Permisos**

* **Médico autenticado:** único actor que puede ver y actualizar su especialidad/es.
* **Administrador / Paciente:** fuera de alcance para modificar; solo podrían consumir el dato en otros módulos.

---

### **Dependencias / Supuestos**

* El usuario está autenticado con rol `medico` y accede a su propio perfil.
* Existe un **catálogo fijo** de especialidades (ej.: clínica médica, pediatría, cardiología, traumatología, dermatología, ginecología, neurología), expuesto desde configuración o constantes de la aplicación.
* Las rutas y vistas de perfil del médico están protegidas por los filtros `auth` y `role:medico`.

---

### **Flujo principal**

1. El médico accede a **Mi Perfil → Especialidad**.
2. El sistema muestra la especialidad/es actualmente guardada/s o un estado “Sin especialidad asignada”.
3. El médico despliega el selector y revisa la lista de especialidades disponibles del catálogo fijo.
4. Selecciona una opción y confirma la actualización.
5. El sistema valida que la opción pertenezca al catálogo permitido.
6. Se persiste la especialidad en el perfil del médico y se muestra mensaje de éxito.

---

### **Validaciones de negocio**

* La especialidad/es seleccionada debe existir en el **catálogo hardcodeado**; no se aceptan valores libres.
* El médico solo puede modificar **su propio** registro de especialidad.
* Si la especialidad no cambia, se mantiene el valor existente sin generar duplicados ni inconsistencias.

---

### **Criterios de aceptación**

**CA-1.** El médico accede a su perfil y visualiza su especialidad/es actual o un estado sin asignar.  
**CA-2.** El selector de especialidades muestra únicamente las opciones del catálogo fijo y no permite texto libre.  
**CA-3.** Al guardar una opción válida, el sistema persiste la especialidad y muestra confirmación de éxito.  
**CA-4.** Si el médico intenta enviar una especialidad fuera del catálogo, se muestra un error y no se guarda nada.   
**CA-5.** La actualización solo está disponible para usuarios con rol médico y sobre su propio perfil.

---

### **Casos borde y errores**

* Catálogo vacío o no disponible → mensaje informando que no hay especialidades configuradas y se bloquea la actualización.
* Valor manipulado por cliente (slug inválido) → error de validación y rechazo de la operación.
* Fallo de persistencia → rollback y mensaje genérico “No se pudo actualizar la especialidad”.
* Intento de un usuario no médico de acceder o modificar → rechazo por permisos y redirección/autenticación según corresponda.


### **Conclusión**

La historia incorpora al perfil médico la definición de especialidad/es mediante un catálogo fijo, asegurando datos clínicos coherentes y visibles sin habilitar gestión dinámica de especialidades.
