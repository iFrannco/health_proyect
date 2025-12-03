# 🩺 Historia de Usuario — COM-AUTH-002

## Restaurar contraseña desde login

### **ID**

`COM-AUTH-002`

### **Título**

*Como usuario (paciente/médico/admin), quiero poder restaurar mi contraseña desde el login, para recuperar el acceso a la plataforma.*

---

### **Descripción / Contexto**

Todos los usuarios (paciente, médico, admin) deben poder recuperar acceso cuando olvidan su contraseña. Desde el formulario de inicio de sesión, el usuario solicita un enlace de restablecimiento ingresando su correo. El sistema responde con un mensaje genérico (sin confirmar existencia de la cuenta) y, mientras no haya backend de correo, muestra en pantalla el enlace temporal generado. El enlace caduca a los 15 minutos y, al abrirlo, lleva a un formulario para definir la nueva contraseña siguiendo la misma política vigente en alta de usuarios.

---

### **Alcance**

**Incluye:**

* Enlace “¿Olvidó su contraseña?” en el login, visible para todos los roles.
* Formulario de solicitud con campo `email` y mensaje genérico de envío (sin revelar existencia).
* Generación de enlace único con caducidad de 15 minutos.
* Cooldown de 60 segundos para volver a solicitar un enlace y evitar spam.
* Pantalla posterior que muestra el enlace generado (solo temporal mientras no haya backend de correo real).
* Formulario de cambio de contraseña (nueva + confirmación) al abrir el enlace.
* Validaciones de contraseña según la política ya utilizada en el alta de usuario.
* Mensajes de éxito y errores formales en español.
* Diseño responsive coherente con la pantalla de login actual.

**No incluye:**

* Envío real de correos o integración con servicios de email/SMS.
* Doble factor, captchas o bloqueo avanzado por intentos.
* Gestión de múltiples correos/teléfonos u otros canales de recuperación.

---

### **Actores y Permisos**

* **Usuario no autenticado**: puede iniciar el flujo de restauración desde el login.
* **Roles aplicables**: `admin`, `medico`, `paciente` (mismo flujo para todos).

---

### **Dependencias / Supuestos**

* Existe una política de contraseñas vigente (usada en el alta de usuario) con sus mensajes de validación.
* Se almacenan correos únicos por usuario; el flujo opera únicamente con `email`.
* Backend de correo aún no disponible: el enlace se muestra en la UI como medida temporal.
* La UI debe permanecer coherente con estilos actuales del login.

---

### **Flujo principal**

1. El usuario accede al login y selecciona **“¿Olvidó su contraseña?”**.
2. Ingresa su **email** en el formulario de recuperación.
3. El sistema valida formato de email y, si es correcto, genera un enlace de restablecimiento con vigencia de **15 minutos**.
4. El sistema muestra un mensaje genérico: “Si el correo existe, le enviamos un enlace para restablecer su contraseña”.
5. Mientras no haya backend, el sistema muestra en pantalla el **enlace temporal** generado.
6. Se inicia un **cooldown de 60 segundos** antes de permitir otra solicitud.
7. Al abrir el enlace, se muestra formulario con campos **Nueva contraseña** y **Confirmar contraseña**.
8. El usuario ingresa y confirma la nueva contraseña; el sistema valida contra la política vigente y que ambas coincidan.
9. Si la validación es exitosa y el enlace es válido/no expirado, se actualiza la contraseña y se muestra mensaje de éxito con opción de volver al login.

---

### **Validaciones de negocio**

* `email` con formato válido; mensaje genérico independientemente de la existencia del usuario.
* Enlace de restablecimiento con **token único**, uso único y expiración a los **15 minutos**.
* **Cooldown de 60 segundos** para solicitar un nuevo enlace desde el mismo cliente.
* Nueva contraseña debe cumplir la **política vigente de alta** y coincidir con la confirmación.
* Si el enlace es inválido, usado o expirado, se bloquea el cambio y se invita a solicitar uno nuevo.

---

### **Criterios de aceptación**

**CA-1.** El login muestra el enlace “¿Olvidó su contraseña?” y lleva al formulario de recuperación para cualquier rol.
**CA-2.** Al enviar un correo válido, se muestra mensaje genérico de envío sin indicar si el usuario existe.
**CA-3.** El enlace generado caduca a los 15 minutos y se invalida tras su uso.
**CA-4.** Se aplica un cooldown de 60 segundos antes de permitir una nueva solicitud.
**CA-5.** El formulario de restablecimiento requiere nueva contraseña y confirmación, aplicando la política de alta.
**CA-6.** Mensaje formal de éxito al actualizar la contraseña y enlace para volver al login.
**CA-7.** Si el enlace es inválido/expirado/ya usado, se muestra mensaje formal y opción de solicitar un nuevo enlace.
**CA-8.** Mientras no haya backend de correo, el enlace temporal se muestra en pantalla tras la solicitud.

---

### **Casos borde y errores**

* Email con formato inválido → error de validación y no se genera enlace.
* Solicitud durante el cooldown de 60 s → mensaje formal indicando esperar para reintentar.
* Token expirado (15 min), inválido o ya usado → mensaje formal y call-to-action para solicitar otro.
* Fallo del servicio de envío (cuando exista backend) → mensaje genérico: “No pudimos procesar su solicitud. Intente nuevamente más tarde.”
