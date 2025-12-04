# 🩺 Historia de Usuario — PAC-DOC-001

## Gestión de documentación médica del paciente (ABMC)

### **ID**

`PAC-DOC-001`

### **Título**

*Como paciente, quiero gestionar mi documentación médica (ABMC), para centralizar y mantener actualizados mis informes, recetas y estudios.*

---

### **Descripción / Contexto**

El paciente necesita un espacio único (Historial Médico) donde visualizar, cargar, editar metadatos básicos y eliminar su documentación clínica. Incluye recetas, informes y estudios, con opción de descarga/visualización y filtros simples por tipo. Los archivos se almacenan en el repositorio de documentación del paciente.

---

### **Alcance**

**Incluye:**

* Alta de documento: selección de archivo, tipo (informe/receta/estudio), nombre descriptivo y fecha del documento.
* Visualización en listado consolidado (Historial Médico) con filtros por tipo y orden cronológico.
* Edición de metadatos básicos (nombre descriptivo, tipo, fecha del documento) sin reemplazar el archivo.
* Eliminación con confirmación y retroalimentación al usuario.
* Descarga/visualización del archivo y acceso a detalle (metadatos + enlace de descarga).

**No incluye:**

* Versionado de documentos ni control de cambios.
* Firma digital, validación clínica o certificación de documentos.
* Envío por correo u otros canales externos.
* Edición del contenido del archivo (solo metadatos básicos).

---

### **Actores y Permisos**

* **Paciente autenticado:** puede crear, ver, filtrar, editar metadatos y eliminar sus propios documentos.
* **Médico/Administrador:** fuera de alcance de esta historia para ABMC del paciente.

---

### **Dependencias / Supuestos**

* El paciente está autenticado y su sesión define `user_id` y rol `paciente`.
* La tabla `documentacion` registra los archivos del paciente; los archivos físicos se almacenan en `public/uploads/` con rutas accesibles via `url`.
* Catálogo de tipos manejado en la capa de aplicación (informe, receta, estudio) para filtrar y clasificar.
* Los filtros `auth` y `role:paciente` protegen la sección Historial Médico.

---

### **Flujo principal**

1) El paciente ingresa a **Historial Médico** desde la sidebar.  
2) Visualiza el listado de documentos ordenado por fecha (más recientes primero) con filtros por tipo.  
3) Para agregar un documento, selecciona archivo, tipo, nombre descriptivo y fecha → confirma.  
4) El sistema valida campos obligatorios, sube el archivo y registra el documento en la base.  
5) El paciente puede editar metadatos (nombre, tipo, fecha) sin reemplazar el archivo.  
6) Puede eliminar un documento con confirmación; el sistema elimina el registro y el archivo.  
7) Puede descargar o abrir el documento desde la tabla o desde una vista de detalle.  

---

### **Validaciones de negocio**

* Archivo obligatorio; tamaño y extensiones permitidas según política (p. ej. PDF/JPG/PNG, máx. X MB).
* `nombre` descriptivo obligatorio, longitud 3–180 caracteres.
* `tipo` requerido (informe/receta/estudio) según catálogo interno.
* `fecha_documento` requerida y no futura.
* Edición: solo metadatos; no se reemplaza archivo en este flujo.
* Eliminación: requiere confirmación explícita.

---

### **Criterios de aceptación**

**CA-1.** El paciente puede cargar un documento con tipo y nombre descriptivo; queda visible en el listado.  
**CA-2.** Los filtros por tipo (informe, receta, estudio) actualizan el listado sin errores.  
**CA-3.** La descarga/visualización es accesible desde el listado y/o detalle.  
**CA-4.** Al editar metadatos se valida longitud y formato; se preservan datos en caso de error.  
**CA-5.** Al eliminar un documento, el sistema solicita confirmación y elimina registro + archivo.  
**CA-6.** Ante validaciones fallidas se muestran mensajes claros y no se pierde el archivo ya subido (se debe reintentar la carga).  
**CA-7.** Solo el paciente autenticado accede a su documentación; otros roles son rechazados.  

---

### **Casos borde y errores**

* Archivo faltante o extensión no permitida → error y no se guarda.  
* Fecha futura → error de validación.  
* Falta de permisos o sesión inválida → redirección a login o error 403.  
* Archivo inexistente en disco al intentar descargar → mensaje de error y sugerencia de volver a cargar.  
* Eliminación cancelada por el usuario → no se realizan cambios.  

---

### **Datos mínimos / Modelo**

**Entidad: Documentación**

| Atributo     | Tipo           | Descripción                                |
|--------------|----------------|--------------------------------------------|
| `id`         | INT (PK)       | Identificador del documento                |
| `usuario_id` | FK→usuarios.id | Paciente dueño del documento               |
| `url`        | VARCHAR        | Ruta/URL pública o protegida al archivo    |
| `created_at` | DATETIME NULL  | Fecha/hora de carga                        |

---

### **Conclusión**

La historia define el ABMC de la documentación médica del paciente dentro de Historial Médico, con filtros por tipo y controles de validación básicos para mantener un repositorio ordenado y accesible.

