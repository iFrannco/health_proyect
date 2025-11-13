# 🛠 Historia de Usuario — **ADM-USR-002**

## Alta de usuario (crear paciente, médico o administrador)

### **Título**

*Como administrador, quiero poder crear nuevos usuarios del sistema (pacientes, médicos o administradores) mediante un formulario único, para mantener actualizado el registro y otorgar acceso a quienes lo necesitan.*

---

### **Descripción / Contexto**

Desde la pantalla de listado de usuarios (ADM-USR-001), el administrador puede pulsar **“Nuevo usuario”**.
Esto lo lleva a un **formulario de alta** que permite completar los datos personales y seleccionar el **rol** del nuevo usuario.
El formulario es **único** para todos los roles (paciente, médico, administrador), simplificando la gestión.
El usuario creado queda automáticamente **activo** y puede iniciar sesión con la contraseña asignada o generada.

---

### **Alcance**

**Incluye**

* Botón **“Nuevo usuario”** en el listado.
* **Formulario único** para crear usuarios con:

  * Nombre
  * Apellido
  * Fecha de nacimiento
  * Email
  * Teléfono (si existe en el modelo)
  * Contraseña inicial
  * Rol (select con: Paciente / Médico / Administrador)
  * Estado inicial: **Activo**
* Validaciones:

  * Email único
  * Campos obligatorios completos
  * Contraseña válida según reglas mínimas
* Comportamiento al guardar:

  * Se crea el registro del usuario activo
  * Se asigna el rol seleccionado
  * Se redirige al listado con mensaje de éxito
* Seguridad:

  * Solo el administrador puede acceder a la pantalla de alta.

**No incluye**

* Edición de usuarios (historia ADM-USR-003).
* Alta de información clínica (diagnósticos, planes).
* Carga masiva de usuarios.
* Envío de email automático (a menos que más adelante lo agregues).

---

### **Actores y permisos**

* **Administrador:** único actor con permiso para crear usuarios.
* Médicos y pacientes no tienen acceso.

---

### **Dependencias / supuestos**

* El formulario de **perfil de usuario** reutilizado para edición existe y tiene campos compatibles.
* `UserModel` permite guardar un nuevo usuario con su rol correspondiente.
* Existe un campo `activo = true|false` en el modelo.
* El sistema soporta validación de email único.

---

### **Flujo principal**

1. El administrador ingresa al módulo **Usuarios**.
2. Hace clic en **“Nuevo usuario”**.
3. El sistema muestra el formulario de **alta de usuario**.
4. El administrador completa:

   * Datos personales
   * Email
   * Contraseña
   * Selecciona **Rol**
5. Pulsa **Guardar**.
6. El sistema:

   * Valida los datos
   * Crea el usuario con `activo = true`
   * Redirige al listado de usuarios
   * Muestra mensaje “Usuario creado correctamente”

---

### **Criterios de aceptación (CA)**

**CA-1.** El administrador puede acceder a la pantalla “Nuevo usuario”.
**CA-2.** El formulario permite crear pacientes, médicos y administradores mediante selección de rol.
**CA-3.** No se aceptan emails duplicados.
**CA-4.** La contraseña inicial es obligatoria.
**CA-5.** Al guardar, el usuario queda **activo**.
**CA-6.** El administrador vuelve automáticamente al listado de usuarios con un mensaje de éxito.
**CA-7.** Usuarios no administradores no pueden acceder a la pantalla de alta.

---

### **Casos borde y errores**

* Email duplicado → mensaje “El email ya está registrado”.
* Datos incompletos → mensajes de validación específicos.
* Falla al guardar → mensaje de error general y mantener datos cargados.


