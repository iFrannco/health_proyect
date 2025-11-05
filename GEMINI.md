# **agents.md**

## **Propósito**

Eres un generador de código para **PHP 8.3 \+ CodeIgniter 4** con **AdminLTE 3**.  
 Debes **respetar estrictamente** la estructura de carpetas y el **modelo de datos** de este documento.  
 No renombres ni muevas carpetas fuera de lo indicado.

---

## **Stack**

* PHP **8.3.14**

* CodeIgniter **4.x**

* Apache **2.4**

* MySQL **9.1.0**

* AdminLTE **3** (archivos copiados en `public/adminlte/`)

---
## **Flujo de trabajo para git:**
**Inicio de la jornada laboral:**
 *   Almacenar los cambios no confirmados (uncommitted) en el *stage* para asegurar su persistencia.
 *   Actualizar la rama de trabajo actual.
 *   Integrar los cambios previamente almacenados del *stage* con la rama actualizada. (En caso de surgir un conflicto, se notificará al usuario y se le permitirá resolverlo).

**Confirmación y envío del trabajo realizado:**
 *   Solicitar la *Historia de Usuario* que se está implementando.
 *   Evaluar la Historia de Usuario documentada en la carpeta `docs` junto con las modificaciones efectuadas. Luego, dividir los cambios en múltiples *commits* y sugerir tres mensajes descriptivos para cada uno (presentar las sugerencias de a un *commit* para facilitar la comprensión).
 *   Una vez que el usuario haya seleccionado el mensaje para cada *commit*, ejecutar un `git fetch origin` para prevenir conflictos.
 *   Posteriormente, realizar un `git rebase` para actualizar la rama. Si se presenta un conflicto, se informará al usuario y se le delegará su resolución.
 *   Finalmente, subir los cambios al repositorio remoto mediante `git push`.
---

## **Estructura del proyecto (obligatoria)**

* `agents.md` (este archivo) en **raíz**

* `docs/historias/` (vacío por ahora)

* `app/`

  * `Config/` (ajustar **solo** `Routes.php` y `Filters.php`)

  * `Controllers/`

    * `Auth/` → `Login.php`, `Register.php`, `Logout.php`

    * `Admin/` → `Usuarios.php`, `Estadisticas.php`, `PlanesEstandar.php`

    * `Medico/` → `Diagnosticos.php`, `Planes.php`, `Validaciones.php`, `Estadisticas.php`

    * `Paciente/` → `Perfil.php`, `Documentacion.php`, `Planes.php`, `Historial.php`

    * `Common/` → `Home.php`

    * `BaseController.php`

  * `Filters/` → `AuthFilter.php`, `RoleFilter.php`

  * `Helpers/` → `menu_helper.php`

  * `Libraries/` → `CarePlanTemplate.php` *(materializa plantillas en actividades con fechas concretas)*q

  * `Entities/` → `User.php`, `Diagnostico.php`, `PlanCuidado.php`, `Actividad.php`, `Documento.php`, `PlanEstandar.php`, `PlanEstandarActividad.php`

  * `Models/` → `UserModel.php`, `RoleModel.php`, `DiagnosticoModel.php`, `PlanCuidadoModel.php`, `ActividadModel.php`, `DocumentoModel.php`, `PlanEstandarModel.php`, `PlanEstandarActividadModel.php`, `EstadoActividadModel.php`

  * `Views/`

    * `layouts/base.php`

    * `layouts/partials/` → `navbar.php`, `footer.php`, `sidebar_admin.php`, `sidebar_medico.php`, `sidebar_paciente.php`

    * `auth/` → `login.php`, `register.php`

    * `admin/usuarios/index.php`, `admin/estadisticas/index.php`, `admin/planes_estandar/index.php`

    * `medico/diagnosticos/index.php`, `medico/planes/index.php`, `medico/validaciones/index.php`, `medico/estadisticas/index.php`

    * `paciente/perfil/index.php`, `paciente/documentacion/index.php`, `paciente/planes/index.php`, `paciente/historial/index.php`

    * `common/home.php`

  * `Database/Migrations/`, `Database/Seeds/`

  * `Validation/` → `user_rules.php`

* `public/`

  * `adminlte/` (dist/, plugins/)

  * `assets/css/`, `assets/js/`, `assets/img/`

  * `uploads/` (documentación médica)

* `writable/` (logs, cache, session)

* `composer.json`, `.env.example`, `spark`

Conserva `.gitkeep` en carpetas vacías. Usa **un** layout base y **sidebars por rol**.

---

## **Seguridad y roles**

* Cada usuario tiene **exactamente un rol** → FK `usuarios.role_id` (N–1 contra `roles`).

* Tablas: `usuarios`, `roles (slug: admin|medico|paciente)`.

* No existen perfiles separados (`medicos`/`pacientes`); los datos particulares viven en la propia fila de `usuarios`.

* Filters:

  * `AuthFilter`: requiere sesión.

  * `RoleFilter`: acepta múltiples roles, p.ej. `role:admin,medico`.

* Seeds obligatorios: `roles` (tres básicos) y **admin inicial**.

---

## **Tablas mínimas (definitivas)**

### **`usuarios`**

* `id` (PK), `dni`, `nombre`, `email` (UNIQUE), `password_hash`, `role_id` (FK→roles.id), `fecha_nac` (DATE NULL), `matricula` (VARCHAR NULL), `activo`, timestamps

### **`roles`**

* `id` (PK), `slug` (UNIQUE: `admin|medico|paciente`), `nombre`

### **`tipo_diagnostico` *(catálogo fijo / hardcodeado)***

* `id` (PK), `nombre`

### **`diagnosticos`**

* `id` (PK)

* `autor_user_id` (FK→usuarios.id) — usuario que registra el diagnóstico

* `destinatario_user_id` (FK→usuarios.id) — usuario al que aplica el diagnóstico

* `tipo_diagnostico_id` (FK→tipo\_diagnostico.id)

* `descripcion`, `fecha_creacion`

### **`plan_estandar`**

* `id` (PK), `nombre`, `version` (INT), `descripcion`, `fecha_creacion`, `vigente` (BOOL)

### **`plan_estandar_actividad` *(actividad de plantilla – fechas relativas)***

* `id` (PK), `plan_estandar_id` (FK→plan\_estandar.id)

* `Descripcion, nombre`

* `offset_inicio_dias` (INT), `offset_fin_dias` (INT)

* `orden` (INT)

### **`planes_cuidado` *(instancia para un diagnóstico)***

* `id` (PK)

* `diagnostico_id` (FK→diagnosticos.id, **NOT NULL**)

* `plan_estandar_id` (FK→plan\_estandar.id, **NULLABLE**; setear si proviene de una plantilla)

* `estado` (STRING; progreso global del plan)

* `fecha_creacion`, `fecha_inicio`, `fecha_fin`

**Regla**: el usuario destinatario se obtiene transitivamente por `diagnostico → destinatario_user_id`.

### **`estado_actividad` *(catálogo)***

* `id` (PK), `nombre` (UNIQUE: `sin_iniciar`, `iniciada`, `terminada`), `orden` (INT)

* **Seed obligatorio** con los 3 estados.

### **`actividades` *(instanciadas, fechas concretas)***

* `id` (PK), `plan_id` (FK→planes\_cuidado.id)

* `estado_id` (FK→estado\_actividad.id) — **estado actual**

* `validada` (BOOL DEFAULT FALSE), `fecha_validacion` (DATETIME NULL)

* `Descripcion, nombre`

* `fecha_creacion`, `fecha_inicio`, `fecha_fin`

**Reglas**:

* `validada = TRUE` **solo** si `estado_id` \= `terminada`.

* Cambios en una plantilla **no** alteran actividades ya materializadas (copiar `descripcion, nombre`  al crear).

### **`documentacion`**

* `id` (PK), `usuario_id` (FK→usuarios.id), `url`, `created_at`

---

## **Relaciones clave (resumen)**

* Usuario → Rol (N–1 mediante `usuarios.role_id`).

* Usuario (rol médico) 1–N Diagnósticos como autor; Usuario (rol paciente) 1–N Diagnósticos como destinatario.

* Diagnóstico 1–N Planes de cuidado.

* **Plan de cuidado** N–1 **Plan estandar** (nullable) y 1–N **Actividades (instancias)**.

* Actividad 1–1 Estado actual (FK a catálogo) \+ validación (bool \+ fecha).

---

## **Comportamiento de plantillas**

* `plan_estandar` \+ `plan_estandar_actividad` definen **reglas relativas** (offsets).

* Al asignar un plan estandarizado:

  * se crea `planes_cuidado` (con `plan_estandar_id` y `fecha_inicio` concreta),

  * la **Library `CarePlanTemplate`** materializa cada `plan_estandar_actividad` en filas de `actividades`:

    * `fecha_inicio = plan.fecha_inicio + offset_inicio_dias`

    * `fecha_fin = plan.fecha_inicio + offset_fin_dias`

    * `estado_id = (sin_iniciar)` por defecto

    * `validada = false`, `fecha_validacion = NULL`

    * Copiar `descripcion` (snapshot)

---

## **Reglas de generación / límites**

* **No** implementar CRUD de `tipo_diagnostico` (catálogo fijo).

* **Un** layout base.

* **Sin** lógica de negocio en vistas/controladores: usar Models/Entities/Libraries.  

* **Nomenclatura**:

  * Los nombres de **variables, métodos y clases** deben estar en **español**.

  * Utilizar `camelCase` para variables y métodos.

  * Utilizar `PascalCase` (o `UpperCamelCase`) para nombres de clases (Controladores, Modelos, Entidades, etc.).

* **Uso de Bibliotecas Externas**:

  * **Restricción Principal**: Al generar código PHP, debes limitarte a usar soluciones nativas de PHP siempre que sea posible.

  * **Excepción y Aprobación**: Si identificas un escenario donde una biblioteca externa es indispensable (por ejemplo, por razones de seguridad, complejidad o ahorro significativo de tiempo), debes detenerte. En ese momento, tu tarea es informarme sobre la biblioteca que consideras necesaria y justificar por qué es la única opción viable. Solo podrás proceder a implementarla después de recibir mi aprobación explícita.


## 🩺 Directiva: Formato estandarizado de Historias de Usuario

**Objetivo:**  
Toda historia de usuario del proyecto debe seguir esta estructura y formato.  
El agente que genere o modifique historias deberá respetar la convención de identificación, secciones y estilo detalladas a continuación. (Las historias se almacenan en la carpeta /docs/historias).

---

### 📘 Convención de Identificación

Cada historia de usuario tiene un código único con la siguiente estructura:

<ROL>-<MÓDULO>-<NÚMERO>


Ejemplos:
- `MED-DIAG-001` → Médico / Diagnóstico / #1  
- `PAC-DOC-002` → Paciente / Documentación / #2  
- `ADM-USR-001` → Administrador / Gestión de usuarios / #1  

---

### 📄 Estructura base de cada historia

El contenido generado debe respetar exactamente los títulos y orden:

1. **ID**  
   Código de la historia según convención.

2. **Título**  
   Frase en formato:  
   *“Como [rol], quiero [acción], para [beneficio o propósito]”*

3. **Descripción / Contexto**  
   Breve descripción del objetivo de la historia y su importancia dentro del dominio.

4. **Alcance**  
   Dividido en dos subapartados:

   **Incluye:**
   - Lista clara de acciones o comportamientos cubiertos.

   **No incluye:**
   - Lista de funciones o tareas explícitamente fuera de alcance.

5. **Actores y Permisos**  
   Especificar quién puede ejecutar la acción y con qué nivel de acceso.

6. **Dependencias / Supuestos**  
   Condiciones previas necesarias para que la historia sea válida (por ejemplo, existencia de usuarios, catálogos, autenticación).

7. **Flujo principal**  
   Pasos numerados que describen el comportamiento esperado en orden cronológico.

8. **Validaciones de negocio**  
   Reglas funcionales y restricciones aplicables a los datos o procesos.

9. **Criterios de aceptación**  
   Enumerados como `CA-1`, `CA-2`, etc.  
   Cada uno representa una condición verificable para considerar completada la historia.

10. **Casos borde y errores**  
    Situaciones excepcionales, validaciones fallidas y respuestas esperadas.

11. **Datos mínimos / Modelo**  
    Campos mínimos que intervienen o tablas afectadas.  
    Se presenta en formato tabla con nombre, tipo y descripción.

12. **Conclusión (opcional)**  
    Resumen del objetivo y propósito de negocio que cumple la historia.

---

### 🧱 Convenciones adicionales

- Los criterios de aceptación deben ser **claros, verificables y numerados**.  
- La descripción y el flujo deben escribirse en **modo declarativo**, sin instrucciones de implementación.  
- No incluir secciones de UI, rutas, pruebas Gherkin ni Definition of Done, salvo que se indique lo contrario.  
- El lenguaje debe mantenerse **en español técnico-académico**.  
- Se debe garantizar coherencia con el modelo de clases actual del dominio.

---

### 🧩 Ejemplo base

*(Resumen de ejemplo, usado como guía estructural)*

ID: MED-DIAG-001
Título: Como médico, quiero dar de alta un diagnóstico para un paciente, para registrar el motivo clínico y vincularlo posteriormente con un plan de cuidado.

Descripción / Contexto:
El diagnóstico constituye el punto de partida del proceso clínico y permite al médico registrar formalmente una evaluación del paciente...

Alcance:
Incluye:

    Alta de diagnósticos desde el módulo del médico.

    Validaciones de integridad (paciente, tipo de diagnóstico, campos obligatorios).

No incluye:

    Edición o eliminación de diagnósticos.

    Adjuntos médicos.

Actores y Permisos:

    Médico: puede crear diagnósticos.

    Paciente: no puede crearlos.

Dependencias / Supuestos:

    Catálogo TipoDiagnostico precargado.

Flujo principal:

    El médico accede al módulo Diagnósticos → Nuevo.

    Completa los campos requeridos y confirma.

    El sistema valida, guarda y muestra mensaje de éxito.

Validaciones de negocio:

    Descripción obligatoria, longitud 10–2000.

    Tipo de diagnóstico válido.

Criterios de aceptación:
CA-1. Se crea el diagnóstico con la fecha de creación automática.
CA-2. Solo médicos autenticados pueden hacerlo.
CA-3. Campos faltantes muestran errores y bloquean guardado.

Casos borde y errores:

    Paciente inexistente o tipo inválido → mensaje de error.

Datos mínimos / Modelo:
Campo	Tipo	Descripción
id	INT	Identificador único
medicoResponsable	FK→Usuario	Médico que crea el diagnóstico
paciente	FK→Usuario	Paciente diagnosticado
tipoDiagnostico	FK→TipoDiagnostico	Tipo de diagnóstico
descripcion	TEXT	Detalle clínico
fechaCreacion	DATE	Generada automáticamente
planDeCuidado	FK→PlanDeCuidado (nullable)	Si existe

Conclusión:
La historia define la creación del diagnóstico como punto de partida clínico del sistema.


---

### 🔧 Instrucción al generador

> Cuando se solicite generar una nueva historia de usuario, seguir **exactamente** la estructura anterior, conservando el estilo, los encabezados y la numeración.  
> Mantener coherencia con el modelo de clases actual y roles definidos (Administrador, Médico, Paciente).  
> No incluir secciones omitidas explícitamente (UI, Rutas, DoD, Gherkin).

---


Excelente idea — lo que querés es establecer **una regla de coherencia entre la documentación, el código y las historias de usuario**, y además permitir **que la IA modifique o actualice esas fuentes de forma controlada y trazable**.
Esto se puede expresar en tu `agents.md` como una **política de consistencia y sincronización**.

Aquí te dejo un bloque listo para agregar al final del archivo, después de la sección de formato de historias que ya tenés 👇

---

## ⚙️ Directiva: Consistencia entre código, documentación y modelo

**Objetivo:**  
Garantizar la coherencia entre las implementaciones del sistema, las historias de usuario, las rutas, los modelos de base de datos y la documentación general del proyecto (incluido este archivo `agents.md`).

---

### 📚 1. Principio de coherencia global
Toda descripción, modelo o especificación que figure en:
- `/docs/historias/` (historias de usuario),
- `/app/Models/`, `/app/Controllers/`, `/app/Database/Migrations/` (implementaciones reales),
- y en el presente archivo `agents.md`,
  
debe **representar la misma estructura funcional y semántica**.  
Cualquier discrepancia detectada por el agente (IA) o por un desarrollador deberá ser **reportada explícitamente** en el cuerpo del mensaje o commit.

---

### 🧠 2. Validación de coherencia
El agente debe verificar antes de desarrollar o modificar funcionalidades:
- Que las **rutas** mencionadas en historias de usuario existan o estén registradas en el router del proyecto.  
- Que los **nombres de tablas y columnas** descritos en los modelos o historias coincidan con las migraciones actuales.  
- Que las **relaciones** (FK, N:N, 1:N, etc.) sean consistentes con el esquema activo.  

Si se detecta una diferencia (por ejemplo, una tabla `Diagnostico` documentada pero no implementada, o un atributo `validado` inexistente), el agente debe:
1. Señalar la discrepancia.  
2. Sugerir la corrección más apropiada (en el código o la documentación).  
3. Aplicar la modificación **solo si es aprobada explícitamente** por el usuario o el equipo.  

---

### 🔁 3. Sincronización y trazabilidad de cambios
Cuando la IA (u otro agente) proponga o ejecute un cambio estructural (por ejemplo, agregar un atributo, modificar una tabla o alterar una ruta):
- El cambio debe reflejarse de forma coherente en:
  - Los **archivos de migración / modelos** afectados.  
  - Las **historias de usuario** relacionadas.  
  - Este archivo `agents.md`, si define una directiva o formato afectado.
- Debe incluir un comentario o registro tipo:
```

[SYNC] Actualizado modelo Diagnóstico → se agregó campo 'urgencia' (reflejado en historias MED-DIAG-001 y migración 2025_XX_XX)

```

---

### 🧩 4. Modificaciones autorizadas
El agente **puede** modificar estructuras documentadas si:
- El cambio surge de una nueva historia aprobada.  
- Se requiere por consistencia funcional (p.ej. agregar FK o atributo faltante).  
- La modificación mantiene la integridad con las reglas del dominio.

El agente **NO debe** alterar:
- La convención de rutas, nombres de carpetas o esquema de numeración sin autorización.  
- El formato definido en la sección *Formato estandarizado de Historias de Usuario*.  

---

### 🧾 5. Reglas de versionado documental
- Cada cambio sustancial en una historia o modelo debe incluir en su encabezado una línea:
```

Versión: vX.Y — actualizado el DD/MM/AAAA

````
- El `agents.md` actúa como **fuente de verdad de la estructura y convenciones del proyecto**.  
Si un cambio lo contradice, se debe actualizar el archivo antes de continuar con nuevas implementaciones.

---

### 🔍 6. Responsabilidad del agente al detectar divergencias
Cuando se solicite generar una funcionalidad o revisar coherencia:
1. Comparar los nombres de entidades, atributos y relaciones contra los modelos existentes.  
2. Si hay inconsistencias:
 - Informar el conflicto con formato:  
   ```
   [WARNING] La entidad 'Diagnostico' documenta 'planDeCuidado' pero el modelo actual no contiene este atributo.
   ```
 - Sugerir cuál debería actualizarse (código ↔ documentación).  
3. Si el usuario lo aprueba, reflejar los cambios en todas las fuentes pertinentes.

---

### 🧱 7. Resultado esperado
Al seguir esta directiva, el proyecto mantendrá:
- Documentación viva y sincronizada.  
- Historias de usuario consistentes con el código real.  
- Reducción de errores por desalineación entre especificación y desarrollo.  
- Mayor trazabilidad en la evolución funcional del sistema.

---


