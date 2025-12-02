# 🩺 Historia de Usuario — MED-PLAN-003

## Búsqueda de paciente para nuevo plan de cuidado

### **ID**

`MED-PLAN-003`

### **Título**

*Como médico, quiero buscar un paciente por nombre o DNI al crear un nuevo plan de cuidado, para seleccionar rápidamente al paciente correcto sin depender de un selector estático.*

---

### **Descripción / Contexto**

Durante la creación de un **plan de cuidado** personalizado, el médico necesita identificar al paciente destinatario. El selector actual de pacientes se reemplaza por un **cuadro de búsqueda** que permite localizar pacientes por nombre o DNI, mostrando solo la información mínima necesaria para confirmar la identidad antes de continuar con la asociación al diagnóstico y la planificación del cuidado.

---

### **Alcance**

**Incluye:**

* Cuadro de búsqueda en la pantalla de **creación de plan** para localizar pacientes por **nombre** o **DNI**.
* Búsqueda que devuelve coincidencias parciales (prefijo o subcadena) y sin sensibilidad a mayúsculas/minúsculas.
* Listado de resultados con **nombre completo** y **DNI** para seleccionar al paciente destinatario.
* Selección de un paciente del listado para continuar el flujo existente de elección de diagnóstico y definición del plan.
* Mensaje de “sin resultados” cuando no haya coincidencias y opción de reintentar.

**No incluye:**

* Autocompletado avanzado con ranking fonético o sugerencias por historial.
* Filtrado por otros atributos (edad, email, estado clínico).
* Registro o edición de pacientes.
* Uso del cuadro de búsqueda fuera del flujo de **creación de plan de cuidado**.

---

### **Actores y Permisos**

* **Médico** autenticado: puede buscar y seleccionar pacientes para iniciar un plan.
* **Paciente** y **Administrador**: fuera de alcance para esta historia.

---

### **Dependencias / Supuestos**

* Existen usuarios con rol **paciente** y atributo `dni` poblado.
* El médico está autenticado y cuenta con permiso para crear planes de cuidado.
* El formulario de **nuevo plan de cuidado** ya existe y actualmente incluye un selector de pacientes que será sustituido.
* El estado `activo` del paciente se respeta: solo se listan pacientes activos.

---

### **Flujo principal**

1. El médico accede a **Planes → Nuevo**.
2. El sistema muestra un **cuadro de búsqueda** de pacientes (sustituye al selector previo).
3. El médico ingresa parte del **nombre** o el **DNI** y ejecuta la búsqueda.
4. El sistema valida el término, consulta pacientes activos con rol paciente y muestra la lista de coincidencias con nombre y DNI.
5. El médico selecciona un paciente de los resultados.
6. El sistema confirma la selección y permite continuar con el flujo existente: elegir diagnóstico del paciente y definir datos del plan.
7. Si no hay resultados, se informa el mensaje correspondiente y el médico puede ajustar el criterio y reintentar.

---

### **Validaciones de negocio**

* La búsqueda solo considera **usuarios con rol paciente** y `activo = true`.
* El término de búsqueda debe ser válido: mínimo 2 caracteres para nombre o patrón de DNI numérico permitido.
* Búsqueda **no sensible a mayúsculas** y con coincidencias parciales en nombre; para DNI puede usarse coincidencia parcial o exacta según formato disponible.
* Debe seleccionarse exactamente **un** paciente antes de avanzar a la selección de diagnóstico.
* Si el paciente seleccionado queda inactivo entre la búsqueda y la confirmación, se rechaza la selección y se solicita una nueva búsqueda.

---

### **Criterios de aceptación**

**CA-1.** El selector previo de pacientes desaparece en la creación de plan y se reemplaza por un **cuadro de búsqueda**.  
**CA-2.** El médico puede buscar pacientes por **nombre** (parcial, insensible a mayúsculas) o por **DNI**.  
**CA-3.** Los resultados muestran solo **nombre completo** y **DNI** del paciente.  
**CA-4.** Al seleccionar un paciente, el sistema confirma la selección y habilita el paso de elección de diagnóstico del paciente.  
**CA-5.** Si no hay coincidencias, se muestra un mensaje claro de **sin resultados** y se permite reintentar sin abandonar el formulario.  
**CA-6.** Pacientes **inactivos** o con rol distinto a paciente no aparecen en los resultados.  
**CA-7.** Entradas de búsqueda inválidas (longitud insuficiente o formato de DNI incorrecto) muestran feedback y no disparan la consulta.  
**CA-8.** El flujo continúa sin cambios posteriores a la selección (elección de diagnóstico y definición del plan se mantienen como hoy).

---

### **Casos borde y errores**

* Búsqueda con menos de 2 caracteres → mensaje de validación y bloqueo de la consulta.
* DNI con caracteres no numéricos → mensaje de validación y bloqueo de la consulta.
* Paciente encontrado pero se vuelve inactivo antes de seleccionar → al confirmar se rechaza y se solicita nueva búsqueda.
* Fallo en la consulta a datos de pacientes → mensaje genérico de error, sin perder los datos ya cargados del plan.

---

### **Conclusión**

La historia asegura que el médico pueda identificar al paciente correcto de forma ágil y segura al iniciar un plan de cuidado, sustituyendo el selector estático por una búsqueda controlada por nombre o DNI sin alterar el resto del flujo de planificación.
