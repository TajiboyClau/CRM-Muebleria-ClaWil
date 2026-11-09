# Plan de trabajo del CRM Mueblería ClaWil

## Arquitectura

| Pieza | Tecnología | Responsabilidad |
|---|---|---|
| Panel | Laravel 13, Blade, Breeze y Eloquent | Esquema, cuentas, catálogo, clientes, equipos y reportes |
| API | Node.js 24, Express 5, `pg`, JWT y `bcryptjs` | Operaciones del vendedor en JSON; SQL parametrizado |
| SPA | HTML, Bootstrap 5.3 y módulos JavaScript | Sesión, cartera, embudo, actividades y resumen |
| Datos | PostgreSQL 16 | Base compartida creada por migraciones de Laravel |

## Fases, tareas y responsable

Los responsables indican quién preparó y revisó principalmente cada bloque;
ambos integrantes deben poder explicarlo en la defensa.

| Fase | Tareas | Responsable principal | Revisión conjunta | Estado |
|---|---|---|---|---|
| 1. Especificación | Sector, regla de muebles a medida, roles, contrato y criterios Dado/Cuando/Entonces | Claudia | Wilberth | Completa |
| 2. Esquema y semillas | Migraciones, restricciones, `VerificacionSeeder` y `SectorSeeder` | Claudia | Wilberth | Completa |
| 3. Panel | Breeze, roles, cuentas, catálogo, clientes, equipos y reportes | Wilberth | Claudia | Completa |
| 4. API | Login JWT, autorización, cartera, oportunidades, actividades y resumen | Wilberth | Claudia | Completa |
| 5. SPA | Login, localStorage de sesión, cartera, oportunidades, actividades y reportes | Claudia | Wilberth | Completa |
| 6. Integración | PostgreSQL compartido, CORS, fechas UTC, precios guardados y errores | Equipo | Equipo | Completa |
| 7. Verificación | Pruebas Laravel, chequeos Node/SPA, Postman, seguridad, cifras y revisión móvil | Equipo | Equipo | Completa |

## Solicitudes al agente durante el desarrollo

En cada fase se pidió al agente que respetara el contrato, utilizara
migraciones para el esquema, no expusiera secretos, validara en el servidor,
mantuviera SQL parametrizado en la API y agregara pruebas o evidencia para
cada funcionalidad. Las decisiones se revisaron antes de integrarse a las
ramas.

## Flujo de ramas y entrega

- `main`: versión integrada para entregar.
- `clau`: trabajo de Claudia.
- `wil`: trabajo de Wilberth.
- Los cambios se incorporan mediante commits descriptivos y revisión antes
  de actualizar `main`.
- La etiqueta `entrega-final` debe apuntar al último commit que pase la
  verificación completa. La etiqueta anterior se actualizará solo después de
  ejecutar las pruebas finales.
- `spec-v1` apunta al commit inicial de especificación (`909e41a`), antes de
  los commits de implementación, como exige el proceso guiado por
  especificaciones.

## Verificación de cada fase

1. Instalar dependencias sin subir `vendor/` ni `node_modules/`.
2. Levantar PostgreSQL y ejecutar `php artisan migrate:fresh --seed`.
3. Ejecutar `php artisan test` en el panel.
4. Ejecutar `npm run check` y levantar la API.
5. Ejecutar la colección de Postman con la semilla de verificación.
6. Abrir la SPA con un servidor estático, comprobar CORS y probar las tres
   puertas con los mismos datos.
7. Registrar resultados, defectos encontrados y correcciones en
   `docs/verificacion.md`.
