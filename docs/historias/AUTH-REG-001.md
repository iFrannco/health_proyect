# 👤 Historia de Usuario — **AUTH-REG-001**

## Autoregistro de pacientes y médicos desde la pantalla de login

### ID

`AUTH-REG-001`

### Título

*Como usuario nuevo quiero poder registrarme en la plataforma como paciente o médico desde la pantalla de inicio de sesión, para crear mi cuenta y luego poder ingresar al sistema.*

---

### Descripción / Contexto

Actualmente existe una pantalla de **login** que permite a usuarios ya registrados iniciar sesión.
Se requiere habilitar un flujo de **autoregistro** para que **nuevos pacientes y médicos** puedan crear su cuenta sin intervención del administrador.

Desde la pantalla de login, el usuario verá un botón adicional que lo lleva a un **formulario de registro**.
En ese formulario completará sus datos personales y elegirá si se registra como **Paciente** o **Médico**.
Al guardar:

* Se crea un nuevo registro de usuario (`activo = true`).
* No se requiere validación por email ni aprobación del administrador.
* El sistema redirige nuevamente al login mostrando un mensaje de éxito o de error.

---

### Alcance

#### Incluye

1. **Modificación de la pantalla de login**

   * Mantener el botón actual **“Ingresar”** para usuarios existentes.
   * Agregar un botón/enlace visible, por ejemplo **“Crear cuenta”** o **“Registrarse”**, que lleve al formulario de registro.
   * El diseño debe ser coherente con el estilo actual (centrado, tarjeta blanca sobre fondo azul).

2. **Formulario de registro único para pacientes y médicos**

   * Campos obligatorios (mismos que alta de usuario por admin, excepto rol restringido):

     * Nombre
     * Apellido
     * DNI
     * Fecha de nacimiento
     * Email
     * Contraseña
     * Rol (selector con opciones **Paciente** y **Médico**)
   * Campos opcionales:

     * Teléfono
   * El formulario se basa en la misma estructura visual del formulario “Nuevo usuario” del administrador, pero adaptado al contexto público (sin navegación de admin ni breadcrumb de “Volver al listado”).

3. **Creación del usuario**

   * Al enviar el formulario correctamente:

     * Se crea un registro en la tabla de usuarios con:

       * Datos personales ingresados.
       * `role_id` correspondiente a **Paciente** o **Médico**.
       * `activo = true`.
       * `password_hash` generado a partir de la contraseña ingresada.
     * No se puede seleccionar rol **Administrador** desde este formulario.
     * El usuario creado **no** inicia sesión automáticamente:

       * El sistema redirige a la pantalla de login.
       * Muestra un mensaje: `"Tu cuenta fue creada correctamente. Ahora podés iniciar sesión."`

4. **Acceso inmediato**

   * No se requiere confirmación por email.
   * No se requiere aprobación manual del administrador.
   * El usuario registrado puede iniciar sesión inmediatamente con su email y contraseña.

5. **Validaciones (front y backend)**

   * Validaciones actuales del modelo (backend):

     * `nombre`: `required|min_length[2]|max_length[120]`
     * `apellido`: `required|min_length[2]|max_length[120]`
     * `dni`: `required|min_length[6]|max_length[20]`
     * `email`: `required|valid_email|max_length[180]`
     * `password_hash`: `required|max_length[255]`
     * `role_id`: `required|is_natural_no_zero`
   * **Extensiones requeridas de validación**:

     * **Fecha de nacimiento**: campo obligatorio, formato de fecha válido (AAAA-MM-DD).
     * **Teléfono**:

       * Si se completa, debe contener solo dígitos (y opcionalmente espacios/guiones según decidas).
       * Validación en **frontend** y en **backend**.
     * **Contraseña**:

       * Mínimo **8 caracteres**.
       * Debe contener al menos:

         * 1 letra
         * 1 número
         * 1 símbolo (carácter no alfanumérico)
       * Validación en **frontend** (feedback inmediato) y en **backend** (regla de seguridad efectiva).
   * Email debe ser único en el sistema (si ya existe, se rechaza el registro con mensaje claro).

6. **Restricción de roles**

   * El selector de rol solo ofrece dos opciones: **Paciente** y **Médico**.
   * No se permite elegir **Administrador** ni cualquier otro rol desde este formulario.

7. **Restricciones de acceso**

   * El formulario de registro solo debe estar disponible para usuarios **no autenticados**.
   * Si un usuario ya logueado intenta acceder a la ruta de registro, se lo redirige a su home según rol.

---

#### No incluye

* Registro de administradores.
* Creación de historia clínica, diagnósticos o planes de cuidado al momento del registro.
* Validación por email, SMS u otros factores.
* Recuperación de contraseña (flujo aparte).
* Edición de datos luego del registro (eso se gestiona desde la pantalla de perfil).

---

### Actores y permisos

* **Paciente (nuevo)**: puede registrarse a través del formulario y se crea con rol Paciente.
* **Médico (nuevo)**: puede registrarse a través del formulario y se crea con rol Médico.
* **Administrador**: no utiliza este flujo (da de alta usuarios desde el módulo de administración).
* **Usuarios autenticados**: no deben poder usar el formulario de registro mientras tengan sesión iniciada.

---

### Dependencias / Supuestos

* Existe una tabla de `roles` con al menos: Paciente, Médico, Administrador.
* El modelo `UserModel` utiliza las validaciones indicadas y soporta `role_id`, `dni`, `fecha_nacimiento`, `telefono`, `email`, `password_hash` y `activo`.
* Hay un flujo de login ya implementado que valida email + contraseña y verifica `activo = true`.
* La vista de login actual puede modificarse para agregar el botón de registro.

---

### Flujo principal

1. El usuario accede a la pantalla de login (`/auth/login`).
2. Ve dos botones:

   * **“Ingresar”** (login actual).
   * **“Registrarse”** (nuevo).
3. El usuario hace clic en **“Registrarse”**.
4. El sistema muestra el formulario de **Registro de usuario** con los campos mencionados.
5. El usuario completa sus datos, elige **Paciente** o **Médico** y define una contraseña válida.
6. Al enviar el formulario:

   * El backend valida todos los campos.
   * Si hay errores, vuelve al formulario mostrando los errores específicos (respetando el `old()` de los campos).
   * Si todo es correcto:

     * Crea el usuario con `activo = true` y el `role_id` correspondiente.
     * Redirige a `/auth/login`.
     * Muestra un mensaje de éxito.
7. El usuario ahora puede iniciar sesión usando su email y contraseña.

---

### Validaciones de negocio

* Un mismo email no puede usarse para dos usuarios distintos.
* El rol elegido debe ser únicamente **Paciente** o **Médico**.
* Las contraseñas deben cumplir el patrón de seguridad acordado (mínimo 8 caracteres, letras, números y símbolos).
* Los usuarios creados por este flujo siempre se registran con `activo = true`.
* No se crean registros clínicos adicionales (diagnósticos, planes, etc.) en este paso.

---

### Criterios de aceptación

**CA-1.** La pantalla de login muestra un botón adicional que permite ir al formulario de registro.
**CA-2.** El formulario permite registrar usuarios con los campos: nombre, apellido, DNI, fecha de nacimiento, teléfono (opcional), email, contraseña y rol (Paciente/Médico).
**CA-3.** La validación de backend utiliza las reglas definidas y se extiende para fecha de nacimiento, teléfono y contraseña según lo indicado.
**CA-4.** El formulario muestra mensajes claros de error cuando falta información obligatoria o algún campo no es válido.
**CA-5.** Al registrar correctamente, se crea un usuario con `activo = true` y `role_id` de Paciente o Médico, y se redirige al login con un mensaje de éxito.
**CA-6.** No es posible registrarse como Administrador desde este formulario.
**CA-7.** Usuarios autenticados no pueden acceder al formulario de registro.
**CA-8.** La interfaz mantiene coherencia visual con el diseño actual (login y formularios de admin).

---

### Casos borde y errores

* Intento de registro con email ya existente → mensaje “El email ya está registrado”.
* Contraseña que no cumple la política → mensaje específico indicando qué falta (por ejemplo: “Debe tener al menos 8 caracteres, una letra, un número y un símbolo”).
* Teléfono con caracteres no numéricos → mensaje de error y no se guarda.
* Error interno al crear usuario (BD) → mensaje genérico de error y preservar datos que el usuario ya completó (excepto contraseña).

---

### Modelo de datos / Impacto

* Inserción en tabla `users` con:

  * `nombre`
  * `apellido`
  * `dni`
  * `fecha_nacimiento`
  * `telefono` (opcional)
  * `email`
  * `password_hash`
  * `role_id` (Paciente o Médico)
  * `activo = true`

No se modifican otras tablas.

---

### UX / UI sugerida

* Login:

  * Mantener tarjeta central actual.
  * Debajo o junto al botón “Ingresar”, agregar un botón/link:

    * Texto sugerido: **“¿No tenés cuenta? Registrate”**.
* Registro:

  * Usar el mismo estilo que la pantalla “Nuevo usuario” de admin:

    * Título: **“Crear cuenta”**.
    * Subtítulo: “Completá tus datos para registrarte en HealthPro”.
  * Al guardar con éxito, mensaje en login tipo alert/flash:

    * Verde: “Tu cuenta fue creada correctamente. Ahora podés iniciar sesión.”

