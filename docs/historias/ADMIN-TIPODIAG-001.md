# 🧩 Historia de Usuario — **ADM-TIPODIAG-001**

## Gestión (ABMC) de Tipos de Diagnóstico

### **ID**

`ADM-TIPODIAG-001`

### **Título**

*Como administrador quiero gestionar (listar, buscar, crear, editar y desactivar) los tipos de diagnóstico, para mantener actualizado el catálogo utilizado por los médicos al registrar diagnósticos.*

---

### **Descripción / Contexto**

Los médicos utilizan un conjunto de **Tipos de Diagnóstico** para clasificar cada diagnóstico clínico que registran.
El administrador necesita poder gestionar este catálogo de forma centralizada, manteniéndolo actualizado y evitando inconsistencias.

Desde el sidebar del administrador debe existir una opción “**Tipos de Diagnóstico**”, que abra una pantalla donde se puedan:

* visualizar todos los tipos existentes
* buscar por nombre
* ver cuántas veces fue utilizado cada tipo
* crear nuevos tipos
* editar tipos existentes
* desactivar (baja lógica) aquellos que ya no deben usarse

Si un tipo de diagnóstico está **inactivo**, deja de aparecer en los formularios al crear nuevos diagnósticos, pero **los diagnósticos previos lo seguirán mostrando**.

La gestión se realiza mediante una **pantalla principal de listado**, con un **pop-up modal** para crear/editar tipos, evitando pantallas innecesarias ya que solo implica dos campos (nombre y descripción).

---

### **Alcance**

#### **Incluye**

1. **Agregar acceso en el sidebar del administrador**

   * Nueva opción: **Tipos de Diagnóstico**, ubicada debajo de “Planes de cuidado estándar” o donde el orden ascendente lo ubique.

2. **Pantalla principal de listado**

   * Tabla paginada (10–20 registros por página).
   * Columnas:

     * Nombre
     * Descripción (acortada)
     * Estado (Activo / Inactivo)
     * “Usado en” (cantidad de diagnósticos vinculados)
     * Acciones (Editar / Desactivar / Reactivar)

3. **Buscador por nombre**

   * Campo de búsqueda dinámica (tipo “contiene”).
   * Se puede combinar con paginación.

4. **Crear nuevo Tipo de Diagnóstico**

   * Botón “Nuevo Tipo” en la parte superior derecha.
   * Apertura de **modal** con los campos:

     * Nombre
     * Descripción
   * Guardado mediante validaciones front y backend.

5. **Editar Tipo de Diagnóstico**

   * Desde la acción “Editar” por fila.
   * Usa el mismo modal, autocompletado.
   * Cambio del nombre/descripcion actualiza lo que médicos ven y lo que muestran diagnósticos previos (sin versiones).

6. **Desactivar / Reactivar (baja lógica)**

   * Cambio de estado `activo = false` o `true`.
   * Tipos inactivos no aparecen en nuevos formularios.
   * Tipos usados en diagnósticos existentes **no deben eliminarse físicamente**.

7. **Validaciones clave**

   * Nombre obligatorio, 2–150 caracteres.
   * Descripción opcional (o dependiendo lo que este definido en la base de datos)
   * **Nombre único** (sin duplicados).
   * Modal con mensajes de error amigables.
   * Backend con reglas de validación estrictas.

8. **Indicador de uso**

   * En la tabla, por cada Tipo mostrar cuántos diagnósticos lo referencian.
   * Si nunca fue usado → mostrar “0”.

9. **Orden de listado**

   * Orden alfabético ascendente por nombre.

---

#### **No incluye**

* Eliminación física del registro.
* Gestión de diagnósticos clínicos (es otra funcionalidad).
* Modificación del comportamiento del módulo médico.
* Manejo de slug, timestamps o campos automáticos (esto se resuelve por modelo).

---

### **Actores y Permisos**

* **Administrador:**

  * acceso completo al ABMC
* **Médico / Paciente:**

  * no tienen acceso
  * médicos solo ven tipos activos al crear diagnósticos

---

### **Dependencias / Supuestos**

* Tabla `tipo_diagnostico` existente con campos: id, nombre, slug, descripcion, activo, created_at, updated_at.
* Relación 1:N → un tipo está asociado a muchos diagnósticos.
* La base ya soporta baja lógica mediante `activo`.
* El sistema de paginación está disponible (similar al módulo de usuarios).
* Los slugs se autogeneran (backend).

---

### **Flujo principal**

1. El administrador ingresa a **/admin/tipos-diagnostico** desde el sidebar.
2. El sistema muestra la tabla paginada de tipos.
3. Opcionalmente, el admin filtra por nombre usando el buscador.
4. El admin hace clic en **“Nuevo Tipo”**.

   * Se abre el modal de creación.
   * Completa nombre y descripción.
   * Guarda y el modal se cierra.
   * La tabla se recarga.
5. Para editar un tipo existente:

   * Clic en “Editar” en la fila.
   * Modal cargado con los valores actuales.
   * Se guardan cambios y se actualiza la tabla.
6. Para desactivar un tipo:

   * Clic en “Desactivar”.
   * El estado pasa a inactivo.
   * La fila refleja el nuevo estado.
7. Para reactivar un tipo:

   * Clic en “Reactivar”.
   * El estado vuelve a activo.
8. Los médicos solo verán tipos activos al registrar diagnósticos.

---

### **Criterios de Aceptación**

**CA-1.** Existe un botón en el sidebar “Tipos de Diagnóstico” visible solo para el administrador.
**CA-2.** La pantalla lista los tipos con: nombre, descripción, estado, cantidad de usos y acciones.
**CA-3.** Es posible buscar tipos por nombre.
**CA-4.** Es posible crear un nuevo tipo desde un modal.
**CA-5.** Es posible editar desde un modal precompletado.
**CA-6.** El nombre debe ser único y validado en frontend y backend.
**CA-7.** El administrador puede desactivar/reactivar tipos.
**CA-8.** Tipos inactivos NO aparecen en los formularios de creación de diagnósticos.
**CA-9.** Tipos usados en diagnósticos NO pueden eliminarse físicamente.
**CA-10.** El listado muestra cuántos diagnósticos usan cada tipo.
**CA-11.** Acceso permitido solo a administradores, bloqueado para otros roles.

---

### **Casos borde y errores**

* Intento de crear un tipo duplicado → mostrar mensaje “Ya existe un tipo de diagnóstico con ese nombre”.
* Intento de desactivar un tipo que actualmente no está activo → error simple.
* Si no existen tipos → tabla vacía con mensaje “No hay tipos de diagnóstico registrados”.
* Si el backend falla al guardar → conservar los datos del modal y mostrar mensaje genérico.

---

### **Modelo de Datos / Impacto**

* Insertar en `tipo_diagnostico` con campos:

  * `nombre`
  * `descripcion`
  * `slug` autogenerado
  * `activo = 1`
  * timestamps automáticos

* Update para edición: `nombre`, `descripcion`, `updated_at`.

* Update para baja lógica: `activo = 0`.

* No se modifican diagnósticos existentes: solo leen el tipo actualizado.

---

### **UX / UI sugerida**

* **Tabla tipo AdminLTE** con badges:

  * “Activo” → verde
  * “Inactivo” → gris o rojo suave

* Acciones por fila:

  * ✏️ Editar
  * 🔒 Desactivar
  * 🔓 Reactivar

* Modal compacto con:

  * Campo “Nombre” (input)
  * Campo “Descripción” (textarea chico)
  * Botón Guardar / Cancelar

* Contador “Usado en X diagnósticos” como texto o badge azul.

* En la parte superior, buscador + botón “Nuevo Tipo”.

