# 👤 Historia de Usuario — **PAC-DASH-001**

## Visualizar dashboard con resumen general de planes, diagnósticos y actividades

### **Título**

*Como paciente quiero ver un panel general con información resumida sobre mis diagnósticos, planes de cuidado y actividades, para entender mi evolución y mantenerme al tanto de mi progreso.*

---

### **Descripción / Contexto**

Al ingresar al sistema, el paciente accede automáticamente a la ruta **`/paciente/home`**, donde se muestra su **dashboard personal**.
Este panel ofrece una visión consolidada de su situación clínica actual, los diagnósticos que tiene o tuvo, los planes de cuidado relacionados, y el estado de sus actividades.
No tiene funciones de gestión (crear, editar o filtrar), sino que actúa como **centro de información y seguimiento personal**.

---

### **Alcance**

**Incluye**

* **Resumen general** (tarjetas KPI en la parte superior):

  1. **Diagnósticos activos** (asociados a planes vigentes).
  2. **Planes de cuidado activos**.
  3. **Planes completados** (ya finalizados).
  4. **Actividades completadas**.
  5. **Actividades pendientes**.
  6. **Actividades vencidas**.
* **Gráfico de distribución de actividades**:

  * Gráfico circular o de dona con proporciones de estados (**pendiente**, **completada**, **vencida**).
  * Tooltip con conteo total.
* **Gráfico de progreso por plan**:

  * Gráfico de barras mostrando porcentaje de avance en cada plan.
  * Si no tiene planes activos, mostrar placeholder informativo.
* **Lista de próximas actividades**:

  * Hasta **5 actividades más próximas** ordenadas por fecha de inicio.
  * Cada fila incluye nombre, tipo, fechas, estado y checkbox para marcar como **completada**.
  * Al marcar una actividad:

    * Se abre un **modal** opcional para agregar comentario.
    * Se actualizan métricas y gráficos.
  * Validaciones:

    * ❌ No puede marcar como completada antes de la fecha de inicio.

      > // Comentario: Si en el futuro se permite anticipar actividades, revisar esta validación.
    * ❌ No puede marcar como completada si está vencida.

      > // Comentario: Dejar visible para cambio de política.
* **Resumen adicional**:

  * Sección lateral o tarjeta inferior con contador de **diagnósticos totales** (activos + históricos).
  * Estadística simple: *Promedio de actividades completadas por plan*.
* **Avisos del día (alertas destacadas)**:

  * Si el paciente tiene actividades que comienzan hoy, mostrar un aviso tipo callout con contador (“Hoy tenés 2 actividades que inician”).

**No incluye**

* Filtros de planes ni navegación entre ellos.
* Visualización de documentos o análisis clínicos.
* Creación o edición de actividades.
* Comparativas entre distintos períodos de tiempo.

---

### **Actores y permisos**

* **Paciente:** acceso único y completo a su propio dashboard.
* **Médico o administrador:** sin acceso a esta vista.

---

### **Dependencias / Supuestos**

* El paciente puede tener o no diagnósticos registrados.
* Los planes de cuidado están correctamente asociados a diagnósticos.
* Las actividades tienen sus fechas y estados actualizados.
* Estados de actividad: **pendiente**, **completada**, **vencida**.
Las métricas y gráficos deben adaptarse automáticamente:
* Si no hay diagnósticos ni planes → mostrar todos los valores en 0 y un mensaje neutro (“Aún no tenés registros clínicos asignados”).

---

### **Flujo principal**

1. El paciente ingresa al sistema y es redirigido automáticamente a **`/paciente/home`**.
2. Se muestran las **tarjetas KPI** con los totales generales.
3. Debajo, aparecen los **gráficos de distribución y progreso**.
4. Luego, se listan las **5 próximas actividades** con su checkbox.
5. El paciente puede marcar una actividad como completada (si cumple con las validaciones).
6. Se actualizan las métricas y gráficos sin recargar la página.
7. Si existen actividades que comienzan hoy, aparece un aviso destacado en la parte superior del dashboard.
8. Si no existen planes ni diagnósticos, se muestra un mensaje informativo (“Aún no tenés registros clínicos asignados”).

---

### **Criterios de aceptación (CA)**

**CA-1.** Al ingresar a `/paciente/home`, el paciente visualiza un resumen general de sus diagnósticos, planes y actividades.
**CA-2.** Las tarjetas KPI muestran datos actualizados de diagnósticos, planes y actividades en sus distintos estados.
**CA-3.** Los gráficos reflejan correctamente la proporción y avance de las actividades.
**CA-4.** La lista de próximas actividades muestra hasta 5 actividades futuras ordenadas por fecha.
**CA-5.** Al marcar una actividad como completada, se valida la fecha y se actualizan los indicadores sin recarga.
**CA-6.** Las validaciones temporales (inicio/vencimiento) se aplican de forma coherente con el resto del sistema.
**CA-7.** Si existen actividades que comienzan hoy, se muestra una alerta visual destacada.
**CA-8.** El diseño mantiene coherencia con el dashboard del médico y el layout general del sistema.
**CA-9.** Si el paciente no tiene planes ni diagnósticos, se muestra un mensaje neutral y vacío de acciones.

---

### **Casos borde y errores**

* Sin planes → mensaje informativo.
* Sin diagnósticos → mensaje informativo.
* Sin actividades próximas → placeholder “No tenés actividades próximas”.
* Error de red al marcar actividad → revertir checkbox y mostrar alerta.
* Actividad vencida o fuera de rango → mensaje de validación clara (“No podés marcar esta actividad fuera del período válido”).

---

### **UX / UI sugerida**

* **Header:** saludo dinámico con nombre del paciente.
* **Bloque 1 (tarjetas KPI):** 6 tarjetas con totales generales.
* **Bloque 2 (gráficos):**

  * Pie chart de distribución de estados.
  * Barra de avance por plan.
* **Bloque 3 (actividades próximas):**

  * Tabla o lista con checkboxes.
* **Bloque 4 (resumen adicional):**

  * Diagnósticos totales y promedio de avance.
* **Bloque 5 (avisos de hoy):**

  * Callout superior con contador de actividades que comienzan hoy.

