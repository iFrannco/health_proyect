# 👤 Historia de Usuario — **PAC-DIAG-001**

## Visualizar diagnósticos asignados al paciente

### **ID**

`PAC-DIAG-001`

### **Título**

*Como paciente, quiero ver mis diagnósticos para entender qué condiciones me registraron y qué planes de cuidado están asociados.*

---

### **Descripción / Contexto**

El paciente necesita un espacio dedicado donde consultar los diagnósticos que le emitieron los médicos. Hoy solo ve un KPI de diagnósticos en el dashboard (`/paciente/home`) y el detalle de cada plan de cuidado, pero no cuenta con un historial claro de diagnósticos. Esta vista debe permitirle revisar qué se le diagnosticó, quién lo registró, cuándo ocurrió y qué planes de cuidado (activos o finalizados) están vinculados.

---

### **Alcance**

**Incluye:**

* Listado de diagnósticos del paciente (ordenado por fecha de creación descendente).
* Datos visibles por diagnóstico:
  * Tipo de diagnóstico (catálogo existente).
  * Descripción resumida (con opción de ver completa).
  * Fecha de creación.
  * Médico responsable (nombre).
  * Contadores de planes vinculados: activos, finalizados, totales.
* Vista de detalle de diagnóstico:
  * Descripción completa, tipo, médico, fecha.
  * Listado de planes de cuidado relacionados con su estado (en curso / sin iniciar / finalizado) y acceso al detalle existente (`/paciente/planes/{id}`).
* Mensajes de estado vacíos o neutros cuando no hay diagnósticos.
* Respuesta adecuada ante diagnósticos eliminados lógicamente (`deleted_at`), evitando que el paciente los vea.

**No incluye:**

* Creación, edición o eliminación de diagnósticos.
* Adjuntar o descargar documentos clínicos.
* Comentarios o notas del paciente sobre el diagnóstico.
* Creación o edición de planes de cuidado (se reutiliza navegación existente).

---

### **Actores y Permisos**

* **Paciente:** puede visualizar únicamente sus diagnósticos y acceder a los planes vinculados.
* **Otros roles:** acceso denegado a la ruta de paciente (redirigir o 403/404 según políticas actuales).

---

### **Dependencias / Supuestos**

* Existen registros en `diagnosticos` asociados al paciente logueado, con referencias a `tipos_diagnostico` y `users` (médico y paciente).
* Los planes de cuidado (`planes_cuidado`) están asociados a diagnósticos y se usan para calcular si un diagnóstico está “activo”.
* Se reutiliza el layout y estilo del módulo paciente (ver `app/Views/paciente`).
* Autenticación y filtro `auth` ya disponibles para las rutas de paciente.

---

### **Flujo principal**

1. El paciente navega al menú lateral → **“Diagnósticos”** (`/paciente/diagnosticos`).
2. El sistema muestra el listado de diagnosticos.
3. Selecciona un diagnóstico para ver su **detalle**: descripción completa, tipo, médico, fecha y planes asociados.
4. Desde la sección de planes asociados puede navegar al detalle del plan (`/paciente/planes/{id}`).

---

### **Criterios de aceptación**

**CA-1.** El paciente autenticado accede a `/paciente/diagnosticos` y ve solo sus diagnósticos (ninguno de otros pacientes).  
**CA-2.** El listado muestra, por cada diagnóstico: tipo, descripción resumida, fecha, médico responsable, y contadores de planes activos/finalizados/total.   
**CA-3.** El orden por defecto es fecha de creación descendente.  
**CA-4.** La vista de detalle incluye la descripción completa, tipo, fecha, médico y la lista de planes asociados con su estado y link al detalle existente del plan.  
**CA-5.** Si el paciente no tiene diagnósticos, se muestra un mensaje informativo y no se rompe el layout.  
**CA-6.** Diagnósticos eliminados lógicamente no se muestran ni permiten acceso directo.  
**CA-7.** El diseño mantiene coherencia con el módulo paciente y es responsive.  
**CA-8.** Intentar acceder a un diagnóstico que no pertenece al paciente responde con 403 o 404 (según política actual).

---

### **Casos borde y errores**

* Paciente sin diagnósticos → mensaje “No tenés diagnósticos registrados por el momento.”
* Diagnóstico sin planes asociados → mostrar contador en 0 y estado “Sin plan asignado”.
* Diagnóstico con médico desactivado o sin especialidad → mostrar texto neutro (“Médico no disponible”).
* Diagnóstico soft-deleted mientras el paciente navega → al refrescar, desaparece y/o detalle devuelve 404.
* Error de carga (BD o red) → mensaje genérico y opción de reintentar.

---

### **UX / UI sugerida**

* **Vista de listado:** tarjetas o tabla compacta con badges de estado y contadores de planes; botón/link “Ver detalle”.
* **Detalle:** panel con resumen del diagnóstico arriba y tarjetas de planes debajo, reutilizando los estilos de `paciente/planes/show`.

