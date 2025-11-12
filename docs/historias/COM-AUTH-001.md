# 🩺 Historia de Usuario — COM-AUTH-001

## Inicio de sesión de usuario

### **ID**

`COM-AUTH-001`

### **Título**

*Como usuario, quiero iniciar sesión en la plataforma, para acceder a las funcionalidades según mi rol.*

---

### **Descripción / Contexto**

La autenticación permite a los usuarios registrados (administrador, médico, paciente) acceder a la plataforma y a las funcionalidades específicas de su rol mediante una sesión segura. Este proceso valida credenciales, verifica el estado activo del usuario y establece el contexto de permisos a través de su rol único.

---

### **Alcance**

**Incluye:**

* Validación de credenciales (email y contraseña).
* Verificación de estado activo del usuario.
* Establecimiento de sesión con rol único (`admin` | `medico` | `paciente`).
* Redirección posterior a autenticación al área correspondiente al rol.
* Cierre de sesión y limpieza de la sesión.

**No incluye:**

* Registro de usuarios.
* Recuperación o cambio de contraseña.
* Políticas de bloqueo por intentos fallidos o 2FA.
* Gestión de perfiles o edición de datos personales.
* Auditoría avanzada o trazas de seguridad.

---

### **Actores y Permisos**

* **Usuario no autenticado**: puede acceder al formulario de inicio de sesión.
* **Usuario autenticado** (`admin` | `medico` | `paciente`): no debe acceder nuevamente al login; accede a su área según rol.
* **Roles válidos**: administrador, médico, paciente (exactamente un rol por usuario).

---

### **Dependencias / Supuestos**

* Catálogo de roles cargado con slugs `admin|medico|paciente` y relación N–1 con usuarios.
* Existencia de usuarios registrados con contraseñas almacenadas como hash seguro y flag de actividad.
* Sesiones de aplicación habilitadas y configuradas.
* Persistencia consistente con el modelo de datos (`usuarios.role_id` → `roles.id`).

---

### **Flujo principal**

1. El usuario accede al formulario **Iniciar sesión**.
2. Ingresa **email** y **contraseña** y confirma.
3. El sistema valida formato de email y requisitos mínimos de contraseña.
4. El sistema verifica la existencia del usuario, su estado activo y compara la contraseña contra el hash almacenado.
5. Si la validación es exitosa, el sistema crea la **sesión** del usuario con su **rol único** y registra la marca temporal de inicio.
6. El sistema redirige al área correspondiente según el **rol** del usuario.
7. El usuario puede **cerrar sesión**; el sistema destruye la sesión y lo devuelve a la página pública.

---

### **Validaciones de negocio**

* `email` con formato válido y `contraseña` presente.
* El usuario debe existir, estar **activo** y no estar eliminado lógicamente.
* La `contraseña` debe coincidir con el `password_hash` almacenado para el usuario.
* Un usuario autenticado no puede reutilizar el formulario de **inicio de sesión**.
* La sesión incluye los datos mínimos (identificador, email/alias, `rol` único); no se admiten múltiples roles.

---

### **Criterios de aceptación**

**CA-1.** Con credenciales válidas y usuario activo, el sistema autentica y crea sesión con el rol único asociado.
**CA-2.** Con email inexistente o contraseña incorrecta, se rechaza la autenticación y se informa error sin revelar detalles sensibles.
**CA-3.** Si el usuario está inactivo o eliminado lógicamente, se bloquea el acceso y se informa el estado.
**CA-4.** Tras autenticación, el sistema redirige a la sección correspondiente al rol único del usuario.
**CA-5.** Al cerrar sesión, se destruye la sesión y se pierde el acceso a recursos protegidos hasta volver a autenticarse.
**CA-6.** La sesión mantiene solo los datos mínimos necesarios y no expone información sensible.

---

### **Casos borde y errores**

* `email` con formato inválido → error de validación y bloqueo del intento.
* Campos vacíos (`email` o `contraseña`) → error de validación.
* Usuario válido con estado inactivo → acceso denegado.
* Usuario eliminado lógicamente (soft delete) → acceso denegado.
* Exceso de intentos fallidos en corto periodo → mensaje genérico (sin revelar políticas); bloqueo avanzado fuera de alcance.

---

### **Datos mínimos / Modelo**

**Entidad: Usuario**

| Atributo         | Tipo                 | Descripción                                   |
| ---------------- | -------------------- | --------------------------------------------- |
| `id`             | INT (PK)             | Identificador único del usuario               |
| `email`          | VARCHAR(180), UNIQUE | Correo electrónico de autenticación           |
| `password_hash`  | VARCHAR(255)         | Hash seguro de la contraseña                  |
| `role_id`        | FK→roles.id          | Rol único del usuario                         |
| `activo`         | TINYINT(1)           | Indicador de cuenta activa (1) o inactiva (0) |
| `created_at`     | DATETIME NULL        | Fecha/hora de creación                        |
| `updated_at`     | DATETIME NULL        | Fecha/hora de última actualización            |
| `deleted_at`     | DATETIME NULL        | Fecha/hora de borrado lógico (si aplica)      |

**Catálogo: Rol (referencia)**

| Atributo | Tipo               | Descripción                               |
| -------- | ------------------ | ----------------------------------------- |
| `id`     | INT (PK)           | Identificador del rol                     |
| `slug`   | VARCHAR(50), UNIQUE| `admin` | `medico` | `paciente`             |
| `nombre` | VARCHAR(100)       | Nombre del rol                            |

