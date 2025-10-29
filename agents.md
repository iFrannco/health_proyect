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

  * `Libraries/` → `CarePlanTemplate.php` *(materializa plantillas en actividades con fechas concretas)*

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

* `tipo_diagnositco_id` (FK→tipo_diagnostico.id, **NOT NULL**)

* `plan_estandar_id` (FK→plan\_estandar.id, **NULLABLE**; setear si proviene de una plantilla)

* `tipo_plan` ENUM(`personalizado`,`estandarizado`)

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
