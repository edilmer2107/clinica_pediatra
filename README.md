# Clínica Pediátrica con Farmacia Integrada

Sistema web para la gestión de una clínica pediátrica con farmacia integrada, desarrollado como
trabajo final del curso **Prueba y Calidad de Software** — Universidad Nacional Toribio Rodríguez de Mendoza de Amazonas.

**Versión evaluada:** 1.0.0

## Tecnologías

| Componente | Tecnología |
|---|---|
| Lenguaje | PHP 8.3 |
| Framework | Laravel 13 |
| Base de datos | PostgreSQL 16 |
| Interfaz | Blade, Tailwind CSS 4, Alpine.js, Chart.js (Vite) |
| Roles y permisos | spatie/laravel-permission |
| PDF / Excel | barryvdh/laravel-dompdf, phpoffice/phpspreadsheet |
| Despliegue | Docker (PHP 8.3 + Apache) en Render |

## Módulos

1. **Gestión de Pacientes Pediátricos** — tutores y consentimiento (Ley 29733), percentiles OMS, ficha clínica, hitos del desarrollo, PSC-35, vacunación con descuento de stock, ESAVI.
2. **Gestión Clínica y Consultas** — citas presenciales y telemedicina (política de 24 h), historia clínica por edad, receta con dosis pediátrica e interacciones, laboratorio e imágenes.
3. **Farmacia e Inventario** — lotes con FEFO, dispensación validada, libro de controlados, retiros de mercado, conciliación de inventario, errores de medicación.
4. **Facturación y Administración** — cuenta única por paciente, pagos, anulaciones, roles y permisos, bitácora de auditoría inmutable.
5. **Comunicación y Portal de Tutores** — portal familiar, gráficas de crecimiento, descargas en PDF, notificaciones por correo y SMS.

Transversal: exportación/importación **HL7 FHIR R4** y 18 reportes con vista en pantalla, impresión, PDF y Excel.

## Instalación local

Requisitos: PHP 8.3 (pdo_pgsql, gd, intl, zip), Composer, Node 20+, PostgreSQL 14+.

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# configurar DB_* en .env
php artisan migrate --seed
php artisan serve
```

## Usuarios de prueba (datos ficticios)

Contraseña para todos: `Clinica2026!`

| Rol | Correo |
|---|---|
| Administrador | admin@clinica.test |
| Médico pediatra | medico@clinica.test / medico2@clinica.test |
| Enfermería | enfermeria@clinica.test |
| Recepción | recepcion@clinica.test |
| Farmacéutico | farmacia@clinica.test |
| Facturación | facturacion@clinica.test |
| Tutor (portal) | tutor@clinica.test |

> Todos los pacientes, tutores y datos clínicos son **ficticios** y se generan con los seeders.

## Pasarela SMS

Por defecto los SMS se simulan (`SMS_DRIVER=log`). Para enviarlos desde un celular Android con la app
*SMS Gateway for Android* (modo nube): `SMS_DRIVER=android`, `SMS_GATEWAY_USER` y `SMS_GATEWAY_PASSWORD`.

## Documentación

Ver la carpeta [`docs/`](docs/): product backlog, arquitectura y modelo de datos.
