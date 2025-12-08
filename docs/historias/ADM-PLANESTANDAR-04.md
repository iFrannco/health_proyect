ID: ADM-PLANESTANDAR-04
Título: Como administrador, quiero gestionar planes estándar y sus actividades con reglas de frecuencia y vigencia, para definir tratamientos asociados a diagnósticos.

Descripción / Contexto:
El administrador define plantillas (planes estándar) que se vinculan obligatoriamente a un "Tipo de Diagnóstico". Dentro de estos planes, se configuran actividades usando un constructor de lenguaje natural (frecuencia y duración). Las actividades pueden estar "Vigentes" o "No Vigentes"; ambas deben listarse en la edición del plan, diferenciándose visualmente, para mantener la integridad histórica.

Alcance:
Incluye:
- Crear/Editar Planes Estándar vinculándolos a un `TipoDiagnostico`.
- Listar actividades dentro del plan, mostrando tanto las habilitadas como las deshabilitadas.
- Etiquetado visual: "Vigente" vs "No Vigente".
- Formulario de creación de actividad con la estructura UI: "[Nombre] [X] veces al/a la [Y] durante [Z] [Unidad]".
- Persistencia de los parámetros de frecuencia/duración.
- Cálculo automático de offsets (días de inicio/fin) basado en la duración ingresada para uso interno.
- Alternar el estado de vigencia de una actividad individual.

No incluye:
- Asignación a pacientes (rol Médico).
- Lógica de horarios absolutos (ej. 14:00 PM).

Actores y Permisos:
- Administrador: CRUD completo.
- Médico: Lectura (uso en asignación).

Dependencias / Supuestos:
- Catálogo `tipo_diagnostico` existente.
- Migración de base de datos ejecutada con los nuevos campos.

Flujo principal:
1. El Administrador ingresa a gestionar Planes Estándar.
2. Crea o edita un plan, seleccionando obligatoriamente un `Tipo de Diagnóstico`.
3. En la sección de actividades, visualiza el listado:
   - Actividades activas tienen etiqueta "Vigente".
   - Actividades inactivas tienen etiqueta "No Vigente".
4. Al agregar/editar una actividad, interactúa con la frase:
   - "Nombre de la actividad"
   - [Input X] veces al [Select Y: Día/Semana/Mes]
   - durante [Input Z] [Select Unidad: Días/Semanas/Meses].
5. Guarda la actividad. El sistema calcula internamente los offsets y marca la actividad como `vigente = true` por defecto.
6. Si desea deshabilitar una actividad, cambia su estado a "No Vigente".

Validaciones de negocio:
- Nombre del plan y Tipo de Diagnóstico obligatorios.
- En la actividad: X y Z deben ser enteros > 0.
- Un plan estándar debe poder contener actividades vigentes y no vigentes simultáneamente.

Criterios de aceptación:
CA-1. El plan estándar se guarda vinculado a un `tipo_diagnostico_id`.
CA-2. La interfaz de carga de actividades respeta la frase "X veces al Y durante Z...".
CA-3. El listado de actividades muestra SIEMPRE todas las actividades (vigentes y no vigentes).
CA-4. Las actividades no vigentes se muestran con una etiqueta visual clara (ej. gris/rojo "No Vigente").
CA-5. Los campos de frecuencia y duración se persisten en la base de datos.

Casos borde y errores:
- Intentar guardar una actividad con duración 0 -> Error.

Datos mínimos / Modelo:

Tabla: `plan_estandar`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | PK | Identificador |
| ... | ... | Campos existentes |
| tipo_diagnostico_id | FK | **[NUEVO]** Vincula el plan a una patología base |

Tabla: `plan_estandar_actividad`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | PK | Identificador |
| ... | ... | Campos existentes |
| vigente | BOOL | **[NUEVO]** Estado de la actividad en la plantilla |
| frecuencia_repeticiones | INT | **[NUEVO]** (X) |
| frecuencia_periodo | VARCHAR | **[NUEVO]** (Y) |
| duracion_valor | INT | **[NUEVO]** (Z) |
| duracion_unidad | VARCHAR | **[NUEVO]** (Unidad de tiempo) |

Conclusión:
Esta historia actualiza la gestión de planes para soportar una prescripción más natural y flexible.
