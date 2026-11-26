# Sprint 0 — Product Backlog

**Proyecto:** Sistema Web para Clínica Pediátrica con Farmacia Integrada
**Cliente:** Ingeniero del curso (requerimientos funcionales entregados)
**Product Owner:** José Alexis Castillo Linares
**Scrum Master:** Edilmer Eli Vasquez Narva
**Equipo de desarrollo:** Abrahan Orlando Hernández Contreras, Jeferson Vela Chappa, Billy Mark Núñez Sánchez
**Fecha de elaboración:** 24/11/2026

Prioridad (MoSCoW): **M** = Must (obligatorio), **S** = Should, **C** = Could.
Estimación en puntos de historia (escala Fibonacci).

## Roles de usuario del sistema

| Rol | Descripción |
|---|---|
| Administrador | Gestiona usuarios, roles, configuración y consulta la bitácora de auditoría. |
| Médico (pediatra) | Atiende consultas, registra historia clínica, prescribe, solicita exámenes, aplica controles de crecimiento. |
| Enfermería | Registra mediciones antropométricas y aplica vacunas. |
| Recepción | Registra pacientes y tutores, agenda y gestiona citas. |
| Farmacéutico | Gestiona inventario, lotes, dispensa recetas y lleva el libro de controlados. |
| Facturación | Visualiza cargos, emite facturas y registra pagos. |
| Tutor | Accede al portal para ver la información de sus hijos vinculados. |

---

## Módulo 1 — Gestión de Pacientes Pediátricos

| ID | Historia de usuario | Criterios de aceptación | Prior. | Pts |
|---|---|---|---|---|
| HU-01 | Como **recepcionista** quiero registrar un paciente y vincularle uno o más tutores con su parentesco y datos de contacto, para saber quién es responsable del menor. | Un paciente puede tener 1..N tutores; un tutor puede tener 1..N pacientes; se marca un tutor principal; DNI de tutor único. | M | 5 |
| HU-02 | Como **recepcionista** quiero registrar el consentimiento informado del tutor para el tratamiento de datos del menor (LPDP), para cumplir la normativa. | No se puede registrar atención sin consentimiento vigente; se guarda fecha, tutor y versión del consentimiento. | M | 3 |
| HU-03 | Como **enfermera/médico** quiero ingresar peso, talla y perímetro cefálico y que el sistema calcule el percentil y la puntuación Z OMS según edad exacta, para evaluar el crecimiento. | Cálculo con método LMS de la OMS; edad exacta en días; alertas visuales si Z < −2 o Z > +2 o cruce de 2 líneas de percentil respecto al control anterior. | M | 8 |
| HU-04 | Como **médico** quiero una ficha clínica pediátrica con antecedentes prenatales, natales y posnatales obligatorios, para conocer la historia del paciente. | Campos obligatorios validados; no se guarda incompleta. | M | 5 |
| HU-05 | Como **médico** quiero evaluar hitos del desarrollo psicomotor por dominios (motricidad, lenguaje, social) y registrar el cribado PSC-35, para detectar alteraciones. | PSC-35: 35 ítems con puntaje 0–2; puntaje total ≥ 28 marca riesgo (≥ 24 en 4–5 años). | M | 5 |
| HU-06 | Como **enfermera** quiero ver el calendario de vacunas del paciente con estado aplicada/pendiente/vencida y, al registrar la aplicación, que se descuente automáticamente la dosis del inventario de farmacia. | Estado calculado por edad; aplicación exige lote vigente con stock; stock disminuye en 1; se registra lote aplicado. | M | 8 |
| HU-07 | Como **administrador** quiero el reporte de cobertura de vacunación por grupo de edad y vacuna (meta, aplicadas, brecha), exportable para el MINSA. | Filtros por fecha; exporta PDF y Excel. | M | 5 |
| HU-08 | Como **enfermera** quiero el listado de pacientes con vacunas pendientes o vencidas, para programar campañas o recordatorios. | Lista con tutor y teléfono; exporta. | M | 3 |
| HU-09 | Como **médico** quiero el reporte de crecimiento y desarrollo con alertas, para priorizar seguimiento. | Lista pacientes con Z fuera de rango o cruce de percentiles. | M | 3 |
| HU-10 | Como **médico** quiero registrar y reportar un ESAVI con paciente, vacuna, lote y síntomas, para notificar a la autoridad sanitaria. | Reporte imprimible/PDF. | M | 3 |

## Módulo 2 — Gestión Clínica y Consultas

| ID | Historia de usuario | Criterios de aceptación | Prior. | Pts |
|---|---|---|---|---|
| HU-11 | Como **médico** quiero registrar la historia clínica con plantillas por grupo de edad (neonato, lactante, preescolar, escolar/adolescente), documentando interrogatorio y examen físico segmentado. | Plantilla seleccionada automáticamente por edad; campos de interrogatorio (alimentación, sueño, escolaridad) según plantilla. | M | 8 |
| HU-12 | Como **médico** quiero emitir una receta electrónica con cálculo de dosis por peso (mg/kg/día) o superficie corporal, con alertas si excede el rango pediátrico o hay interacción grave. | Dosis calculada = peso × mg/kg/día; alerta si fuera de [mín, máx]; bloqueo o confirmación ante interacción grave; SC por fórmula de Mosteller. | M | 8 |
| HU-13 | Como **recepcionista** quiero agendar citas presenciales o por telemedicina, con recordatorio automático al tutor por correo/SMS. | Sin cruce de horario del médico; cita de telemedicina genera enlace de videollamada. | M | 5 |
| HU-14 | Como **recepcionista** quiero cancelar o reprogramar citas solo hasta 24 h antes, según política de la clínica. | Rechaza cancelación con menos de 24 h; registra motivo. | M | 3 |
| HU-15 | Como **médico** quiero generar órdenes de laboratorio o imágenes vinculadas a la consulta y recibir resultados destacando valores anormales. | Resultado con valor y rango de referencia; marca anormal si fuera de rango; adjunta archivo PDF/imagen. | M | 5 |
| HU-16 | Como **administrador** quiero el reporte de producción médica por profesional y tipo de servicio. | Filtros por período, médico y servicio. | M | 3 |
| HU-17 | Como **administrador** quiero el reporte de diagnósticos CIE-10 más frecuentes filtrable por edad y sexo. | Top de diagnósticos con conteo. | M | 3 |
| HU-18 | Como **administrador** quiero el reporte de ausentismo y cancelaciones por médico y tipo de cita. | Porcentaje de canceladas y no-show. | M | 3 |

## Módulo 3 — Farmacia e Inventario

| ID | Historia de usuario | Criterios de aceptación | Prior. | Pts |
|---|---|---|---|---|
| HU-19 | Como **farmacéutico** quiero ver la receta electrónica y registrar la dispensación, validando que el medicamento coincida y la cantidad no exceda lo prescrito. | Rechaza medicamento distinto; rechaza cantidad mayor a la pendiente. | M | 8 |
| HU-20 | Como **farmacéutico** quiero gestionar stock por lote y fecha de vencimiento, con sugerencia FEFO y bloqueo de lotes vencidos o en cuarentena. | Sugiere el lote con vencimiento más próximo no vencido; lotes vencidos/cuarentena no dispensables. | M | 8 |
| HU-21 | Como **farmacéutico** quiero identificar a los pacientes y tutores que recibieron un lote ante una alerta sanitaria y registrar la disposición final del stock retirado. | Búsqueda por lote; lista de pacientes/tutores; lote pasa a "retirado". | M | 5 |
| HU-22 | Como **farmacéutico** quiero llevar el libro de estupefacientes y controlados con saldo acumulado, exigiendo farmacéutico responsable y tutor que retira. | Cada movimiento recalcula saldo; no permite saldo negativo; DNI del tutor obligatorio. | M | 5 |
| HU-23 | Como **farmacéutico** quiero el reporte de vencimientos y lotes críticos (≤ 90 días) con ubicación en almacén. | Filtro de días configurable. | M | 3 |
| HU-24 | Como **administrador** quiero el reporte de rotación y consumo de medicamentos. | Mayor/menor rotación por período. | M | 3 |
| HU-25 | Como **farmacéutico** quiero conciliar stock teórico vs. físico con indicador de consistencia ≥ 99 %. | Calcula diferencia y % de consistencia; marca si < 99 %. | M | 5 |
| HU-26 | Como **farmacéutico** quiero registrar errores de dispensación y prescripción con evidencia, para auditorías. | Adjunta evidencia; tipo, descripción, medida correctiva. | M | 3 |
| HU-27 | Como **farmacéutico** quiero imprimir/exportar el libro oficial de estupefacientes y psicotrópicos. | PDF/Excel con saldo y movimientos. | M | 2 |

## Módulo 4 — Facturación y Administración

| ID | Historia de usuario | Criterios de aceptación | Prior. | Pts |
|---|---|---|---|---|
| HU-28 | Como **facturador** quiero una cuenta única por paciente que consolide consulta, procedimientos (vacunas, curaciones) y medicamentos dispensados en una sola factura. | Cargos se acumulan automáticamente; IGV 18 %; una factura por cuenta. | M | 8 |
| HU-29 | Como **administrador** quiero roles y permisos granulares (farmacéutico no modifica historia clínica; médico no altera inventario; facturación solo ve cargos y pagos). | Acceso denegado (403) fuera de permisos. | M | 5 |
| HU-30 | Como **administrador** quiero una auditoría inmutable de operaciones sensibles (dispensación de controlados, modificación de receta, anulación de factura). | Registro no editable ni eliminable, con usuario, fecha, hora, IP y detalle. | M | 5 |
| HU-31 | Como **administrador** quiero el reporte de ingresos consolidados clínica + farmacia. | Facturado vs. cobrado; servicios vs. medicamentos. | M | 3 |
| HU-32 | Como **administrador** quiero el ticket promedio por paciente por período. | Total / pacientes atendidos. | M | 2 |
| HU-33 | Como **facturador** quiero las cuentas por cobrar por antigüedad (30, 60, 90 días). | Agrupa saldos pendientes por tramo. | M | 3 |
| HU-34 | Como **administrador** quiero consultar la bitácora de auditoría. | Filtros por usuario, acción y fecha. | M | 2 |

## Módulo 5 — Comunicación y Portal de Tutores

| ID | Historia de usuario | Criterios de aceptación | Prior. | Pts |
|---|---|---|---|---|
| HU-35 | Como **tutor** quiero iniciar sesión de forma segura y ver a todos mis hijos vinculados desde una sola cuenta. | Solo ve pacientes vinculados; acceso a otro paciente devuelve 403. | M | 5 |
| HU-36 | Como **tutor** quiero ver las gráficas de crecimiento y el estado del calendario de vacunas de mi hijo. | Gráfica peso/talla vs. edad; próximas dosis. | M | 5 |
| HU-37 | Como **tutor** quiero descargar en PDF las recetas y resultados de laboratorio y ver el historial de consultas. | Descarga registrada para el reporte de entrega. | M | 3 |
| HU-38 | Como **tutor** quiero recibir notificaciones de citas, vacunas vencidas y receta lista para retiro por correo/SMS. | SMS vía pasarela Android; correo; registro de envío y apertura. | M | 5 |
| HU-39 | Como **administrador** quiero el reporte de entrega de resultados y recetas (quién descargó y quién no). | Lista con estado. | M | 2 |
| HU-40 | Como **administrador** quiero el reporte de efectividad de notificaciones por canal. | Enviadas, fallidas, abiertas por canal. | M | 2 |

## Transversal — Interoperabilidad y Exportación

| ID | Historia de usuario | Criterios de aceptación | Prior. | Pts |
|---|---|---|---|---|
| HU-41 | Como **administrador** quiero exportar/importar información clínica en HL7 FHIR (Patient, Observation, Immunization). | JSON FHIR R4 válido; códigos LOINC para mediciones. | S | 5 |
| HU-42 | Como **usuario** quiero que todos los reportes se vean en pantalla, se impriman y se exporten a PDF y Excel, con filtros por fechas, profesional, servicio y tipo de paciente. | Botones PDF/Excel/Imprimir en cada reporte. | M | 5 |

## Base técnica (habilitadores)

| ID | Historia | Prior. | Pts |
|---|---|---|---|
| HT-01 | Proyecto Laravel + PostgreSQL configurado, layout base y navegación por rol. | M | 3 |
| HT-02 | Autenticación (login/logout), bloqueo por intentos fallidos. | M | 3 |
| HT-03 | Despliegue en hosting gratuito. | M | 3 |

**Total:** 42 historias de usuario + 3 habilitadores técnicos.
