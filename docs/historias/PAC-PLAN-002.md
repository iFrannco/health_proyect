# 👤 Historia de Usuario — **PAC-PLAN-002**

## Visualizar el médico responsable del plan

### **ID**

`PAC-PLAN-002`

### **Título**

*Como paciente, quiero visualizar el nombre del médico que preparó mi plan de cuidado, para saber quién es mi referente asistencial.*

---

### **Descripción / Contexto**

El paciente necesita identificar de forma clara quién elaboró y supervisa el plan de cuidado asignado. Esto refuerza la confianza, facilita la comunicación y permite saber a quién dirigirse ante dudas sobre actividades o cambios en el tratamiento.

---

### **Alcance**

**Incluye:**

* Mostrar el **nombre y apellido** del médico responsable en el listado de planes de cuidado del paciente.
* Mostrar el nombre y apellido del médico responsable en la vista de detalle del plan, junto a los datos básicos (diagnóstico, fechas, estado).
* Indicar la **especialidad** del médico cuando esté disponible.
* Reflejar el cambio de médico responsable si el plan es reasignado.

**No incluye:**

* Cambiar o solicitar cambio de médico desde la vista del paciente.
* Mostrar datos de contacto (teléfono, email) ni habilitar mensajería.
* Modificar la información clínica del plan.

---

### **Actores y Permisos**

* **Paciente:** visualiza el nombre del médico responsable de cada plan asignado.
* **Médico / Administrador:** gestionan la asignación del médico responsable desde su módulo correspondiente.

---

### **Dependencias / Supuestos**

* Cada `PlanDeCuidado` tiene un `medico_id` asociado al profesional que lo preparó o supervisa.
* El sistema puede resolver el nombre, apellido y especialidad del médico a partir de `medico_id` (tabla `users` o `medicos`).
* El paciente solo puede ver planes que le fueron asignados y sus datos asociados.

---

### **Flujo principal**

1. El paciente ingresa a **"Planes de cuidado"**.
2. En el listado, cada plan muestra el **nombre del médico responsable** junto al estado y fechas.
3. El paciente abre el detalle de un plan.
4. En la cabecera del detalle se muestra el **nombre del médico responsable** (y su especialidad si aplica).
5. Si el plan cambia de médico responsable, la vista refleja el nuevo nombre sin acciones adicionales del paciente.

---

### **Criterios de aceptación**

**CA-1.** En el listado de planes, cada plan muestra el nombre y apellido del médico que lo preparó.
**CA-2.** En el detalle de un plan, se muestra de forma destacada el nombre del médico responsable y su especialidad si existe.
**CA-3.** Si el plan es reasignado a otro médico, la información visible para el paciente se actualiza al nuevo profesional.
**CA-4.** Si el plan no tiene médico asociado, se muestra el mensaje "Médico responsable no disponible" sin bloquear el acceso al plan.
**CA-5.** El nombre del médico respeta el layout del módulo de paciente y no desplaza otros datos principales del plan.

---

### **Casos borde y errores**

* Plan sin `medico_id` o médico eliminado → mostrar "Médico responsable no disponible".
* Médico desactivado → se muestra igualmente el nombre del medico.
* Error al cargar los datos del médico → se mantiene la vista del plan y se muestra aviso de error de datos del médico.

---

### **Datos mínimos / Modelo**

**Entidad: PlanDeCuidado**

| Atributo        | Tipo          | Descripción                                        |
| ----------------| ------------- | -------------------------------------------------- |
| `id`            | INT (PK)      | Identificador del plan de cuidado                  |
| `paciente_id`   | FK→usuarios   | Paciente al que pertenece el plan                  |
| `medico_id`     | FK→usuarios   | Médico que preparó/supervisa el plan               |
| `diagnostico_id`| FK→diagnosticos | Diagnóstico asociado al plan                      |
| `estado`        | ENUM          | Estado del plan (activo, futuro, finalizado)       |
| `fecha_inicio`  | DATE          | Fecha de inicio del plan                           |
| `fecha_fin`     | DATE          | Fecha de fin del plan                              |

**Entidad: Usuario (médico)**

| Atributo      | Tipo          | Descripción                              |
| ------------- | ------------- | ---------------------------------------- |
| `id`          | INT (PK)      | Identificador del médico                 |
| `nombre`      | VARCHAR(120)  | Nombre del médico                        |
| `apellido`    | VARCHAR(120)  | Apellido del médico                      |
| `especialidad`| VARCHAR(120)  | Especialidad médica (si aplica)          |

---

### **Conclusión**

La historia asegura que el paciente identifique a su médico responsable en todo momento, mejorando transparencia y facilitando la comunicación futura.
