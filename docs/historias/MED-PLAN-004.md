# 🩺 Historia de Usuario — **MED-PLAN-004**

## Estados y cierre manual de planes de cuidado

### **ID**

`MED-PLAN-004`

### **Título**

*Como médico, quiero que los planes de cuidado gestionen estados “Sin iniciar”, “En curso” y “Finalizado”, y poder cerrarlos manualmente, para controlar su vigencia y evitar modificaciones cuando concluyen.*

---

### **Descripción / Contexto**

Los planes de cuidado requieren un flujo de vida claro: comienzan antes de su vigencia (**Sin iniciar**), se ejecutan durante el período definido (**En curso**) y se cierran explícitamente por el médico (**Finalizado**), aun cuando la fecha fin haya pasado. Esto asegura coherencia entre lo planificado y lo que el equipo permite modificar: un plan finalizado ya no debe admitir cambios ni acciones del paciente o del médico sobre sus actividades.

---

### **Alcance**

**Incluye:**

* Estados de plan acotados a **`sin_iniciar`**, **`en_curso`** y **`finalizado`** (slugs hardcodeados).
* Cálculo automático de estado visible por fecha: antes de `fecha_inicio` → *Sin iniciar*; desde `fecha_inicio` → *En curso*; “listo para finalizar” cuando pasó `fecha_fin` pero sin cerrar; cierre definitivo solo manual por el médico creador.
* Acción del médico para **finalizar** el plan, cambiando el estado a `finalizado`.
* Listados, filtros y KPI actualizados a estos estados; conteos de finalizados solo cuando el plan está marcado como tal.
* Bloqueos al estar **finalizado**: el médico no puede editar/eliminar/validar actividades; el paciente no puede marcar/desmarcar actividades.
* Normalización de estados previos a los tres permitidos, usando fecha fin para cerrar planes vencidos sin estado.

**No incluye:**

* Reapertura de planes finalizados.
* Estados adicionales ni ABM de estados.
* Cambios en el catálogo de estados de actividades (solo se respetan sus slugs actuales).

---

### **Actores y Permisos**

* **Médico** (propietario del plan): puede crear, ver, editar y finalizar el plan mientras no esté finalizado; después solo lectura.
* **Paciente** asignado: puede ver el plan y marcar/desmarcar actividades solo si el plan **no** está finalizado.
* **Administrador**: sin cambios de permisos específicos en esta historia.

---

### **Dependencias / Supuestos**

* Existe un plan de cuidado con `fecha_inicio` y `fecha_fin` válidas.
* El médico autenticado es el creador del plan.
* Catálogo de estados de actividad ya definido (`pendiente`, `completada`, `vencida`).
* Los listados y dashboards consumen el estado del plan para filtros/KPI.

---

### **Flujo principal**

1. El sistema calcula el estado visible del plan según fechas (`sin_iniciar` antes de `fecha_inicio`, `en_curso` desde `fecha_inicio`).
2. Al pasar `fecha_fin`, el plan se marca como “listo para finalizar” pero permanece **En curso** hasta acción explícita.
3. El médico ingresa al detalle del plan y usa la acción **Finalizar plan**.
4. El sistema marca el plan como `finalizado` y bloquea edición/validaciones y marcado de actividades tanto para médico como para paciente.
5. Listados/KPI reflejan los nuevos estados y conteos de finalizados.

---

### **Validaciones de negocio**

* Solo el **médico creador** puede finalizar el plan.
* El estado del plan solo puede ser uno de los tres permitidos.
* Un plan finalizado no admite: edición/eliminación del plan, validación/desvalidación de actividades, marcado/desmarcado por el paciente.
* Los filtros/kpi deben mapear correctamente estados previos al set permitido.

---

### **Criterios de aceptación**

**CA-1.** Los planes muestran uno de los tres estados con etiquetas “Sin iniciar”, “En curso” o “Finalizado”.  
**CA-2.** El estado visible se calcula por fecha (inicio/fin) salvo cuando esté marcado como `finalizado`.  
**CA-3.** Existe una acción para que el médico finalice el plan; al hacerlo, el estado pasa a `finalizado` y se bloquean acciones de edición/validación y marcado de actividades.  
**CA-4.** Paciente y médico no pueden marcar/desmarcar ni validar actividades cuando el plan está `finalizado`; se informa el motivo.  
**CA-5.** Listados, filtros y KPI muestran conteos por los tres estados, considerando finalizados solo los planes marcados como tales.  
**CA-6.** Los estados antiguos se normalizan a los tres permitidos, usando la fecha fin para cerrar planes vencidos sin estado.  

---

### **Casos borde y errores**

* Plan vencido (fecha fin < hoy) sin estado previo → se considera listo para finalizar; queda “En curso” hasta que el médico lo cierre.
* Intento de finalizar un plan ya `finalizado` → se informa que ya está cerrado.
* Intento de editar/eliminar/validar o marcar actividades en un plan finalizado → operación rechazada con mensaje claro.
* Estado almacenado fuera del set permitido → se normaliza al estado correcto según reglas de fecha.

---

### **Conclusión**

Esta historia define un flujo de estados claro para los planes de cuidado, garantiza cierres manuales controlados por el médico y protege la integridad del plan finalizado al bloquear modificaciones posteriores, manteniendo la consistencia en listados y métricas.
