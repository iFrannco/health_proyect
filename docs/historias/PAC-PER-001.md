# 🩺 Historia de Usuario — PAC-PER-001

## Gestión del perfil del paciente

### **ID**

`PAC-PER-001`

### **Título**

*Como paciente, quiero gestionar mi perfil, para mantener actualizados mis datos personales y credenciales de acceso.*

---

### **Descripción / Contexto**

El paciente necesita un espacio seguro donde revisar y editar su información básica (nombre, email de contacto, teléfono, fecha de nacimiento) y donde también pueda cambiar su contraseña de acceso. Esta pantalla centraliza la autogestión del perfil y evita depender de terceros para actualizaciones simples.

---

### **Alcance**

**Incluye:**

* Visualización de los datos personales actuales del paciente (nombre, apellido, email, teléfono, fecha de nacimiento).
* Edición de dichos datos con validaciones y feedback inmediato.
* Cambio de contraseña solicitando la contraseña actual, la nueva y su confirmación.
* Indicadores visuales de éxito/error y preservación del formulario ante validaciones fallidas.

**No incluye:**

* Edición del rol, estado activo o cualquier atributo administrativo.
* Carga de foto/avatar.
* Gestión de autenticación multifactor o recuperación de contraseña.
* Cambios sobre diagnósticos, planes u otras entidades clínicas.

---

### **Actores y Permisos**

* **Paciente autenticado:** único actor con acceso al formulario; solo edita sus propios datos.
* **Médico / Administrador:** sin acceso mediante esta historia.

---

### **Dependencias / Supuestos**

* El usuario está autenticado y la sesión contiene su `user_id` y `rol = paciente`.
* La tabla `users` almacena la información básica del paciente (`nombre`, `apellido`, `email`, `telefono`, `fecha_nac`, `password_hash`).
* Existen rutas protegidas por los filtros `auth` y `role:paciente`.
* Se cuenta con validaciones que aseguran la unicidad del email y la fortaleza mínima de la contraseña.

---

### **Flujo principal**

1. El paciente ingresa a la opción **“Mi Perfil”** desde la barra superior.
2. El sistema muestra los datos actuales en un formulario editable.
3. El paciente modifica la información deseada y confirma.
4. El sistema valida la información, persiste los cambios y muestra mensaje de éxito.
5. En la sección de cambio de contraseña, ingresa la contraseña actual y la nueva con confirmación.
6. El sistema valida la contraseña actual, guarda la nueva y muestra confirmación.
7. Ante errores de validación, se muestran mensajes específicos y se preservan los datos ingresados.

---

### **Validaciones de negocio**

* `nombre` y `apellido`: obligatorios, 2–120 caracteres.
* `email`: obligatorio, formato válido y único (puede repetirse solo si es el mismo usuario).
* `telefono`: opcional, máximo 50 caracteres.
* `fecha_nac`: opcional, formato `YYYY-MM-DD` válido.
* Cambio de contraseña:
  * Requiere la contraseña actual.
  * Nueva contraseña: mínimo 8 caracteres y coincidencia con la confirmación.
  * Si la contraseña actual no coincide, no se permite el cambio.

---

### **Criterios de aceptación**

**CA-1.** El paciente visualiza y puede editar sus datos personales, aplicando las validaciones definidas.
**CA-2.** Si el email ingresado ya existe en otro usuario, se muestra error indicando que debe elegir otro.
**CA-3.** Al guardar datos válidos, se muestra mensaje de éxito y la información se persiste correctamente.
**CA-4.** El formulario mantiene los valores ingresados cuando ocurre una validación fallida.
**CA-5.** El cambio de contraseña exige la contraseña actual y la valida antes de reemplazarla.
**CA-6.** Las contraseñas nuevas deben coincidir y cumplir el mínimo de longitud.
**CA-7.** Toda la interacción ocurre dentro del layout general del paciente y respeta los estilos de AdminLTE.
**CA-8.** Los mensajes de feedback utilizan los componentes estándar de alertas del sistema.

---

### **Casos borde y errores**

* Email existente en otro usuario → mensaje “El email ya está registrado”.
* Fecha de nacimiento inválida → error de validación.
* Contraseña actual incorrecta → mensaje “La contraseña actual no es válida”.
* Nueva contraseña igual a la actual → se permite, pero se vuelve a hashear (no se fuerza cambio distinto).
* Errores de base de datos → mensaje genérico “No se pudo actualizar el perfil, intentá nuevamente”.

---

### **Datos mínimos / Modelo**

**Entidad: Usuario (paciente)**

| Atributo        | Tipo                 | Descripción                                       |
| ----------------| -------------------- | ------------------------------------------------- |
| `id`            | INT (PK)             | Identificador del usuario                         |
| `nombre`        | VARCHAR(120)         | Nombre del paciente                               |
| `apellido`      | VARCHAR(120)         | Apellido del paciente                             |
| `email`         | VARCHAR(180), UNIQUE | Correo de contacto y autenticación                |
| `telefono`      | VARCHAR(50) NULL     | Teléfono principal                                |
| `fecha_nac`     | DATE NULL            | Fecha de nacimiento                               |
| `password_hash` | VARCHAR(255)         | Hash de la contraseña                             |
| `role_id`       | FK→roles.id          | Rol asociado (`paciente`)                         |
| `activo`        | TINYINT(1)           | Indicador de cuenta habilitada                    |

---

### **Conclusión**

La historia consolida en una sola vista la autogestión del perfil del paciente, mejorando la experiencia de usuario y reduciendo la carga operativa sobre administradores o médicos para cambios simples.

