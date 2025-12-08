ID: MED-PLAN-005
Título: Como médico, quiero instanciar y gestionar planes de cuidado estandarizados para asignarlos a un paciente, para reutilizar plantillas clínicas con actividades ya parametrizadas.

Descripción / Contexto:
Se habilita a los médicos a crear planes de cuidado basados en plantillas estandarizadas vigentes, generando actividades concretas (con fechas) a partir de reglas de frecuencia y duración definidas en las actividades del plan estándar. El flujo debe convivir con la creación de planes personalizados existente, reutilizando el mismo formulario pero permitiendo seleccionar un plan estándar según el diagnóstico elegido.

Alcance:
Incluye:
- ABMC de planes de cuidado creados por el médico (personalizados o basados en plantilla), respetando el mismo formulario actual con la opción adicional de “Plan de cuidado estándar”.
- Selección de paciente y, luego, diagnóstico del paciente como prerrequisito para mostrar planes estándar compatibles.
- Instanciación de un plan estándar vigente, filtrado por tipo de diagnóstico, precargando nombre/descripción y generando actividades concretas distribuidas según frecuencia/duración de la plantilla.
- Generación automática de actividades con estado inicial pendiente y validado = null/false; el médico no edita las actividades generadas.

No incluye:
- Edición de la definición del plan estándar ni de sus actividades (responsabilidad del administrador).
- Reglas de horarios intra-día u optimización por fines de semana.
- Reapertura o edición de planes ya finalizados.

Actores y Permisos:
- Médico autenticado: crea, ve, edita y elimina planes de cuidado que él mismo creó (personalizados o instanciados). No puede modificar plantillas estándar ni instancias de otros médicos.
- Paciente y Administrador: sin permisos para crear/editar planes de cuidado en este flujo.

Dependencias / Supuestos:
- Planes estándar vigentes, con tipo de diagnóstico y actividades válidas (frecuencia y duración) ya cargadas.
- Diagnóstico del paciente seleccionado (requisito previo para filtrar plantillas por tipo de diagnóstico).
- Catálogo estado_actividad existente (`pendiente`, `completada`, `vencida`).
- Librería `CarePlanTemplate` disponible para materializar actividades a partir de reglas de frecuencia/duración y offsets.
- Plan de cuidado puede almacenar `plan_estandar_id` nullable; estados del plan coherentes con MED-PLAN-004.

Flujo principal:
1. El médico accede a Planes de Cuidado → Nuevo.
2. Selecciona paciente y luego diagnóstico del paciente.
3. El sistema habilita la selección de un plan estándar vigente, filtrado por el tipo de diagnóstico del diagnóstico elegido; si no selecciona, procede como plan personalizado.
4. Al elegir un plan estándar, el formulario precarga nombre/descr del plan y muestra la lista de actividades generadas en modo solo lectura.
5. El médico define fechas de inicio y fin del plan (requeridas); la fecha de inicio es la base para generar fechas de las actividades instanciadas.
6. El sistema valida datos, genera actividades concretas según reglas de frecuencia/duración/offset de la plantilla (distribuidas equitativamente, sin excluir fines de semana), asigna estado pendiente y validado null/false.
7. Se persiste el plan con su vínculo al diagnóstico, al médico creador y al plan estándar origen; se confirma y se muestra en el listado.
8. El médico puede consultar/editar/eliminar solo sus planes mientras no estén finalizados; la edición no reescribe la plantilla ni permite editar actividades generadas.

Validaciones de negocio:
- Solo médicos autenticados y creadores pueden ABMC sus planes; no se listan ni editan planes de otros médicos.
- Debe existir diagnóstico seleccionado del paciente antes de mostrar planes estándar; la plantilla elegida debe ser vigente y de tipo de diagnóstico coincidente.
- Fechas del plan: `fechaInicio` ≤ `fechaFin`; `fechaInicio` no en pasado si así lo exige la política actual (hereda de MED-PLAN-001).
- Plantilla con actividades vigentes obligatoria para instanciar; si no hay actividades, se bloquea la creación.
- Regla de frecuencia/duración: la duración total debe cubrir al menos 1 período completo; las repeticiones por período no pueden exceder la cantidad de días del período aproximado (Día=1, Semana=7, Mes=30). Ej: no permitir 10 veces al día ni 15 veces en 1 semana.
- Distribución de actividades: número de períodos = floor(duración_en_días / días_del_período); actividades = repeticiones × períodos, distribuidas equitativamente dentro de cada período a partir de `fechaInicio + offset_inicio_dias`, sin omitir fines de semana.
- Una vez generadas, las actividades quedan en estado pendiente, validado = null/false; el médico no puede editarlas ni regenerarlas desde la instancia.
- El plan estándar seleccionado queda almacenado en `plan_estandar_id` y no puede cambiarse tras la creación; la edición del plan no modifica la plantilla ni las actividades.

Criterios de aceptación:
CA-1. Solo médicos autenticados pueden ver y usar la opción “Plan de cuidado estándar”; pacientes y administradores no pueden crear/editar planes en este flujo.
CA-2. Tras seleccionar paciente y diagnóstico, el sistema lista solo planes estándar vigentes cuyo `tipo_diagnostico_id` coincide; seleccionar uno precarga nombre/descr y bloquea edición de esos campos de plantilla.
CA-3. Al elegir un plan estándar, las actividades se generan automáticamente con estado pendiente y validado null/false, distribuidas según frecuencia/duración/offset, mostrando el total esperado (p. ej., 2/semana durante 1 mes → 8 actividades).
CA-4. Las validaciones de frecuencia/duración rechazan configuraciones donde las repeticiones superen los días del período o la duración no cubra un período completo.
CA-5. El plan se guarda asociado al diagnóstico y al médico creador, con `plan_estandar_id` cuando aplique; se confirma con mensaje de éxito y aparece en el listado del médico.
CA-6. El médico puede editar o eliminar únicamente sus planes mientras no estén finalizados; no puede modificar plantillas estándar ni las actividades ya generadas.

Casos borde y errores:
- Diagnóstico no pertenece al paciente seleccionado → error bloqueante.
- No hay planes estándar vigentes para el tipo de diagnóstico → se oculta/inhabilita la selección de plantilla; se puede continuar con plan personalizado.
- Plantilla sin actividades vigentes → impedir instanciación y mostrar mensaje.
- Intento de instanciar plantilla no vigente o de otro tipo de diagnóstico → rechazo con error.
- Plan finalizado (según MED-PLAN-004) → no permite edición/eliminación ni marcado de actividades.
- Validaciones de frecuencia/duración fallidas → no se genera ni persiste nada; mostrar motivo.

Datos mínimos / Modelo:
Tabla: `plan_estandar`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | PK | Identificador |
| nombre, descripcion | VARCHAR/TEXT | Datos de la plantilla |
| version | INT | Versión definida por administrador (solo lectura para médico) |
| vigente | BOOL | Debe ser true para instanciar |
| tipo_diagnostico_id | FK→tipos_diagnostico | Filtra plantillas por diagnóstico |

Tabla: `plan_estandar_actividad`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | PK | Identificador |
| plan_estandar_id | FK | Plantilla a la que pertenece |
| nombre, descripcion | VARCHAR/TEXT | Datos base de la actividad |
| frecuencia_repeticiones | INT | Repeticiones por período (1..días del período) |
| frecuencia_periodo | VARCHAR | Día/Semana/Mes |
| duracion_valor | INT | Cantidad de unidades |
| duracion_unidad | VARCHAR | Días/Semanas/Meses |
| offset_inicio_dias | INT | Desplazamiento desde inicio del plan |
| offset_fin_dias | INT | Calculado según duración |
| vigente | BOOL | Debe ser true para instanciar |

Tabla: `planes_cuidado`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | PK | Identificador |
| diagnostico_id | FK→diagnosticos | Diagnóstico elegido del paciente |
| creador_user_id | FK→usuarios | Médico creador (permite filtrar ABMC) |
| plan_estandar_id | FK→plan_estandar NULL | Plantilla origen si aplica |
| nombre, descripcion | VARCHAR/TEXT | Del plan instanciado (precargados si estándar) |
| fecha_creacion | DATETIME | Automática |
| fecha_inicio, fecha_fin | DATE | Definidas por el médico |
| estado | STRING | Estados según MED-PLAN-004 |

Tabla: `actividades`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | PK | Identificador |
| plan_id | FK→planes_cuidado | Plan instanciado |
| nombre, descripcion | VARCHAR/TEXT | Copiados de la plantilla |
| fecha_inicio, fecha_fin | DATE | Calculadas según frecuencia/duración/offset |
| estado_id | FK→estado_actividad | Inicial = pendiente |
| validado | BOOL NULL | Inicial null/false, solo true cuando se valide una completada |
| fecha_creacion | DATETIME | Automática |

Conclusión:
La historia habilita a los médicos a reutilizar planes de cuidado estandarizados vigentes, asegurando consistencia con el diagnóstico del paciente, validaciones de frecuencia/duración, generación automática de actividades y control de permisos sobre las instancias sin exponer la edición de plantillas.
