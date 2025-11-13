# 🛠 Historia de Usuario — **ADM-USR-003**

## Ver, editar y desactivar/reactivar usuarios

### **Título**

*Como administrador, quiero visualizar el perfil de un usuario, editar sus datos personales y poder desactivarlo o reactivarlo, para mantener actualizada la información y controlar quién puede acceder al sistema.*

---

### **Descripción / Contexto**

Desde el listado de usuarios (ADM-USR-001), el administrador selecciona un usuario y accede a la vista de **Perfil**, que **ya existe** y se reutiliza para edición.
En esta vista, el administrador puede:

* **Editar los datos personales** del usuario.
* **Resetear la contraseña**.
* **Desactivar** al usuario (baja lógica).
* **Reactivar** a un usuario previamente desactivado.

El administrador **NO puede cambiar el rol** del usuario.
Tampoco puede ver datos clínicos (diagnósticos, planes, actividades), solo datos personales y de acceso.

---

### **Alcance**

**Incluye**

* Navegación desde la tabla de usuarios → Perfil del usuario.
* Reutilización de la **vista de perfil existente** (la misma que ve el usuario cuando edita su cuenta).
* Campos editables:

  * Nombre
  * Apellido
  * Email
  * Teléfono (si aplica)
  * Otros datos personales existentes en tu perfil actual
* Botón **Resetear contraseña** (genera una nueva o fuerza cambio, según tu implementación).
* Botón **Desactivar usuario** (soft delete) → cambia `activo = false`.
* Botón **Reactivar usuario** si está desactivado → cambia `activo = true`.
* Indicador claro del estado del usuario: badge **Activo** o **Inactivo**.
* Validaciones estándar (email único, campos obligatorios, etc.).

**No incluye**

* Cambiar el rol del usuario.
* Eliminar físicamente el usuario.
* Gestión de relaciones clínicas (diagnósticos, planes, actividades).
* Historial de cambios o auditoría avanzada.

---

### **Actores y permisos**

* **Administrador**: acceso total a este flujo.
* **Pacientes y médicos**: no pueden acceder a perfiles de terceros.

---

### **Dependencias / Supuestos**

* La vista de perfil ya existe y soporta edición.
* `UserModel` tiene campo `activo`.
* El sistema valida emails únicos.
* La desactivación debe impedir al usuario iniciar sesión en adelante.
* Las relaciones clínicas asociadas a un usuario desactivado deben permanecer intactas.

---

### **Flujo principal**

1. El administrador accede al módulo **Usuarios**.
2. En la tabla, hace clic en **“Ver/Editar”** sobre un usuario.
3. El sistema muestra la **vista de perfil**, poblada con los datos actuales.
4. El administrador puede modificar datos personales y presionar **Guardar cambios**.
5. Si desea resetear contraseña, presiona el botón correspondiente.
6. Si el usuario está **activo**, puede presionar **“Desactivar usuario”**:

   * El sistema actualiza `activo = false`.
   * Muestra “Usuario desactivado correctamente”.
   * El usuario ya no puede autenticarse.
7. Si el usuario está **inactivo**, el botón cambia a **“Reactivar usuario”**:

   * El sistema actualiza `activo = true`.
   * Muestra “Usuario reactivado correctamente”.
8. El administrador puede volver al listado.

---

### **Criterios de aceptación (CA)**

**CA-1.** El administrador puede acceder al perfil de cualquier usuario desde la tabla.
**CA-2.** La vista de perfil muestra datos personales editables.
**CA-3.** El administrador **no puede cambiar el rol** del usuario.
**CA-4.** Existe un botón para **desactivar** usuario si está activo, y para **reactivar** si está inactivo.
**CA-5.** Desactivar un usuario vuelve `activo = false` sin borrar datos.
**CA-6.** Usuarios desactivados **no pueden iniciar sesión**.
**CA-7.** Existe un botón para **resetear la contraseña**.
**CA-8.** Los cambios se reflejan al volver al listado de usuarios.
**CA-9.** Usuarios no administradores no pueden acceder a la vista de perfil de terceros.

---

### **Casos borde y errores**

* Email duplicado → mostrar error y no guardar.
* Intento de editar usuario inexistente → mostrar mensaje y volver al listado.
* Fallo en BD al desactivar → no cambiar estado, mostrar error.
* Usuario desactivado intenta iniciar sesión → se deniega el acceso con mensaje apropiado.

