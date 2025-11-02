# 🩺 Historia de Usuario — **MED-DASH-001**

## Dashboard del médico con métricas clínicas y de rendimiento

### **ID**

`MED-DASH-001`

### **Título**

*Como médico, quiero acceder a un panel de control con estadísticas sobre mis diagnósticos, pacientes y planes de cuidado, para monitorear mi desempeño y la evolución de mis pacientes.*

---

### **Descripción / Contexto**

El médico necesita una vista global de su actividad clínica y del progreso de los pacientes bajo su cuidado.
El **Dashboard Médico** resume los datos más relevantes sobre **diagnósticos creados**, **planes de cuidado**, **actividades validadas**, y la **evolución de los pacientes**, permitiéndole identificar tendencias y oportunidades de mejora en su práctica.

---

### **Alcance**

**Incluye:**

* Visualización de **indicadores clave (KPIs)**:

  * Total de pacientes diagnosticados.
  * Diagnósticos activos / nuevos en el mes.
  * Distribución por tipo de diagnóstico.
  * Total de planes de cuidado creados.
  * Porcentaje de planes finalizados.
  * Promedio de actividades por plan.
  * Porcentaje de actividades validadas.
  * Tiempo promedio de duración de los planes.
  * Tasa de adherencia del paciente (% de actividades completadas).
  * Total de pacientes bajo cuidado.
* **Gráficos visuales** (barras, líneas, pie chart) para diagnósticos y planes.
* **Tabla resumen de últimos diagnósticos** (paciente, tipo, fecha).


**No incluye:**

* Edición de datos.
* Creación o modificación de diagnósticos / planes.
* Exportación de métricas o reportes.

---

### **Actores y Permisos**

* **Médico:** único actor con acceso.
  El dashboard muestra solo información **de sus propios diagnósticos y planes.**

---

### **Dependencias / Supuestos**

* Existen tablas `Diagnostico`, `PlanDeCuidado`, `Actividad`, y sus relaciones (`diagnostico.medico_id`, `plan.medico_id`).
* Los estados de planes y actividades están normalizados (`activo`, `terminado`, `sin_iniciar`, etc.).
* Los datos estadísticos pueden calcularse dinámicamente o mediante vistas/materializadas según rendimiento.

---

### **Flujo principal**

1. El médico accede a **Dashboard** desde el menú lateral.
2. El sistema carga y muestra:

   * KPIs superiores (diagnósticos, pacientes, planes, validaciones).
   * Gráficos de distribución y tendencias.
   * Tabla con últimos diagnósticos y planes activos.
3. El médico puede:

   * Revisar métricas generales.
   * Detectar pacientes o planes con bajo rendimiento/adherencia.
   * Ir a detalles desde accesos rápidos (opcional: “ver todos los planes” o “ver diagnóstico”).

---

### **Criterios de aceptación**

**CA-1.** El médico ve estadísticas basadas exclusivamente en sus propios diagnósticos y planes.
**CA-2.** El dashboard muestra al menos los siguientes KPIs:

* Pacientes diagnosticados totales.
* Diagnósticos activos.
* Planes creados.
* Actividades validadas (%).
* Promedio de actividades por plan.
  **CA-3.** Las métricas se presentan de forma visual (gráficos, tarjetas, tablas).
  **CA-4.** El médico puede acceder al dashboard desde el menú principal.
  **CA-5.** La actualización de los datos es coherente (sin duplicados, datos desfasados o inconsistentes).
  **CA-6.** Los indicadores y gráficos responden correctamente aunque el médico no tenga datos (mostrar valores “0” o estados vacíos).

---

### **Casos borde y errores**

* Médico sin diagnósticos o planes → mostrar dashboard vacío con mensajes informativos.
* Fallo en la carga de datos → mostrar error genérico con opción de reintentar.
* Fechas de planes o actividades incoherentes → excluir del cálculo y registrar log.

