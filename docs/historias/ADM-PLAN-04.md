# 🩺 Historia de Usuario — **ADM-PLAN-04**

## Gestión de planes de cuidado estandarizados (ABMC)

### **ID**
`ADM-PLAN-04`

### **Título**
*Como administrador, quiero gestionar los planes de cuidado estandarizados (crear, editar, consultar, inhabilitar), para proveer a los médicos de plantillas reutilizables que agilicen la asignación de tareas clínicas.*

---

### **Descripción / Contexto**
Los planes estandarizados actúan como plantillas maestras que definen protocolos clínicos mediante actividades con **tiempos relativos** (días de desfase desde el inicio) en lugar de fechas calendario.
Esta gestión permite al administrador mantener un catálogo actualizado de dichos protocolos. Los médicos podrán posteriormente instanciar estos planes en pacientes específicos (historia futura), donde los offsets se convertirán en fechas reales.

---

### **Alcance**

**Incluye:**
*   **Listado** de planes estandarizados con buscador por nombre y filtro por estado (Vigente / No vigente).
*   **Creación** de un nuevo plan estandarizado (Maestro-Detalle):
    *   **Cabecera:** Nombre, Versión (numérica), Descripción.
    *   **Detalle (Actividades):** Lista dinámica donde se define Nombre, Descripción, Offset Inicio (días), Offset Fin (días) y Orden.
*   **Edición** de planes existentes:
    *   Modificación de datos de cabecera y actividades.
    *   Gestión de actividades (agregar, quitar, modificar).
*   **Baja lógica (Soft Delete):**
    *   Acción para cambiar el estado de `vigente` (true/false).
*   **Validaciones de integridad** temporal en los offsets (inicio ≤ fin).

**No incluye:**
*   Asignación de planes a pacientes (Módulo Médico).
*   Cálculo de fechas calendario (se realiza al instanciar el plan, no al definir la plantilla).
*   Eliminación física de registros (solo inhabilitación).

---

### **Actores y Permisos**
*   **Administrador:** Acceso total (Leer, Crear, Editar, Cambiar vigencia).
*   **Médico:** Sin acceso a esta gestión (solo consumirá los planes vigentes en sus propios módulos).
*   **Paciente:** Sin acceso.

---

### **Dependencias / Supuestos**
*   Las tablas `plan_estandar` y `plan_estandar_actividad` existen en la base de datos según `GEMINI.md`.
*   El administrador está autenticado.
*   No se requiere un flujo de aprobación para los planes; la publicación es inmediata al guardar con estado vigente.

---

### **Flujo principal**

1.  El administrador accede al menú **"Planes Estandarizados"**.
2.  El sistema muestra el listado de planes (por defecto los vigentes).
3.  **Para Crear:**
    *   El admin presiona **"Nuevo Plan"**.
    *   Ingresa Nombre, Versión y Descripción.
    *   Agrega N actividades definiendo sus atributos y offsets.
    *   Confirma la operación.
    *   El sistema valida y persiste cabecera y detalles.
4.  **Para Editar:**
    *   Selecciona un plan existente.
    *   Modifica los datos y guarda.
5.  **Para Inhabilitar:**
    *   Desde el listado, cambia el switch o estado de "Vigente" a "No vigente".

---

### **Validaciones de negocio**

*   **Nombre:** Obligatorio y único entre planes vigentes.
*   **Versión:** Número entero positivo obligatorio.
*   **Actividades:**
    *   El plan debe tener **al menos una actividad** al crearse.
    *   `offset_inicio_dias` debe ser mayor o igual a 0.
    *   `offset_fin_dias` debe ser mayor o igual a `offset_inicio_dias`.
*   **Integridad:** No se permite borrar actividades que ya hayan sido instanciadas en pacientes (el sistema de plantillas debe manejar esto copiando datos al instanciar, desacoplando la plantilla de la instancia, por lo que la edición de la plantilla es segura para usos futuros pero no afecta pasados).

---

### **Criterios de aceptación**

**CA-1.** El listado muestra columnas: Nombre, Versión, Descripción (truncada), Estado y Acciones.
**CA-2.** El formulario permite agregar múltiples actividades dinámicamente antes de guardar.
**CA-3.** El sistema impide guardar una actividad si el día de fin es menor al día de inicio (offset).
**CA-4.** Al guardar, se insertan registros en `plan_estandar` y `plan_estandar_actividad`.
**CA-5.** La edición carga todos los datos actuales y permite modificar offsets y descripciones.
**CA-6.** La acción de inhabilitar actualiza el campo `vigente = false` y el plan deja de aparecer en los selectores de médicos (en futuras historias).

---

### **Casos borde y errores**

*   **Sin actividades:** Intentar guardar un plan sin filas en el detalle → Error "Debe agregar al menos una actividad".
*   **Offsets negativos:** Error de validación.
*   **Nombre duplicado:** Error "Ya existe un plan vigente con ese nombre".

---

### **Datos mínimos / Modelo**

**plan_estandar**
| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | PK | Identificador |
| `nombre` | VARCHAR | Nombre del protocolo |
| `version` | INT | Número de versión |
| `descripcion` | TEXT | Detalle |
| `fecha_creacion` | DATETIME | Fecha de alta |
| `vigente` | BOOL | 1=Activo, 0=Inactivo |

**plan_estandar_actividad**
| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | PK | Identificador |
| `plan_estandar_id` | FK | Relación con cabecera |
| `nombre` | VARCHAR | Nombre actividad |
| `descripcion` | TEXT | Detalle actividad |
| `offset_inicio_dias` | INT | Días desde inicio (0, 1, 2...) |
| `offset_fin_dias` | INT | Días plazo fin |
| `orden` | INT | Para ordenar en visualización |

---

### **Conclusión**
Esta historia sienta las bases para la estandarización clínica, permitiendo crear "recetas" de cuidado que luego serán asignadas masivamente.
