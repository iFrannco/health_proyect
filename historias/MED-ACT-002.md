# 🩺 Historia de Usuario — **MED-ACT-002**

## Asignar categoría predefinida a actividades

### **ID**

`MED-ACT-002`

### **Título**

*Como médico, quiero asignar una categoría predefinida a cada actividad del plan de cuidado, para clasificarlas y facilitar el seguimiento clínico.*

---

### **Descripción / Contexto**

Las actividades de un **plan de cuidado** necesitan clasificarse bajo un **catálogo cerrado de categorías** (por ejemplo: Educación sanitaria, Medicación, Ejercicio/Indicaciones, Controles y seguimiento), incluyendo una opción **genérica/otras** para los casos no cubiertos por las categorías específicas. El médico debe poder elegir una de estas categorías al crear o ajustar una actividad, de modo que el equipo clínico pueda priorizar, reportar y navegar las actividades según su naturaleza sin depender de texto libre.

---

### **Alcance**

**Incluye:**

* Campo **Categoría** obligatorio en la creación y edición de actividades de planes de cuidado pertenecientes al médico.
* Selección desde un **catálogo precargado** y activo de categorías (lista desplegable sin texto libre).
* Opción **genérica/otras** dentro del catálogo para actividades que no encajen en una categoría específica.
* Persistencia de la categoría seleccionada en la actividad y **visualización** en el listado/detalle de actividades.
* Edición de la categoría mientras la actividad no esté validada, manteniendo histórico de validaciones intacto.

**No incluye:**

* Alta, edición o eliminación de categorías del catálogo.
* Asignación múltiple de categorías a una misma actividad.
* Gestión de categorías por parte de pacientes o administradores.
* Filtros o reportes avanzados por categoría (solo visualización básica en esta historia).

---

### **Actores y Permisos**

* **Médico** autenticado: puede asignar o modificar la categoría de actividades en planes propios.
* **Paciente** y **Administrador**: no pueden asignar ni modificar categorías de actividades.

---

### **Dependencias / Supuestos**

* Existe un **plan de cuidado** con actividades creadas o en creación y pertenece al médico en sesión.
* Catálogo de **categorías de actividad** precargado (incluye opción genérica) y marcado como activo/inactivo.
* El médico ya está autenticado y autorizado para gestionar actividades de sus planes.
* Estados de actividad (`sin_iniciar`, `iniciada`, `terminada`) y reglas de validación vigentes.

---

### **Flujo principal**

1. El médico accede al detalle de un plan de cuidado propio o inicia la creación de una nueva actividad.
2. El formulario de la actividad muestra el campo **Categoría** como lista desplegable, precargada con las categorías activas.
3. El médico selecciona una categoría y completa el resto de datos obligatorios de la actividad.
4. El sistema valida la selección (categoría activa y perteneciente al catálogo) y guarda la actividad.
5. La actividad queda asociada a la categoría elegida; el listado/detalle muestra la categoría (etiqueta o columna).
6. Si el médico edita una actividad no validada, puede cambiar la categoría y guardar nuevamente.

---

### **Validaciones de negocio**

* La categoría es **obligatoria** y debe existir en el catálogo de categorías activas.
* Solo el **médico dueño del plan** puede asignar o modificar la categoría de sus actividades.
* Las actividades **validadas** quedan bloqueadas para cambio de categoría.
* No se admiten categorías libres ni valores enviados que no pertenezcan al catálogo activo (incluida la opción genérica).
* La asignación no debe alterar el estado (`estado_id`) ni la marca de validación existente.

---

### **Criterios de aceptación**

**CA-1.** El formulario de creación/edición de actividad incluye el campo **Categoría** como lista desplegable con categorías precargadas y activas.  
**CA-2.** Al guardar, la actividad queda asociada a la categoría seleccionada y se muestra en el detalle/listado.  
**CA-3.** No se permiten valores fuera del catálogo ni texto libre; de intentarlo, se bloquea el guardado con mensaje claro.  
**CA-4.** Solo médicos autenticados y propietarios del plan pueden asignar o modificar la categoría.  
**CA-5.** Se ofrece una opción **genérica/otras** dentro del catálogo para cubrir actividades no contempladas en las categorías específicas.  
**CA-6.** Si la actividad ya está validada, el campo de categoría aparece bloqueado y no se persisten cambios.  
**CA-7.** Campos faltantes o categoría no seleccionada generan mensajes de validación y no crean/actualizan la actividad.

---

### **Casos borde y errores**

* Categoría inexistente o marcada como inactiva → error de validación y se mantiene la actividad sin cambios.
* Si ninguna categoría específica aplica, el médico puede seleccionar la opción **genérica/otras** del catálogo activo.
* Intento de asignar categoría en un plan ajeno al médico → acceso denegado.
* Cambio de categoría sobre actividad validada → operación rechazada y se muestra motivo.
* Fallo de persistencia → mensaje genérico, conservando los datos cargados en el formulario.

---

### **Conclusión**

La historia habilita a los médicos a **clasificar cada actividad** mediante un catálogo precargado, asegurando consistencia y trazabilidad sin abrir la gestión de categorías a otros roles ni a texto libre.
