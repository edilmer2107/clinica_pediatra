# Arquitectura del sistema

## Estilo: monolito modular por capas (MVC + Services)

```
Navegador (Blade + Tailwind + Alpine.js + Chart.js)
        │  HTTPS
        ▼
Rutas (routes/modulos/*.php) ── middleware: auth, permission:*, cabeceras de seguridad, CSRF, throttle
        ▼
Controladores (app/Http/Controllers/<Módulo>) ── validación (Form Requests / validate)
        ▼
Servicios de negocio (app/Services/<Módulo>) ── reglas: percentiles OMS, dosis, FEFO, 24 h, facturación
        ▼
Modelos Eloquent (app/Models) ── relaciones, casts
        ▼
PostgreSQL 16 ── restricciones, triggers de inmutabilidad (auditoría)
```

**Justificación**
- Un solo despliegue y una sola base de datos: viable en hosting gratuito y en el plazo del curso.
- La lógica de negocio vive en *Services* sin dependencia de HTTP: se prueba de forma aislada con PHPUnit.
- Los controladores son delgados y los flujos completos se prueban con Codeception.
- Organización por módulos (carpetas de controladores, servicios, vistas, rutas y reportes por módulo).

## Componentes transversales

| Componente | Ubicación | Propósito |
|---|---|---|
| Auditoría inmutable | `app/Services/AuditService.php`, migración `audit_logs` | Registro con cadena de hashes SHA-256; trigger que impide UPDATE/DELETE |
| Roles y permisos | `database/seeders/RolesPermisosSeeder.php` | 7 roles, 33 permisos granulares |
| Reportes | `app/Reportes/*`, `ExportadorReportes` | Contrato común: pantalla, impresión, PDF y Excel con filtros |
| Notificaciones | `app/Services/Portal/*` | Correo y SMS (pasarela Android o simulada) con seguimiento de apertura |
| Interoperabilidad | `app/Services/Interoperabilidad/FhirService.php` | HL7 FHIR R4 (LOINC, CVX, CIE-10) |
| Reglas de negocio | `app/Exceptions/ReglaNegocioException.php` | Errores de negocio mostrados al usuario sin exponer detalles técnicos |

## Seguridad incorporada

- Contraseñas con hash bcrypt, regeneración de sesión al iniciar sesión, bloqueo tras 5 intentos fallidos (5 min).
- Protección CSRF en todos los formularios, consultas parametrizadas (Eloquent), escape de salida en Blade.
- Autorización por permisos en rutas y verificación de pertenencia en el portal (anti-IDOR).
- Subida de archivos restringida (PDF/JPG/PNG, tipo MIME real, 5 MB) y almacenados fuera de `public/`.
- Cabeceras: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Exportación a Excel protegida contra inyección de fórmulas.

## Metodología: Scrum (sprints cortos)

| Sprint | Fechas | Objetivo |
|---|---|---|
| Sprint 0 | 24–25/11/2026 | Backlog, arquitectura, modelo de datos, proyecto base |
| Sprint 1 | 26/11/2026 | Autenticación, roles, auditoría, layout |
| Sprint 2 | 27/11/2026 | Módulo 3: Farmacia e Inventario |
| Sprint 3 | 28/11/2026 | Módulo 1: Pacientes Pediátricos |
| Sprint 4 | 29/11/2026 | Módulo 2: Clínica y Consultas |
| Sprint 5 | 30/11/2026 | Módulos 4 y 5, FHIR, despliegue v1.0.0 |
| Sprint 6 | 01/12/2026 | Pruebas (PHPUnit, Codeception, SonarQube, OWASP ZAP, experiencia) |
| Cierre | 02/12/2026 | Corrección de defectos (v1.1.0), re-pruebas e informe |
