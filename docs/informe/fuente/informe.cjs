// Informe del trabajo final — Prueba y Calidad de Software (formato APA 7.ª ed.)
const fs = require('fs');
const path = require('path');
const {
  Document, Packer, Paragraph, TextRun, ImageRun, Table, TableRow, TableCell, Header, AlignmentType,
  HeadingLevel, PageNumber, PageBreak, BorderStyle, WidthType, ShadingType, TableOfContents,
  SimpleField, LevelFormat, VerticalAlign, TabStopType, LineRuleType,
} = require('docx');

const IMG = path.join(__dirname, 'img');
const FUENTE = 'Times New Roman';
const ANCHO = 9026; // A4 (11906) - márgenes 1440 x 2
const DOBLE = { line: 480, lineRule: LineRuleType.AUTO, before: 0, after: 0 };
const SIMPLE = { line: 240, lineRule: LineRuleType.AUTO, before: 0, after: 0 };
const IMAGEN = { line: 240, lineRule: LineRuleType.AUTO, before: 120, after: 120 };

// ---------- utilidades ----------
const r = (text, o = {}) => new TextRun({ text, font: FUENTE, size: 24, ...o });
function runs(contenido, base = {}) {
  // admite: string, o array de string / [texto, {bold, italics}]
  if (typeof contenido === 'string') return [r(contenido, base)];
  return contenido.map(c => (typeof c === 'string' ? r(c, base) : r(c[0], { ...base, ...c[1] })));
}
const p = (contenido, o = {}) => new Paragraph({
  children: runs(contenido), spacing: DOBLE, indent: o.sinSangria ? undefined : { firstLine: 720 },
  alignment: o.alineacion || AlignmentType.LEFT, keepNext: o.keepNext,
});
const pendiente = texto => new Paragraph({
  spacing: DOBLE, indent: { firstLine: 720 },
  children: [r(`[Pendiente: ${texto}]`, { italics: true, highlight: 'yellow' })],
});
const h1 = t => new Paragraph({ heading: HeadingLevel.HEADING_1, alignment: AlignmentType.CENTER, spacing: DOBLE, keepNext: true, children: [r(t, { bold: true })] });
const h2 = t => new Paragraph({ heading: HeadingLevel.HEADING_2, spacing: DOBLE, keepNext: true, children: [r(t, { bold: true })] });
const h3 = t => new Paragraph({ heading: HeadingLevel.HEADING_3, spacing: DOBLE, keepNext: true, children: [r(t, { bold: true, italics: true })] });
const salto = () => new Paragraph({ children: [new PageBreak()] });
const vinieta = (contenido, nivel = 0) => new Paragraph({ numbering: { reference: 'vinetas', level: nivel }, spacing: DOBLE, children: runs(contenido) });
const numerada = (contenido, ref = 'numeros') => new Paragraph({ numbering: { reference: ref, level: 0 }, spacing: DOBLE, children: runs(contenido) });

// Etiqueta APA: "Tabla N" (negrita) y, en la línea siguiente, el título en cursiva.
// El campo SEQ permite generar el índice de tablas/figuras.
function rotulo(tipo, titulo) {
  return new Paragraph({
    spacing: DOBLE, keepNext: true, keepLines: true,
    children: [
      r(`${tipo} `, { bold: true }),
      new SimpleField(`SEQ ${tipo} \\* ARABIC`),
      r('', { break: 1 }),
      r(titulo, { italics: true }),
    ],
  });
}
const nota = texto => new Paragraph({ spacing: DOBLE, children: [r('Nota. ', { italics: true }), ...runs(texto)] });

const borde = { style: BorderStyle.SINGLE, size: 6, color: '000000' };
const sinBorde = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
function tabla(titulo, encabezados, filas, anchos, notaTexto) {
  const total = anchos.reduce((a, b) => a + b, 0);
  const escala = ANCHO / total;
  const w = anchos.map(a => Math.floor(a * escala));
  w[w.length - 1] += ANCHO - w.reduce((a, b) => a + b, 0);
  const celda = (contenido, i, esEnc, ultimaFila) => new TableCell({
    width: { size: w[i], type: WidthType.DXA },
    margins: { top: 40, bottom: 40, left: 80, right: 80 },
    verticalAlign: VerticalAlign.TOP,
    borders: {
      top: esEnc ? borde : sinBorde,
      bottom: esEnc || ultimaFila ? borde : sinBorde,
      left: sinBorde, right: sinBorde,
    },
    children: [contenido].map(c =>
      new Paragraph({ spacing: SIMPLE, alignment: esEnc ? AlignmentType.CENTER : AlignmentType.LEFT, children: runs(c, { size: 21, bold: esEnc || undefined }) })),
  });
  const t = new Table({
    width: { size: ANCHO, type: WidthType.DXA }, columnWidths: w,
    borders: { top: borde, bottom: borde, left: sinBorde, right: sinBorde, insideHorizontal: sinBorde, insideVertical: sinBorde },
    rows: [
      new TableRow({ tableHeader: true, children: encabezados.map((e, i) => celda(e, i, true, false)) }),
      ...filas.map((f, k) => new TableRow({ cantSplit: true, children: f.map((c, i) => celda(c, i, false, k === filas.length - 1)) })),
    ],
  });
  const salida = [rotulo('Tabla', titulo), t];
  if (notaTexto) salida.push(nota(notaTexto));
  salida.push(new Paragraph({ spacing: DOBLE, children: [] }));
  return salida;
}

function figura(titulo, archivo, notaTexto, anchoPx = 500) {
  const buf = fs.readFileSync(path.join(IMG, archivo));
  const { width, height } = require('./dim')(path.join(IMG, archivo));
  const alto = Math.round(anchoPx * height / width);
  const tipo = archivo.endsWith('.jpg') ? 'jpg' : 'png';
  const salida = [
    rotulo('Figura', titulo),
    new Paragraph({ alignment: AlignmentType.CENTER, spacing: IMAGEN, keepNext: true, children: [new ImageRun({ type: tipo, data: buf, transformation: { width: anchoPx, height: alto }, altText: { title: titulo, description: titulo, name: archivo } })] }),
  ];
  if (notaTexto) salida.push(nota(notaTexto));
  salida.push(new Paragraph({ spacing: DOBLE, children: [] }));
  return salida;
}

const ref = contenido => new Paragraph({ spacing: DOBLE, indent: { left: 720, hanging: 720 }, children: runs(contenido) });

// ---------- contenido ----------
const URL = 'https://clinica-pediatrica-wr1u.onrender.com';
const REPO = 'https://github.com/edilmer2107/clinica_pediatra';
const integrantes = [
  ['Billy Mark Núñez Sánchez', '7152900731'],
  ['Edilmer Eli Vasquez Narva', '7196803631'],
  ['Abrahan Orlando Hernández Contreras', '7280718022'],
  ['José Alexis Castillo Linares', '7480576292'],
  ['Jeferson Vela Chappa', '7562101231'],
].sort((a, b) => a[0].localeCompare(b[0], 'es'));

const centro = (texto, o = {}) => new Paragraph({ alignment: AlignmentType.CENTER, spacing: { line: 360, lineRule: LineRuleType.AUTO, before: o.antes || 0, after: o.despues || 0 }, children: [r(texto, { bold: true, size: o.size || 28 })] });

const caratula = [
  centro('UNIVERSIDAD NACIONAL TORIBIO RODRÍGUEZ DE MENDOZA DE AMAZONAS', { size: 30, despues: 240 }),
  centro('FACULTAD DE INGENIERÍA DE SISTEMAS Y MECÁNICA ELÉCTRICA', { despues: 240 }),
  centro('Programa de Estudios de Ingeniería de Sistemas', { size: 26, despues: 360 }),
  centro('TRABAJO DE SEGUNDA UNIDAD DEL CURSO PRUEBA Y CALIDAD DE SOFTWARE', { despues: 240 }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { line: 360, lineRule: LineRuleType.AUTO, after: 240 }, children: [r('Aplicación de pruebas de software y evaluación de la calidad del Sistema Web para Clínica Pediátrica con Farmacia Integrada', { italics: true, size: 26 })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { line: 240, lineRule: LineRuleType.AUTO, before: 120, after: 360 }, children: [new ImageRun({ type: 'jpg', data: fs.readFileSync(path.join(IMG, 'logo-000.jpg')), transformation: { width: 170, height: 208 }, altText: { title: 'Logo UNTRM', description: 'Logotipo de la Universidad Nacional Toribio Rodríguez de Mendoza de Amazonas', name: 'logo' } })] }),
  ...integrantes.map(([n, c]) => centro(`${n} - ${c}`, { size: 24 })),
  centro('Mg. Luciano Llauce Valdera', { size: 24, antes: 360 }),
  centro('Chachapoyas, 2026', { size: 24, antes: 240 }),
  salto(),
];

const indices = [
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: DOBLE, children: [r('Índice general', { bold: true })] }),
  new TableOfContents('Índice general', { hyperlink: true, headingStyleRange: '1-3' }),
  salto(),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: DOBLE, children: [r('Índice de tablas', { bold: true })] }),
  new TableOfContents('Índice de tablas', { hyperlink: true, captionLabelIncludingNumbers: 'Tabla' }),
  salto(),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: DOBLE, children: [r('Índice de figuras', { bold: true })] }),
  new TableOfContents('Índice de figuras', { hyperlink: true, captionLabelIncludingNumbers: 'Figura' }),
  salto(),
];

const resumen = [
  h1('Resumen ejecutivo'),
  pendiente('el resumen ejecutivo (máximo una página) se redactará al concluir la ejecución de las pruebas, pues debe incluir los resultados cuantitativos, los defectos de mayor impacto, las limitaciones y la conclusión general, según la guía del trabajo.'),
  salto(),
];

const introduccion = [
  h1('Introducción'),
  p('La atención pediátrica exige un control riguroso del crecimiento, del desarrollo y de la inmunización de los niños, además de una prescripción que ajuste la dosis al peso o a la superficie corporal del paciente. Cuando la clínica cuenta con una farmacia propia, la información clínica, el inventario de medicamentos y la facturación deben mantenerse integrados para evitar errores de medicación, pérdidas por vencimiento y cobros fragmentados. En ese contexto, el equipo desarrolló el Sistema Web para Clínica Pediátrica con Farmacia Integrada, a partir de los requerimientos funcionales planteados por el docente en el rol de cliente.'),
  p(['Un sistema que calcula percentiles, dosis pediátricas y saldos de medicamentos controlados no puede aceptarse solo porque “funciona” en una demostración. La calidad del producto debe evaluarse de forma sistemática, con evidencias verificables, sobre las características que define la norma ISO/IEC 25010 (International Organization for Standardization [ISO], 2023), en especial la adecuación funcional y la seguridad. Como señalan Sommerville (2016) y el ', ['International Software Testing Qualifications Board', { italics: false }], ' (ISTQB, 2023), las pruebas permiten reducir el riesgo de fallos en producción y aportan información objetiva para decidir si el software está listo para su uso.']),
  p('El propósito de este trabajo es aplicar los fundamentos, técnicas y prácticas de prueba y calidad de software sobre la aplicación desarrollada por el equipo. Para ello se elaboró un Plan Maestro de Pruebas y se aplican cuatro enfoques complementarios: pruebas funcionales automatizadas con PHPUnit y Codeception, pruebas basadas en la experiencia (incluidas sesiones exploratorias y evaluación de usabilidad), pruebas de seguridad dentro de un alcance autorizado con OWASP ZAP y análisis estático del código fuente con SonarQube.'),
  p('El informe se organiza de la siguiente manera. Primero se describe la aplicación desarrollada: el problema que atiende, sus usuarios, la metodología ágil empleada, la arquitectura, las tecnologías, la versión evaluada, el despliegue y el alcance funcional por módulos. Luego se presenta el Plan Maestro de Pruebas. Las secciones posteriores documentan el diseño de las pruebas, la preparación del entorno, la ejecución y sus resultados, y cierran con las conclusiones, recomendaciones, referencias y anexos.'),
  salto(),
];

const diagnostico = [
  h1('Diagnóstico y descripción de la aplicación desarrollada'),
  h2('Problema y necesidad que atiende'),
  p('En una clínica pediátrica con farmacia, la información suele registrarse en herramientas separadas: la historia clínica en papel u hojas de cálculo, el carné de vacunación en manos del tutor, el inventario de la farmacia en un sistema distinto y la facturación en cajas independientes. Esta fragmentación produce problemas concretos: el cálculo manual de percentiles retrasa la detección de desnutrición o sobrepeso; el cálculo manual de dosis por peso aumenta el riesgo de sobredosis; la dispensación sin control de lotes permite entregar medicamentos vencidos; y el paciente debe pagar en varias cajas. Además, la normativa peruana exige proteger los datos personales de los menores de edad (Ley N.º 29733, 2011) y mantener un control estricto de estupefacientes y psicotrópicos.'),
  p('El sistema desarrollado integra en una sola aplicación web la gestión de pacientes y tutores, la atención clínica, la farmacia, la facturación y la comunicación con las familias, y aplica de forma automática las reglas de negocio que antes dependían del cálculo manual.'),
  h2('Usuarios previstos y propósito'),
  p('La aplicación está dirigida al personal de la clínica y a los tutores de los pacientes. Cada perfil accede solo a las funciones que le corresponden mediante roles y permisos granulares (Tabla 1).'),
  ...tabla('Usuarios del sistema y funciones principales', ['Rol', 'Usuario previsto', 'Funciones principales'], [
    ['Administrador', 'Jefatura de la clínica', 'Usuarios y roles, bitácora de auditoría, anulación de comprobantes, interoperabilidad FHIR, todos los reportes'],
    ['Médico pediatra', 'Pediatras', 'Historia clínica, diagnósticos CIE-10, recetas con cálculo de dosis, órdenes de laboratorio, ficha clínica, desarrollo'],
    ['Enfermería', 'Licenciadas en enfermería', 'Mediciones antropométricas, aplicación de vacunas, reporte de ESAVI, registro de resultados'],
    ['Recepción', 'Personal de admisión', 'Registro de pacientes, tutores y consentimiento; agenda, cancelación y reprogramación de citas'],
    ['Farmacéutico', 'Químico farmacéutico', 'Inventario por lotes, dispensación, libro de controlados, retiros de mercado, conciliación, errores de medicación'],
    ['Facturación', 'Cajero(a)', 'Cuentas de pacientes, emisión de comprobantes y registro de pagos'],
    ['Tutor', 'Padre, madre o tutor legal', 'Portal: información de todos sus hijos, crecimiento, vacunas, recetas y resultados'],
  ], [16, 20, 64], 'Elaboración propia.'),
  p('El propósito del sistema es apoyar la atención pediátrica integral con información confiable y oportuna: detectar alteraciones del crecimiento, asegurar el cumplimiento del esquema de vacunación, prescribir y dispensar de forma segura, consolidar los cobros en una sola cuenta y mantener informados a los tutores.'),
  h2('Metodología de desarrollo'),
  p(['El sistema se desarrolló con Scrum, marco ágil que organiza el trabajo en iteraciones cortas llamadas ', ['sprints', { italics: true }], ', con roles, eventos y artefactos definidos (Schwaber & Sutherland, 2020). Por el plazo disponible (del 24 de noviembre al 2 de diciembre de 2026) se adoptaron sprints de un día, cada uno con un incremento funcional desplegable. La Tabla 2 muestra los roles del equipo.']),
  ...tabla('Roles Scrum y responsabilidades del equipo', ['Integrante', 'Rol Scrum', 'Responsabilidad en la documentación y pruebas'], [
    ['Edilmer Eli Vasquez Narva', 'Scrum Master', 'Metodología, arquitectura, despliegue y coordinación de pruebas'],
    ['José Alexis Castillo Linares', 'Product Owner', 'Product backlog, historias de usuario y pruebas basadas en experiencia (UX)'],
    ['Abrahan Orlando Hernández Contreras', 'Equipo de desarrollo', 'Pruebas unitarias y de integración con PHPUnit'],
    ['Jeferson Vela Chappa', 'Equipo de desarrollo', 'Pruebas funcionales y de aceptación con Codeception'],
    ['Billy Mark Núñez Sánchez', 'Equipo de desarrollo', 'Análisis estático con SonarQube y pruebas de seguridad con OWASP ZAP'],
  ], [34, 20, 46], 'El docente del curso actuó como cliente y entregó los requerimientos funcionales.'),
  p('El product backlog se construyó a partir de los requerimientos del cliente y contiene 42 historias de usuario y 3 habilitadores técnicos, priorizados con la técnica MoSCoW y estimados en puntos de historia. La Tabla 3 resume la planificación de los sprints.'),
  ...tabla('Planificación de sprints', ['Sprint', 'Fecha', 'Objetivo del sprint', 'Incremento entregado'], [
    ['Sprint 0', '24-25/11/2026', 'Planificación', 'Product backlog, arquitectura, modelo de datos, proyecto base'],
    ['Sprint 1', '26/11/2026', 'Base del sistema', 'Autenticación, roles y permisos, auditoría, plantilla de interfaz'],
    ['Sprint 2', '27/11/2026', 'Módulo 3', 'Farmacia e inventario y su infraestructura de reportes'],
    ['Sprint 3', '28/11/2026', 'Módulo 1', 'Pacientes pediátricos, percentiles OMS y vacunación'],
    ['Sprint 4', '29/11/2026', 'Módulo 2', 'Citas, consultas, recetas y laboratorio'],
    ['Sprint 5', '30/11/2026', 'Módulos 4 y 5', 'Facturación, portal de tutores, FHIR y despliegue de la versión 1.0.0'],
    ['Sprint 6', '01/12/2026', 'Pruebas', 'Ejecución de pruebas funcionales, de seguridad, estáticas y de experiencia'],
    ['Cierre', '02/12/2026', 'Corrección', 'Corrección de defectos (versión 1.1.0), re-pruebas e informe final'],
  ], [12, 17, 21, 50], `El detalle de las historias de usuario se encuentra en el repositorio del proyecto (${REPO}, carpeta docs).`),
  p('El Módulo 3 (Farmacia) se construyó antes que el Módulo 1 porque la aplicación de vacunas descuenta stock del inventario; así se respetó la dependencia técnica entre ambos módulos.'),
  h2('Arquitectura'),
  p('Se eligió una arquitectura de monolito modular por capas basada en el patrón Modelo-Vista-Controlador (MVC) de Laravel, con una capa adicional de servicios que concentra las reglas de negocio (Figura 1). Esta decisión responde a tres criterios: el plazo de desarrollo, el despliegue en un hosting gratuito con una sola base de datos y la facilidad para probar la lógica de negocio de forma aislada. Una arquitectura de microservicios habría multiplicado los despliegues y la complejidad de las pruebas sin aportar beneficios para el tamaño de este sistema.'),
  ...figura('Arquitectura del sistema por capas', '00_arquitectura.png', 'Los servicios no dependen de la capa HTTP, lo que permite probarlos de forma unitaria con PHPUnit, mientras que los flujos completos se prueban con Codeception. Elaboración propia.'),
  p('La base de datos relacional consta de 47 tablas organizadas por módulo. La Figura 2 resume las entidades principales y sus relaciones, incluidas las que vinculan los módulos entre sí (por ejemplo, la aplicación de una vacuna descuenta el lote de farmacia y genera un cargo en la cuenta única del paciente).'),
  ...figura('Modelo de datos resumido por módulo', '00_modelo_datos.png', 'La notación 1—N indica una relación de uno a muchos. Elaboración propia.', 600),
  h2('Tecnologías, lenguajes y librerías'),
  ...tabla('Tecnologías utilizadas y versiones', ['Componente', 'Tecnología', 'Versión', 'Uso en el sistema'], [
    ['Lenguaje', 'PHP', '8.3', 'Lenguaje del servidor'],
    ['Framework', 'Laravel', '13.44.1', 'MVC, ORM Eloquent, validación, autenticación, colas y planificador'],
    ['Base de datos', 'PostgreSQL', '16', 'Persistencia; trigger de inmutabilidad de la auditoría'],
    ['Interfaz', 'Blade + Tailwind CSS', '4.3.5', 'Vistas y estilos'],
    ['Interactividad', 'Alpine.js', '3.17.5', 'Formularios dinámicos (receta, pestañas)'],
    ['Gráficos', 'Chart.js', '4.6.2', 'Curvas de crecimiento OMS y gráficos de reportes'],
    ['Compilación', 'Vite / Node.js', '8.4.0 / 22', 'Empaquetado de recursos'],
    ['Seguridad', 'spatie/laravel-permission', '8.5.1', 'Roles y permisos granulares'],
    ['Documentos', 'barryvdh/laravel-dompdf', '3.1.2', 'Recetas, comprobantes y reportes en PDF'],
    ['Hojas de cálculo', 'phpoffice/phpspreadsheet', '5.12.0', 'Exportación de reportes a Excel'],
    ['Despliegue', 'Docker + Apache en Render', '—', 'Contenedor PHP 8.3 con HTTPS'],
    ['Pruebas', 'PHPUnit', '12.5.45', 'Pruebas unitarias y de integración'],
  ], [17, 27, 14, 42], 'Versiones tomadas del archivo composer.lock y package-lock.json de la versión 1.0.0.'),
  h2('Módulos y alcance funcional'),
  p('La aplicación cuenta con cinco módulos funcionales y una consideración transversal de interoperabilidad y exportación. La Tabla 5 describe el propósito de cada módulo y las funciones principales que se someten a prueba, con sus reglas de negocio y validaciones relevantes.'),
  ...tabla('Módulos de la aplicación y funciones sujetas a prueba', ['Módulo', 'Propósito', 'Funciones principales sujetas a prueba'], [
    ['1. Gestión de Pacientes Pediátricos', 'Registrar al paciente, sus tutores y su información de crecimiento, desarrollo e inmunización', 'Vínculo tutor-paciente y consentimiento obligatorio (Ley 29733); cálculo de Z y percentil OMS por edad exacta con alertas (Z < −2, Z > +2, cruce de 2 o más líneas de percentil); ficha clínica con antecedentes obligatorios; hitos del desarrollo y PSC-35 (corte ≥ 28 o ≥ 24); calendario de vacunas con estados y descuento de stock; ESAVI'],
    ['2. Gestión Clínica y Consultas', 'Atender al paciente desde la cita hasta la prescripción y los exámenes', 'Citas presenciales y por telemedicina sin cruces de horario; cancelación o reprogramación solo hasta 24 h antes; plantilla de historia clínica según la edad; receta con dosis por peso (mg/kg/día) o superficie corporal (Mosteller); alertas de dosis fuera de rango; bloqueo por interacción grave; órdenes de laboratorio con valores anormales'],
    ['3. Farmacia e Inventario', 'Controlar el stock por lote y dispensar de forma segura', 'Sugerencia FEFO; bloqueo de lotes vencidos, en cuarentena o retirados; correspondencia exacta medicamento prescrito/dispensado y cantidad no mayor a la indicada; libro de controlados con saldo acumulado y DNI del tutor; trazabilidad inversa; conciliación con consistencia ≥ 99 %'],
    ['4. Facturación y Administración', 'Consolidar los cobros y controlar el acceso y las operaciones sensibles', 'Cuenta única por paciente (servicios + medicamentos, IGV 18 %); pagos sin exceder el saldo; anulación con motivo; roles y permisos (farmacéutico no edita historia clínica, médico no altera inventario, facturación solo ve cargos y pagos); auditoría inmutable con cadena SHA-256'],
    ['5. Comunicación y Portal de Tutores', 'Dar a la familia acceso seguro a la información de sus hijos y enviar recordatorios', 'Acceso con sesión segura y visibilidad familiar; restricción a hijos vinculados (acceso a otros pacientes = 403); gráficas de crecimiento y vacunas; descarga de recetas y resultados en PDF; notificaciones por correo y SMS con seguimiento de apertura'],
    ['Transversal', 'Interoperabilidad y exportación de reportes', 'Exportación e importación HL7 FHIR R4 (LOINC, CVX, CIE-10); 18 reportes en pantalla, impresión, PDF y Excel con filtros por fechas, profesional, servicio y tipo de paciente'],
  ], [20, 22, 58], 'Elaboración propia a partir de los requerimientos funcionales del cliente.'),
  p('Las figuras 3 a 10 muestran las principales pantallas de la versión evaluada, capturadas del sistema desplegado.'),
  ...figura('Pantalla de inicio de sesión', '01_login.png', `Captura del sistema desplegado en ${URL}.`),
  ...figura('Ficha del paciente: crecimiento con percentiles y alertas OMS', '04_crecimiento_c.png', 'La paciente presenta una caída de tres líneas de percentil en peso (de Z 0.11 a Z −2.30) y el sistema genera las alertas de forma automática. Las curvas corresponden a los percentiles 3, 15, 50, 85 y 97 de la Organización Mundial de la Salud (OMS, 2006). Datos ficticios.', 520),
  ...figura('Calendario de vacunación con estados aplicada, pendiente y vencida', '05_vacunas.png', 'Al registrar una aplicación, el sistema descuenta automáticamente una dosis del lote seleccionado en farmacia. Datos ficticios.'),
  ...figura('Cálculo de dosis pediátrica con alerta de dosis fuera de rango', '06_dosis.png', 'Para 16 kg y 100 mg/kg/día de amoxicilina, el sistema calcula 1600 mg/día y advierte que se excede el máximo pediátrico de 90 mg/kg/día; la receta no se emite sin una justificación clínica.', 480),
  ...figura('Sugerencia FEFO y bloqueo de lote vencido en farmacia', '09_fefo.png', 'El lote L24-VENC01 está vencido: el sistema lo excluye del stock vigente y sugiere el lote L26-011 según el criterio FEFO (first expired, first out).'),
  ...figura('Libro de estupefacientes y psicotrópicos con saldo acumulado', '10_controlados.png', 'Cada salida exige la identificación del farmacéutico responsable y del tutor que retira.'),
  ...figura('Cuenta única del paciente con servicios médicos y medicamentos', '11_cuenta_unica.png', 'Todos los cargos se consolidan en un solo comprobante con IGV incluido.'),
  ...figura('Portal de tutores con visibilidad familiar', '12_portal.png', 'El tutor visualiza a todos sus hijos vinculados desde una sola cuenta.'),
  h2('Versión evaluada, despliegue y condiciones de acceso'),
  p(['La versión sometida a evaluación es la ', ['1.0.0', { bold: true }], `, identificada en el repositorio con la etiqueta v1.0.0 (commit 0ba5928) y publicada en ${REPO}/releases/tag/v1.0.0. Esta versión se mantiene sin cambios durante la ejecución de las pruebas; las correcciones de defectos se publicarán como versión 1.1.0 y se aplicarán las re-pruebas correspondientes.`]),
  p(`El sistema está desplegado en un contenedor Docker (PHP 8.3 con Apache) en la plataforma Render, con base de datos PostgreSQL gestionada y acceso por HTTPS en ${URL}. Los datos cargados son ficticios y se generan mediante los seeders del proyecto: 20 pacientes, sus tutores, mediciones, vacunas, citas, consultas, recetas, dispensaciones y comprobantes. No se utilizan datos personales reales. La Tabla 6 presenta las cuentas de prueba.`),
  ...tabla('Cuentas de prueba por rol', ['Rol', 'Correo', 'Contraseña'], [
    ['Administrador', 'admin@clinica.test', 'Clinica2026!'],
    ['Médico pediatra', 'medico@clinica.test', 'Clinica2026!'],
    ['Médico pediatra', 'medico2@clinica.test', 'Clinica2026!'],
    ['Enfermería', 'enfermeria@clinica.test', 'Clinica2026!'],
    ['Recepción', 'recepcion@clinica.test', 'Clinica2026!'],
    ['Farmacéutico', 'farmacia@clinica.test', 'Clinica2026!'],
    ['Facturación', 'facturacion@clinica.test', 'Clinica2026!'],
    ['Tutor (portal)', 'tutor@clinica.test', 'Clinica2026!'],
  ], [30, 45, 25], 'Credenciales exclusivas del entorno de pruebas, con datos ficticios. Tras cinco intentos fallidos el correo se bloquea por cinco minutos.'),
  h3('Requisitos de instalación y ejecución'),
  p('Para ejecutar el sistema de forma local se requiere PHP 8.3 con las extensiones pdo_pgsql, gd, intl y zip; Composer 2; Node.js 20 o superior; y PostgreSQL 14 o superior. La instalación consiste en descargar las dependencias (composer install y npm install), compilar los recursos (npm run build), configurar el archivo .env con la conexión a la base de datos, generar la clave de la aplicación y ejecutar las migraciones con los datos de prueba (php artisan migrate --seed). En producción, el archivo Dockerfile y el archivo render.yaml automatizan estos pasos.'),
  h2('Funcionalidades incluidas y excluidas del alcance de pruebas'),
  p('El alcance definitivo se establece en el Plan Maestro de Pruebas. De manera general, se incluyen en las pruebas las funciones de los cinco módulos con reglas de negocio y cálculos (Tabla 5). Se excluyen o se prueban de forma limitada los componentes que dependen de servicios de terceros o del entorno gratuito, los cuales se declaran expresamente en la Tabla 9.'),
  salto(),
];

// ---------- Plan Maestro de Pruebas ----------
const pmp = [
  h1('Plan Maestro de Pruebas'),
  h2('Identificación y control del plan'),
  ...tabla('Identificación y control del Plan Maestro de Pruebas', ['Campo', 'Descripción'], [
    ['Proyecto', 'Aplicación de pruebas de software y evaluación de la calidad'],
    ['Aplicación', 'Sistema Web para Clínica Pediátrica con Farmacia Integrada'],
    ['Versión de la aplicación', '1.0.0 (etiqueta v1.0.0, commit 0ba5928)'],
    ['URL del entorno de pruebas', URL],
    ['Fecha de elaboración', '30 de noviembre de 2026'],
    ['Versión del documento', '1.0'],
    ['Responsable del plan', 'Edilmer Eli Vasquez Narva (Scrum Master)'],
    ['Aprobación', 'José Alexis Castillo Linares (Product Owner)'],
    ['Referencia normativa', 'ISO/IEC/IEEE 29119-3:2021 (documentación de pruebas) e ISO/IEC 25010:2023 (modelo de calidad)'],
  ], [32, 68]),
  h2('Introducción y contexto'),
  p('Este plan define qué se probará, cómo se probará, con qué recursos, bajo qué condiciones y con qué criterios se valorarán los resultados del Sistema Web para Clínica Pediátrica con Farmacia Integrada. Su estructura sigue la plantilla de plan de pruebas de la norma ISO/IEC/IEEE 29119-3 (ISO, 2021) y las actividades del proceso de pruebas descritas por el ISTQB (2023): planificación, análisis, diseño, implementación, ejecución y cierre.'),
  p('El sistema maneja información sensible de menores de edad y realiza cálculos que influyen en decisiones clínicas (percentiles, dosis pediátricas) y en el control legal de medicamentos (estupefacientes y psicotrópicos). Por ello, la estrategia prioriza las pruebas de las reglas de negocio y de la seguridad, por encima de los aspectos estéticos de la interfaz.'),
  h2('Objetivos de prueba'),
  numerada('Verificar que las reglas de negocio y los cálculos de cada módulo producen resultados correctos con datos de entrada concretos (percentiles OMS, dosis pediátrica, FEFO, saldo de controlados, política de 24 horas, totales con IGV, consistencia del inventario).', 'objetivos'),
  numerada('Comprobar la respuesta de la aplicación ante entradas incorrectas, errores durante las operaciones y excepciones, incluidos los mensajes y mecanismos de recuperación.', 'objetivos'),
  numerada('Evaluar la seguridad del sistema en sus seis subcaracterísticas (confidencialidad, integridad, no repudio, responsabilidad, autenticidad y resistencia) frente a escenarios de ataque pertinentes dentro del entorno autorizado.', 'objetivos'),
  numerada('Identificar defectos mediante técnicas basadas en la experiencia: adivinación de errores, listas de verificación, pruebas de ataque, pruebas de guerrilla, sesiones exploratorias y evaluación heurística de usabilidad.', 'objetivos'),
  numerada('Medir la calidad interna del código fuente mediante análisis estático (bugs, vulnerabilidades, code smells, duplicación, deuda técnica y cobertura).', 'objetivos'),
  numerada('Registrar los defectos de forma reproducible, corregirlos y repetir las pruebas para confirmar su solución (re-prueba) y la ausencia de efectos colaterales (regresión).', 'objetivos'),
  h2('Alcance'),
  p('La Tabla 8 resume los elementos incluidos en el alcance, y la Tabla 9 los excluidos o limitados con su justificación.'),
  ...tabla('Elementos incluidos en el alcance de pruebas', ['Módulo', 'Funciones y características incluidas'], [
    ['1. Pacientes', 'Registro paciente-tutor y consentimiento; cálculo OMS y alertas; ficha clínica; hitos y PSC-35; vacunación con descuento de stock; ESAVI; reportes del módulo'],
    ['2. Clínica', 'Agenda y política de 24 h; plantillas por edad; receta con dosis, alertas e interacciones; laboratorio y valores anormales; reportes del módulo'],
    ['3. Farmacia', 'Lotes y FEFO; bloqueo de lotes no aptos; dispensación validada; libro de controlados; retiros; conciliación; errores de medicación; reportes'],
    ['4. Facturación', 'Cuenta única; emisión, pagos y anulación; roles y permisos; auditoría inmutable; reportes'],
    ['5. Portal', 'Autenticación; visibilidad familiar y control de acceso por paciente; descargas; registro de accesos; notificaciones; reportes'],
    ['Transversal', 'Exportación e importación FHIR; exportación de reportes a PDF y Excel; cabeceras de seguridad; manejo de errores'],
  ], [20, 80]),
  ...tabla('Elementos excluidos o con alcance limitado', ['Elemento', 'Tratamiento', 'Justificación'], [
    ['Envío real de SMS por operador', 'Limitado', 'En el entorno de pruebas la pasarela opera en modo simulado; se realizará una prueba controlada con un celular del equipo. Depende de un servicio externo y del plan del operador.'],
    ['Entrega real de correo (SMTP)', 'Limitado', 'El correo se registra en el log del servidor; se verifica el registro de envío y apertura, no la bandeja del destinatario.'],
    ['Videollamada de telemedicina', 'Excluido', 'Se prueba la generación del enlace; la videollamada la provee un servicio de terceros (Jitsi Meet).'],
    ['Pruebas de rendimiento y carga', 'Limitado', 'Solo sobrecarga básica controlada; el plan gratuito del hosting no es representativo de un entorno productivo.'],
    ['Integración con la historia clínica electrónica nacional, laboratorios y seguros', 'Excluido', 'No existe un entorno autorizado de terceros; se valida la estructura de los recursos FHIR exportados e importados.'],
    ['Infraestructura del proveedor (Render)', 'Excluido', 'Las pruebas de seguridad se limitan a la aplicación autorizada; no se ataca la infraestructura del proveedor.'],
  ], [25, 17, 58]),
  h2('Enfoque y estrategia'),
  p('La estrategia combina pruebas de caja blanca y de caja negra en distintos niveles, y se relaciona con los objetivos según la Tabla 10. Las técnicas de diseño de caja negra utilizadas son la partición de equivalencia, el análisis de valores límite y las tablas de decisión (ISTQB, 2023), aplicadas a las reglas con umbrales numéricos: Z = ±2, 24 horas, cantidad pendiente de la receta, saldo del libro de controlados y consistencia del 99 %.'),
  ...tabla('Niveles, tipos de prueba, técnicas y herramientas', ['Nivel / tipo', 'Técnica', 'Herramienta', 'Objetivo relacionado'], [
    ['Unitarias (componente)', 'Caja blanca y caja negra: particiones, valores límite', 'PHPUnit 12', '1, 2'],
    ['Integración', 'Pruebas de servicios con base de datos', 'PHPUnit + PostgreSQL de pruebas', '1, 2, 6'],
    ['Funcionales / aceptación (sistema)', 'Escenarios de historias de usuario, tablas de decisión', 'Codeception 5', '1, 2, 6'],
    ['Seguridad', 'Escaneo pasivo y activo, pruebas manuales de ataque', 'OWASP ZAP y navegador', '3'],
    ['Basadas en la experiencia', 'Adivinación de errores, listas de verificación, ataques, guerrilla, sesiones exploratorias, heurísticas de Nielsen', 'Fichas de sesión, navegador', '2, 4'],
    ['Análisis estático', 'Revisión automática del código', 'SonarQube (SonarCloud)', '5'],
    ['Humo y regresión', 'Verificación rápida de funciones críticas', 'Codeception / manual', '6'],
  ], [24, 34, 22, 20]),
  h3('Escenarios de seguridad por subcaracterística'),
  p('La Tabla 11 relaciona cada subcaracterística de seguridad de la norma ISO/IEC 25010 (ISO, 2023) con la amenaza a evaluar y el mecanismo de defensa esperado. Las pruebas se ejecutan únicamente sobre la aplicación y el entorno autorizados para el trabajo, y se apoyan en las categorías del OWASP Top 10 (OWASP Foundation, 2021).'),
  ...tabla('Escenarios de seguridad planificados', ['Subcaracterística', 'Amenaza o ataque', 'Comportamiento esperado'], [
    ['Confidencialidad', 'Acceso de un tutor a datos de un niño ajeno manipulando el identificador en la URL; acceso de un rol a módulos no autorizados', 'Respuesta 403 y ausencia de datos del paciente ajeno'],
    ['Integridad', 'Inyección SQL en búsquedas; XSS almacenado en campos de texto; manipulación de cantidades en la dispensación; carga de archivos maliciosos', 'Consultas parametrizadas, salida escapada y validación del lado del servidor; rechazo de archivos no permitidos'],
    ['No repudio', 'Negación de una dispensación de controlado o de una anulación de comprobante', 'Registro en la bitácora con usuario, fecha, hora, IP y hash encadenado'],
    ['Responsabilidad (accountability)', 'Modificación o eliminación de registros de auditoría', 'La base de datos rechaza UPDATE y DELETE; la verificación de la cadena detecta alteraciones'],
    ['Autenticidad', 'Fuerza bruta en el inicio de sesión; falsificación de solicitudes (CSRF); uso de sesión ajena', 'Bloqueo tras 5 intentos; rechazo de solicitudes sin token CSRF (419); regeneración de la sesión'],
    ['Resistencia', 'Sobrecarga básica de solicitudes y entradas extremas', 'El sistema limita la tasa de solicitudes donde corresponde y responde con mensajes controlados sin exponer detalles técnicos'],
  ], [20, 42, 38]),
  h2('Elementos de prueba'),
  p('Los elementos de prueba son los componentes de la versión 1.0.0 identificados a continuación.'),
  vinieta([['Servicios de negocio: ', { bold: true }], 'PercentilOmsService, VacunacionService, Psc35Service, DesarrolloService, PacienteService, CitaService, DosisPediatricaService, RecetaService, ConsultaService, LaboratorioService, InventarioService, DispensacionService, ControladosService, RetiroService, ConciliacionService, CuentaService, FacturacionService, NotificacionService, FhirService y AuditService.']),
  vinieta([['Interfaces web: ', { bold: true }], '121 rutas agrupadas en los cinco módulos, el portal de tutores y los 18 reportes.']),
  vinieta([['Base de datos: ', { bold: true }], '47 tablas, restricciones de unicidad e integridad referencial y el trigger de inmutabilidad de la auditoría.']),
  vinieta([['Integraciones: ', { bold: true }], 'pasarela de SMS, correo, enlaces de telemedicina e intercambio HL7 FHIR R4.']),
  vinieta([['Código fuente: ', { bold: true }], 'directorios app, routes, resources/views y database del repositorio, para el análisis estático.']),
  h2('Criterios de entrada y salida'),
  ...tabla('Criterios de entrada, suspensión y salida', ['Tipo', 'Criterio'], [
    ['Entrada', 'Versión 1.0.0 etiquetada y desplegada; prueba de humo aprobada; datos de prueba cargados; cuentas por rol disponibles; herramientas instaladas y configuradas; plan y casos de prueba revisados'],
    ['Suspensión', 'El entorno desplegado no está disponible por más de 30 minutos; un defecto bloqueante impide ejecutar un módulo; el escaneo de seguridad degrada el servicio'],
    ['Reanudación', 'Restablecimiento del entorno o publicación de la corrección del defecto bloqueante'],
    ['Salida', '100 % de los casos planificados ejecutados o con justificación de no ejecución; 0 defectos críticos y 0 altos abiertos; defectos corregidos re-probados; análisis estático sin vulnerabilidades críticas; resultados y evidencias documentados'],
  ], [18, 82]),
  h2('Entorno de prueba'),
  ...tabla('Entorno de prueba: infraestructura, software y herramientas', ['Elemento', 'Configuración'], [
    ['Servidor de pruebas (en línea)', `Render, plan gratuito; contenedor Docker PHP 8.3 + Apache 2.4; HTTPS; ${URL}`],
    ['Base de datos (en línea)', 'PostgreSQL 16 gestionado por Render, datos ficticios generados por seeders'],
    ['Entorno local de pruebas automatizadas', 'Ubuntu 24.04; PHP 8.3.6; PostgreSQL 16.15 con base de datos exclusiva para pruebas; Node.js 22'],
    ['Aplicación', 'Laravel 13.44.1; versión 1.0.0'],
    ['Navegadores', 'Google Chrome / Chromium y Mozilla Firefox (versiones vigentes a diciembre de 2026)'],
    ['Herramientas', 'PHPUnit 12.5; Codeception 5; SonarQube (SonarCloud); OWASP ZAP 2.16; Git y GitHub'],
    ['Cuentas y roles', 'Siete roles con cuentas de prueba (Tabla 6)'],
    ['Datos de prueba', '20 pacientes y sus tutores, 76 citas, 47 consultas, 30 recetas, inventario de 34 productos con lotes vigentes, próximos a vencer, vencidos y en cuarentena'],
    ['Red', 'Acceso público por HTTPS; las pruebas de seguridad se dirigen solo al dominio autorizado'],
  ], [28, 72], 'La preparación del entorno, la verificación de conexiones y la prueba de humo se documentan en la sección correspondiente.'),
  h2('Riesgos y contingencias'),
  ...tabla('Riesgos del proceso de pruebas', ['Riesgo', 'Impacto', 'Contingencia'], [
    ['El servidor gratuito se suspende por inactividad (demora cercana a 50 s)', 'Medio', 'Realizar una solicitud previa para activarlo y registrar el tiempo de arranque como condición del entorno'],
    ['La base de datos gratuita vence a los 30 días', 'Bajo', 'Las pruebas concluyen antes del vencimiento; el entorno local reproduce los mismos datos'],
    ['El escaneo activo de OWASP ZAP satura el servidor o altera los datos', 'Alto', 'Ejecutar el escaneo activo en el entorno local idéntico y solo un escaneo controlado en línea; respaldar con los seeders'],
    ['El bloqueo por intentos fallidos interfiere con otras pruebas', 'Bajo', 'Usar un correo distinto para la prueba de fuerza bruta y esperar el tiempo de bloqueo'],
    ['Plazo reducido y disponibilidad del equipo', 'Alto', 'Priorizar los casos por riesgo (reglas de negocio y seguridad) y dividir el trabajo por herramienta'],
    ['Pérdida de archivos subidos al reiniciar el servidor', 'Bajo', 'Documentar la limitación; las evidencias se guardan fuera del servidor'],
  ], [40, 12, 48]),
  h2('Roles y responsabilidades'),
  ...tabla('Roles y responsabilidades en las pruebas', ['Integrante', 'Rol en las pruebas', 'Actividades asignadas'], [
    ['Edilmer Eli Vasquez Narva', 'Líder de pruebas', 'Plan Maestro, preparación del entorno, prueba de humo, gestión de defectos, consolidación del informe'],
    ['José Alexis Castillo Linares', 'Analista de pruebas de experiencia', 'Sesiones exploratorias, listas de verificación, pruebas de guerrilla y evaluación heurística de usabilidad'],
    ['Abrahan Orlando Hernández Contreras', 'Ingeniero de pruebas unitarias', 'Diseño y ejecución de pruebas con PHPUnit y reporte de cobertura'],
    ['Jeferson Vela Chappa', 'Ingeniero de pruebas funcionales', 'Diseño y ejecución de pruebas de aceptación con Codeception'],
    ['Billy Mark Núñez Sánchez', 'Analista de seguridad y calidad de código', 'Escenarios de seguridad, OWASP ZAP y análisis estático con SonarQube'],
  ], [32, 24, 44]),
  h2('Cronograma'),
  ...tabla('Cronograma de actividades de prueba', ['Actividad', 'Responsable', 'Periodo'], [
    ['Elaboración del Plan Maestro de Pruebas', 'Edilmer Vasquez', '30/11/2026'],
    ['Diseño de escenarios y casos de prueba', 'Todo el equipo', '30/11/2026'],
    ['Preparación del entorno, datos y prueba de humo', 'Edilmer Vasquez', '01/12/2026'],
    ['Ejecución de pruebas unitarias e integración (PHPUnit)', 'Abrahan Hernández', '01/12/2026'],
    ['Ejecución de pruebas funcionales (Codeception)', 'Jeferson Vela', '01/12/2026'],
    ['Análisis estático (SonarQube)', 'Billy Núñez', '01/12/2026'],
    ['Pruebas de seguridad (OWASP ZAP y manuales)', 'Billy Núñez', '01/12/2026'],
    ['Pruebas basadas en experiencia y sesiones exploratorias', 'José Castillo', '01/12/2026'],
    ['Registro de defectos, corrección (versión 1.1.0) y re-pruebas', 'Todo el equipo', '02/12/2026'],
    ['Consolidación del informe final', 'Edilmer Vasquez', '02/12/2026'],
  ], [52, 26, 22]),
  h2('Entregables'),
  vinieta('Plan Maestro de Pruebas (este documento).'),
  vinieta('Escenarios y casos de prueba funcionales, de seguridad y basados en experiencia.'),
  vinieta('Registros de ejecución con estado aprobado, fallido o no ejecutado.'),
  vinieta('Registro de defectos con severidad, pasos de reproducción, corrección y re-prueba.'),
  vinieta('Evidencias (capturas de pantalla y reportes de herramientas).'),
  vinieta('Resultados del análisis estático y del escaneo de seguridad.'),
  vinieta('Configuraciones y scripts de PHPUnit y Codeception.'),
  vinieta('Informe de resultados (este informe académico).'),
  salto(),
];

const pendientes = [
  h1('Diseño de pruebas'),
  pendiente('escenarios y casos de prueba funcionales, de seguridad y basados en experiencia, relacionados con los módulos, con precondiciones, datos de entrada, pasos, resultado esperado y evidencia.'),
  h1('Preparación y configuración del entorno de pruebas'),
  pendiente('hardware, software y versiones, cuentas y permisos, preparación de datos de prueba, verificación de conexiones y prueba de humo.'),
  h1('Ejecución de pruebas y resultados'),
  pendiente('resultados de PHPUnit, Codeception, OWASP ZAP, SonarQube y pruebas basadas en experiencia; registro de defectos y re-pruebas.'),
  h1('Conclusiones'),
  pendiente('conclusiones por cada tipo de prueba realizada.'),
  h1('Recomendaciones'),
  pendiente('recomendaciones derivadas de las pruebas aplicadas.'),
  salto(),
];

const referencias = [
  h1('Referencias'),
  ref(['Gobierno del Perú. (2011). ', ['Ley N.° 29733, Ley de Protección de Datos Personales', { italics: true }], '. Diario Oficial El Peruano.']),
  ref(['HL7 International. (2019). ', ['FHIR Release 4 (R4)', { italics: true }], '. https://hl7.org/fhir/R4/']),
  ref(['International Organization for Standardization. (2021). ', ['Software and systems engineering — Software testing — Part 3: Test documentation', { italics: true }], ' (ISO/IEC/IEEE Standard No. 29119-3:2021).']),
  ref(['International Organization for Standardization. (2023). ', ['Systems and software engineering — Systems and software Quality Requirements and Evaluation (SQuaRE) — Product quality model', { italics: true }], ' (ISO/IEC Standard No. 25010:2023).']),
  ref(['International Software Testing Qualifications Board. (2023). ', ['Certified Tester Foundation Level syllabus', { italics: true }], ' (Versión 4.0). https://www.istqb.org']),
  ref(['Jellinek, M. S., Murphy, J. M., Robinson, J., Feins, A., Lamb, S., & Fenton, T. (1988). Pediatric Symptom Checklist: Screening school-age children for psychosocial dysfunction. ', ['The Journal of Pediatrics, 112', { italics: true }], '(2), 201–209. https://doi.org/10.1016/S0022-3476(88)80056-8']),
  ref(['Laravel. (2026). ', ['Laravel documentation', { italics: true }], ' (Versión 13.x). https://laravel.com/docs']),
  ref(['Mosteller, R. D. (1987). Simplified calculation of body-surface area. ', ['The New England Journal of Medicine, 317', { italics: true }], '(17), 1098. https://doi.org/10.1056/NEJM198710223171717']),
  ref(['Nielsen, J. (1994). ', ['Usability engineering', { italics: true }], '. Morgan Kaufmann.']),
  ref(['Organización Mundial de la Salud. (2006). ', ['WHO child growth standards: Length/height-for-age, weight-for-age, weight-for-length, weight-for-height and body mass index-for-age: Methods and development', { italics: true }], '. https://www.who.int/tools/child-growth-standards']),
  ref(['OWASP Foundation. (2021). ', ['OWASP Top 10: 2021', { italics: true }], '. https://owasp.org/Top10/']),
  ref(['Pressman, R. S., & Maxim, B. R. (2020). ', ['Software engineering: A practitioner’s approach', { italics: true }], ' (9.ª ed.). McGraw-Hill Education.']),
  ref(['Schwaber, K., & Sutherland, J. (2020). ', ['La Guía Scrum: La guía definitiva de Scrum, las reglas del juego', { italics: true }], '. https://scrumguides.org']),
  ref(['Sommerville, I. (2016). ', ['Software engineering', { italics: true }], ' (10.ª ed.). Pearson.']),
  salto(),
];

const anexos = [
  h1('Anexos'),
  p('Los anexos se incorporarán al concluir la ejecución de las pruebas, según la estructura solicitada en la guía del trabajo.', { sinSangria: false }),
  vinieta('Anexo A. Plan Maestro de Pruebas (versión independiente).'),
  vinieta('Anexo B. Matrices de prueba.'),
  vinieta('Anexo C. Escenarios y casos de prueba.'),
  vinieta('Anexo D. Fichas de sesión exploratoria.'),
  vinieta('Anexo E. Evidencias de ejecución (capturas de pantalla).'),
  vinieta('Anexo F. Resultados del análisis estático y de las herramientas utilizadas.'),
  vinieta('Anexo G. Configuraciones y scripts de PHPUnit y Codeception.'),
  h2('Anexo E (avance). Pantallas adicionales de la versión evaluada'),
  ...figura('Panel principal del administrador', '02_panel.png', 'Indicadores generales de la clínica. Datos ficticios.'),
  ...figura('Receta electrónica con alerta de dosis justificada', '07_receta_alerta.png', 'La alerta y su justificación quedan registradas en la receta.'),
  ...figura('Agenda de citas presenciales y por telemedicina', '08_citas.png', null),
  ...figura('Catálogo de reportes por módulo', '13_reportes.png', 'Cada reporte se visualiza en pantalla, se imprime y se exporta a PDF y Excel.'),
  ...figura('Reporte de cobertura de vacunación con meta, aplicadas y brecha', '14_reporte_cobertura.png', null),
  ...figura('Bitácora de auditoría inmutable con cadena de hashes', '15_auditoria.png', null),
  ...figura('Módulo de interoperabilidad HL7 FHIR R4', '16_fhir.png', null),
];

// ---------- documento ----------
const doc = new Document({
  creator: 'Equipo del curso Prueba y Calidad de Software',
  title: 'Informe del trabajo final - Prueba y Calidad de Software',
  description: 'Sistema Web para Clínica Pediátrica con Farmacia Integrada',
  features: { updateFields: true },
  styles: {
    default: { document: { run: { font: FUENTE, size: 24 }, paragraph: { spacing: { line: 480, lineRule: LineRuleType.AUTO } } } },
    paragraphStyles: [
      { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true, run: { font: FUENTE, size: 24, bold: true, color: '000000' }, paragraph: { spacing: { line: 480, before: 0, after: 0 }, alignment: AlignmentType.CENTER, keepNext: true, outlineLevel: 0 } },
      { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true, run: { font: FUENTE, size: 24, bold: true, color: '000000' }, paragraph: { spacing: { line: 480, before: 0, after: 0 }, keepNext: true, outlineLevel: 1 } },
      { id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true, run: { font: FUENTE, size: 24, bold: true, italics: true, color: '000000' }, paragraph: { spacing: { line: 480, before: 0, after: 0 }, keepNext: true, outlineLevel: 2 } },
    ],
  },
  numbering: {
    config: [
      { reference: 'vinetas', levels: [{ level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 720, hanging: 360 } } } }] },
      { reference: 'numeros', levels: [{ level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 720, hanging: 360 } } } }] },
      { reference: 'objetivos', levels: [{ level: 0, format: LevelFormat.DECIMAL, text: 'OBJ-%1.', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 1080, hanging: 1080 } } } }] },
    ],
  },
  sections: [{
    properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 1440, right: 1440, bottom: 1440, left: 1440, header: 720 } } },
    headers: { default: new Header({ children: [new Paragraph({ alignment: AlignmentType.RIGHT, children: [new TextRun({ children: [PageNumber.CURRENT], font: FUENTE, size: 24 })] })] }) },
    children: [...caratula, ...indices, ...resumen, ...introduccion, ...diagnostico, ...pmp, ...pendientes, ...referencias, ...anexos],
  }],
});

Packer.toBuffer(doc).then(b => {
  const salida = process.argv[2] || path.join(__dirname, 'Informe_Final_Prueba_Calidad_Software.docx');
  fs.writeFileSync(salida, b);
  console.log('Generado', salida, b.length, 'bytes');
});
