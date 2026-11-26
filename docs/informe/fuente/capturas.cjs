const { chromium } = require('playwright');
const R = 'https://clinica-pediatrica-wr1u.onrender.com', L = 'http://127.0.0.1:8000';
const out = __dirname + '/img/';
async function sesion(b, base, email) {
  const ctx = await b.newContext({ viewport: { width: 1366, height: 768 }, deviceScaleFactor: 2, locale: 'es-PE' });
  const p = await ctx.newPage();
  await p.goto(base + '/login'); await p.fill('#email', email); await p.fill('#password', 'Clinica2026!');
  await p.click('button[type=submit]'); await p.waitForLoadState('networkidle');
  return p;
}
async function shot(p, base, url, nombre, opts = {}) {
  await p.goto(base + url); await p.waitForLoadState('networkidle'); await p.waitForTimeout(900);
  if (opts.scroll) await p.evaluate(s => document.querySelector(s)?.scrollIntoView(), opts.scroll);
  await p.screenshot({ path: out + nombre + '.png', fullPage: !!opts.full });
  console.log('ok', nombre);
}
(async () => {
  const b = await chromium.launch();
  // Login (sin sesión)
  const c0 = await b.newContext({ viewport: { width: 1366, height: 768 }, deviceScaleFactor: 2 }); const p0 = await c0.newPage();
  await p0.goto(R + '/login'); await p0.waitForLoadState('networkidle'); await p0.screenshot({ path: out + '01_login.png' }); console.log('ok login');
  const a = await sesion(b, R, 'admin@clinica.test');
  await shot(a, R, '/panel', '02_panel');
  await shot(a, R, '/reportes', '13_reportes');
  await shot(a, R, '/reportes/cobertura-vacunacion', '14_reporte_cobertura', { full: false });
  await shot(a, R, '/auditoria', '15_auditoria');
  await shot(a, R, '/fhir', '16_fhir');
  const m = await sesion(b, R, 'medico@clinica.test');
  await shot(m, R, '/pacientes/4', '03_paciente_resumen');
  await shot(m, R, '/pacientes/4?tab=crecimiento', '04_crecimiento', { full: true });
  await shot(m, R, '/pacientes/5?tab=vacunas', '05_vacunas');
  await shot(m, R, '/recetas/25', '07_receta_alerta');
  await shot(m, R, '/citas?fecha=' + new Date(Date.now() + 86400000).toISOString().slice(0, 10), '08_citas');
  const f = await sesion(b, R, 'farmacia@clinica.test');
  await shot(f, R, '/farmacia/medicamentos/1', '09_fefo');
  await shot(f, R, '/farmacia/controlados?medication_id=16', '10_controlados');
  const fa = await sesion(b, R, 'facturacion@clinica.test');
  await shot(fa, R, '/cuentas/5', '11_cuenta_unica');
  const t = await sesion(b, R, 'tutor@clinica.test');
  await shot(t, R, '/portal', '12_portal');
  // Cálculo de dosis (entorno local, misma versión 1.0.0): consulta abierta nueva
  const ml = await sesion(b, L, 'medico@clinica.test');
  await ml.goto(L + '/consultas/nueva?patient_id=14'); await ml.fill('#motivo_consulta', 'Otalgia y fiebre');
  for (const x of await ml.locator('textarea[name^="interrogatorio"]').all()) await x.fill('Sin alteraciones referidas');
  await ml.selectOption('select[name="diagnosticos[0]"]', { label: 'H66.9 - Otitis media, no especificada' });
  await ml.click('button:has-text("Guardar consulta")'); await ml.waitForLoadState('networkidle');
  await ml.fill('#r_peso', '16'); await ml.fill('#r_talla', '102');
  const s0 = ml.locator('select[name="items[0][medication_id]"]');
  await s0.selectOption({ label: await s0.locator('option', { hasText: 'Amoxicilina 250' }).first().innerText() });
  await ml.fill('input[name="items[0][dosis_indicada]"]', '100'); await ml.selectOption('select[name="items[0][frecuencia_horas]"]', '12'); await ml.fill('input[name="items[0][duracion_dias]"]', '10');
  await ml.click('button:has-text("Calcular dosis y validar")'); await ml.waitForTimeout(1200);
  const form = ml.locator('div.card:has(h3:has-text("Nueva receta"))');
  await form.screenshot({ path: out + '06_dosis.png' }); console.log('ok dosis');
  await b.close();
})();
