# 🛠 Historia de Usuario — **ADM-USR-001**

## Listado y filtrado de usuarios

### **Título**

*Como administrador, quiero ver un listado de todos los usuarios del sistema, con buscador y filtros por rol, para poder ubicar rápidamente a quien necesito gestionar.*

---

### **Descripción / Contexto**

El administrador accede al módulo **Usuarios** desde el sidebar.
El sistema muestra una **tabla con todos los usuarios**, que puede ser filtrada tanto mediante un **buscador por texto** como mediante un **filtro por rol**.
Esta pantalla es el punto central desde donde el admin podrá seleccionar usuarios para editarlos o desactivarlos (esos flujos se detallan en las historias ADM-USR-002 y ADM-USR-003).

---

### **Alcance**

**Incluye**

* Acceso a la ruta `/admin/usuarios` o equivalente.
* Tabla de usuarios del sistema, utilizando las **mismas columnas** que la tabla de “Pacientes” en el módulo Médico (para mantener coherencia visual).
* Filtro por **rol** (Paciente / Médico / Administrador / Todos).
* **Buscador** por nombre, apellido o email (coincidencia parcial, no sensible a mayúsculas).
* Indicador “Activo / Inactivo” por usuario.
* Por defecto muestra los usuarios activos.
* Acción por fila: **Ver/Editar** (esta acción dirige a la historia ADM-USR-003).
* Ordenamiento lógico (por apellido ascendente, o por defecto alfabético).

**No incluye**

* Alta de usuarios (ADM-USR-002).
* Edición, activación o desactivación (ADM-USR-003).
* Contenidos clínicos (planes, diagnósticos, etc.).

---

### **Actores y permisos**

* **Administrador:** único con acceso a esta pantalla.
* Médicos y pacientes no ven este módulo.

---

### **Dependencias / Supuestos**

* El sistema tiene usuarios registrados con un campo `rol`.
* Cada usuario tiene `activo = true|false`.
* La tabla puede contener muchos usuarios, por lo que se recomienda paginación integrada (por ejemplo, 10–20 filas por página).
* Los modelos y controladores ya permiten obtener usuarios filtrando por rol y texto.

---

### **Flujo principal**

1. El administrador hace clic en **“Usuarios”** en el sidebar.
2. El sistema muestra:

   * Tabla con todos los usuarios.
   * Buscador.
   * Filtro de rol.
   * Estado activo/inactivo.
3. El administrador:

   * Escribe texto en el buscador → la tabla se filtra por nombre/apellido/email.
   * Selecciona un rol en el filtro → la tabla cambia dinámicamente.
   * Puede combinar ambos (texto + rol).
4. En cada fila, el admin puede hacer clic en **“Ver/Editar”** para gestionar ese usuario (historia ADM-USR-003).

---

### **Criterios de aceptación**

**CA-1.** La pantalla muestra **todos** los usuarios con sus datos personales básicos.
**CA-2.** El buscador filtra usuarios por nombre, apellido o email.
**CA-3.** El filtro permite ver solo Pacientes, Médicos, Administradores o Todos.
**CA-4.** El buscador y el filtro pueden usarse simultáneamente.
**CA-5.** Usuarios inactivos aparecen claramente marcados.
**CA-6.** Cada usuario tiene disponible la acción “Ver/Editar”.
**CA-7.** Solo el administrador puede acceder a este listado.

---

### **Casos borde y errores**

* Sin usuarios coincidentes con el filtro → mostrar mensaje “No se encontraron usuarios”.
* Error de servidor → mostrar mensaje genérico.
* Intento de acceso sin permisos → “Acceso denegado”.

---

### **Modelo de datos / Impacto**

No modifica datos.
Realiza solo **consultas**: listados, filtros y búsquedas.

