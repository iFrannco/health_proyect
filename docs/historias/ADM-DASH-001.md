# 🧭 Historia de Usuario — **ADM-DASH-001**

## Dashboard del administrador con estadísticas globales del sistema

### **ID**

`ADM-DASH-001`

### **Título**

*Como administrador, quiero acceder a un panel con estadísticas globales de usuarios, diagnósticos y planes de cuidado, para tener una visión general del funcionamiento del sistema en la clínica.*

---

### **Descripción / Contexto**

El administrador accede a la ruta **`/admin/home`** (o equivalente en el módulo Admin), donde se muestra un **dashboard informativo**, sin acciones directas, construido con el layout base de AdminLTE.
Este dashboard resume:

* La **composición de usuarios** del sistema por rol y estado.
* La **actividad clínica global** de los últimos 30 días (diagnósticos y planes de cuidado).
* Una **comparación entre médicos** en términos de cantidad de diagnósticos y planes creados, sin entrar en calidad de atención ni datos clínicos detallados por paciente.

El objetivo es que el administrador tenga una **visión general compacta pero útil**, similar en densidad al dashboard del médico (con algunos indicadores adicionales propios del rol de administración).

---

### **Alcance**

**Incluye**

1. **KPIs de usuarios (globales, sin límite temporal)**
   Tarjetas en la parte superior con los siguientes indicadores:

   * Total de **Pacientes** registrados.
   * Total de **Médicos** registrados.
   * Total de **Administradores**.
   * Total de **Usuarios inactivos** (cualquier rol con `activo = false`).

2. **KPIs de actividad clínica (últimos 30 días)**
   Segundo bloque de tarjetas, con métricas agregadas:

   * **Diagnósticos creados** en los últimos 30 días.

   * **Planes de cuidado creados** en los últimos 30 días.

   * **Actividades completadas** en los últimos 30 días.

   * **Planes de cuidado activos** actualmente (sin límite de tiempo, estado = activo).

   > // Comentario: el horizonte “últimos 30 días” se aplica a diagnósticos, planes creados y actividades completadas; si se desea cambiar a 7 días o a período configurable, revisar esta sección.

3. **Gráfico de distribución de usuarios por rol**

   * Gráfico tipo **dona** (donut chart) con la proporción de:

     * Pacientes
     * Médicos
     * Administradores
   * Leyenda con valores absolutos y porcentajes.

4. **Gráfico de distribución de planes por estado (global)**

   * Gráfico de barras o dona con:

     * Planes **activos**
     * Planes **futuros**
     * Planes **finalizados**
   * Se consideran todos los planes del sistema (no solo últimos 30 días), salvo que la implementación decida filtrar por periodo.

5. **Comparación entre médicos (planes y diagnósticos)**

   * Gráfico de barras agrupadas con eje X = médicos, eje Y = cantidad.
   * Dos series:

     * Cantidad de **diagnósticos creados** por médico (últimos 30 días).
     * Cantidad de **planes de cuidado creados** por médico (últimos 30 días).
   * Vista puramente cuantitativa (no se muestran datos de pacientes, ni calidad de atención, ni contenido clínico).

6. **Resumen de pacientes (global)**

   * Tarjeta o bloque resumen con:

     * Total de pacientes sin diagnósticos.
     * Total de pacientes con al menos un plan activo.

   *(Esto ayuda al admin a entender cobertura global sin bajar al detalle individual.)*

7. **Naturaleza informativa**

   * El dashboard **no incluye botones de acción** (crear, editar, navegar a detalles clínicos).
   * Como máximo puede tener **links suaves** a secciones administrativas (por ejemplo, “Ver usuarios”), pero no son parte del flujo principal de esta historia.

---

**No incluye**

* Navegación hacia fichas clínicas de pacientes.
* Visualización de diagnósticos, planes o actividades a nivel individual.
* Métricas de “calidad de atención” (por ejemplo, adherencia por médico, tiempos de respuesta, etc.).
* Estadísticas de uso del sistema (logins, actividad técnica, auditoría).
* Alertas avanzadas (reglas de negocio complejas, notificaciones de riesgo).

---

### **Actores y permisos**

* **Administrador**

  * Único actor con acceso a este dashboard.
* **Médicos y Pacientes**

  * No pueden acceder a `/admin/home` ni ver estas estadísticas.

---

### **Dependencias / Supuestos**

* Existen usuarios con roles definidos (**Paciente**, **Médico**, **Administrador**) y un campo `activo` que indica si están habilitados.
* Existen tablas y relaciones:

  * `Usuario` ↔ `Diagnostico` (medico_id / paciente_id)
  * `Diagnostico` ↔ `PlanDeCuidado`
  * `PlanDeCuidado` ↔ `Actividad`
* Los estados de plan (`activo`, `futuro`, `finalizado`) y de actividad (`pendiente`, `completada`, `vencida`) están normalizados.
* Las estadísticas de **últimos 30 días** se calculan a partir de la fecha de creación/registro (`fecha_creacion` o equivalente).

---

### **Flujo principal**

1. El administrador inicia sesión y accede a **`/admin/home`** mediante el menú (opción “Inicio” o “Dashboard”).
2. El sistema calcula y muestra los **KPIs de usuarios** (totales por rol, usuarios inactivos).
3. El sistema calcula y muestra los **KPIs de actividad clínica** de los últimos 30 días.
4. Se renderiza el **gráfico de distribución de usuarios por rol**.
5. Se renderiza el **gráfico de distribución de planes por estado**.
6. Se renderiza el **gráfico comparativo por médico**, con diagnósticos y planes creados en los últimos 30 días.
7. Se muestra un **resumen de pacientes** (sin diagnóstico / con plan activo).
8. El administrador puede revisar la información sin ejecutar acciones desde esta pantalla.

---

### **Criterios de aceptación**

**CA-1.** Al acceder a `/admin/home`, el administrador ve al menos 4 tarjetas de KPIs de usuarios: Pacientes, Médicos, Administradores, Usuarios inactivos.

**CA-2.** El dashboard muestra KPIs de actividad clínica para los últimos 30 días:

* Diagnósticos creados
* Planes de cuidado creados
* Actividades completadas
* Planes activos

**CA-3.** Se muestra un gráfico de **distribución de roles** (Pacientes / Médicos / Administradores) de forma clara.

**CA-4.** Se muestra un gráfico de **planes por estado** (activos, futuros, finalizados).

**CA-5.** Existe un gráfico que compara a los médicos con dos métricas:

* Diagnósticos creados por médico (últimos 30 días).
* Planes de cuidado creados por médico (últimos 30 días).

**CA-6.** El administrador no puede ver desde este dashboard datos de pacientes individuales ni contenido clínico detallado (descripciones de diagnósticos, actividades, etc.).

**CA-7.** El dashboard es **solo informativo**: no hay botones de alta/edición/eliminación de datos.

**CA-8.** Si no hay datos en algún bloque (por ejemplo, ningún plan creado en los últimos 30 días), se muestra un mensaje neutro (“No hay datos para el período seleccionado”) y el gráfico/contador refleja 0.

**CA-9.** Usuarios que no son administradores no pueden acceder a este dashboard y reciben un mensaje de “Acceso denegado” o equivalente.

---

### **Casos borde y errores**

* **Sin datos clínicos aún** (no hay diagnósticos, planes ni actividades):

  * Todas las métricas clínicas muestran 0.
  * Los gráficos correspondientes muestran un estado vacío.
* **Sin médicos registrados**:

  * El gráfico comparativo por médico no se muestra o indica claramente que no hay médicos.
* **Errores de consulta o BD**:

  * El sistema muestra un mensaje de error genérico en el panel correspondiente, sin romper toda la página.
* **Falta de permisos**:

  * Si un usuario no admin intenta acceder a `/admin/home`, se le redirige o se muestra un mensaje claro de acceso denegado.

---

### **Modelo de datos / Impacto**

La historia es **solo de lectura**:

* No se crean, modifican ni eliminan registros.
* Se consumen datos de:

  * `usuarios` (roles, activo/inactivo)
  * `diagnosticos` (fecha_creacion, medico_id)
  * `planes_de_cuidado` (fecha_creacion, estado, medico_id)
  * `actividades` (fecha_completado, estado)

---

### **UX / UI sugerida**

* Layout usando **AdminLTE**, consistente con los otros dashboards.

* Estructura sugerida:

  1. **Fila 1 – KPIs de usuarios** (4 tarjetas):

     * Pacientes
     * Médicos
     * Administradores
     * Usuarios inactivos

  2. **Fila 2 – KPIs clínicos** (4 tarjetas):

     * Diagnósticos últimos 30 días
     * Planes últimos 30 días
     * Actividades completadas últimos 30 días
     * Planes activos

  3. **Fila 3 – Gráficos**:

     * Columna izquierda: Dona usuarios por rol
     * Columna derecha: Dona/barras de planes por estado

  4. **Fila 4 – Comparativa médicos**:

     * Gráfico de barras (médicos vs diagnósticos y planes creados últimos 30 días).

  5. **Fila 5 – Resumen pacientes** (opcional, una tarjeta):

     * Pacientes sin diagnóstico
     * Pacientes con plan activo

* Colores y símbolos coherentes con otros módulos (sin introducir una estética nueva).

