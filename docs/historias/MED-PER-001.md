# 🩺 Historia de Usuario — MED-PER-001

## Gestión del perfil del médico

### **ID**

`MED-PER-001`

### **Título**

*Como médico, quiero gestionar mi perfil, para mantener actualizados mis datos de contacto y credenciales de acceso dentro de la plataforma.*

---

### **Descripción / Contexto**

El médico necesita tener un lugar centralizado donde revisar y editar sus datos básicos (nombre, email, teléfono profesional, fecha de nacimiento) y donde también pueda cambiar la contraseña que utiliza para ingresar al sistema. Esta capacidad de autogestión reduce la dependencia de administradores y mantiene la información clínica consistente.

---

### **Alcance**

**Incluye:**

* Visualización y edición de los datos personales actuales del médico.
* Validaciones y mensajes de error claros por campo.
* Cambio de contraseña solicitando la contraseña actual, la nueva y su confirmación.
* Preservación de datos ingresados cuando ocurren errores.

**No incluye:**

* Edición del rol, estado activo, matrícula u otros atributos administrativos.
* Gestión de permisos, filtros o módulos clínicos.
* Carga de avatar o archivos.

---

### **Actores y Permisos**

* **Médico autenticado:** único actor con acceso; solo gestiona su propio perfil.
* **Administrador / Paciente:** no acceden a esta vista mediante esta historia.

---

### **Dependencias / Supuestos**

* El usuario está autenticado y pertenece al rol `medico`.
* La tabla `users` almacena los datos del médico (misma estructura que pacientes).
* Existen rutas protegidas por los filtros `auth` y `role:medico`.
* Se cuenta con reglas de validación para email único y contraseña segura.

---

### **Flujo principal**

1. El médico hace clic en **“Mi Perfil”** desde la barra superior.
2. El sistema carga el formulario con los datos actuales.
3. El médico modifica información y guarda; se validan campos y se muestran mensajes según corresponda.
4. Para cambiar la contraseña, ingresa la actual, la nueva y la confirmación.
5. El sistema verifica la contraseña actual y, si es correcta, reemplaza el hash.
6. Ante errores (validaciones, email duplicado, contraseña incorrecta) se informa al usuario y se conservan los datos ingresados.

---

### **Validaciones de negocio**

* `nombre`, `apellido`: obligatorios, 2–120 caracteres.
* `email`: obligatorio, formato válido, único entre usuarios (permite el propio).
* `telefono`: opcional, máximo 50 caracteres.
* `fecha_nac`: opcional, formato `YYYY-MM-DD` válido.
* Cambio de contraseña:
  * Requiere contraseña actual.
  * Nueva contraseña mínimo 8 caracteres y coincidencia con la confirmación.

---

### **Criterios de aceptación**

**CA-1.** El médico ve y edita sus datos personales bajo las validaciones definidas.
**CA-2.** Los mensajes de error indican claramente el campo y la causa.
**CA-3.** El email no puede duplicarse con otro usuario distinto.
**CA-4.** Tras guardar datos válidos se muestra confirmación y la información persiste.
**CA-5.** El cambio de contraseña exige la contraseña actual y valida coincidencia de la nueva con su confirmación.
**CA-6.** Toda la experiencia utiliza el layout y estilos AdminLTE acordados con los pacientes, manteniendo coherencia visual.

---

### **Casos borde y errores**

* Email en uso por otro usuario → “El email ya está registrado por otro usuario”.
* Fecha inválida → mensaje con formato esperado.
* Contraseña actual errónea → “La contraseña actual no es válida”.
* Errores de BD → mensaje genérico y registro en logs.

---

### **Datos mínimos / Modelo**

| Atributo        | Tipo                 | Descripción                           |
|-----------------|----------------------|---------------------------------------|
| `id`            | INT (PK)             | Identificador del médico              |
| `nombre`        | VARCHAR(120)         | Nombre                                |
| `apellido`      | VARCHAR(120)         | Apellido                              |
| `email`         | VARCHAR(180), UNIQUE | Correo de contacto y autenticación    |
| `telefono`      | VARCHAR(50) NULL     | Teléfono profesional                  |
| `fecha_nac`     | DATE NULL            | Fecha de nacimiento                   |
| `password_hash` | VARCHAR(255)         | Hash de contraseña                    |
| `role_id`       | FK→roles.id          | Rol asociado (medico)                 |
| `activo`        | TINYINT(1)           | Indicador de cuenta activa            |

---

### **Conclusión**

La historia extiende la autogestión del perfil al rol médico, alineando la experiencia con la del paciente y manteniendo los datos de contacto actualizados sin intervención administrativa.

