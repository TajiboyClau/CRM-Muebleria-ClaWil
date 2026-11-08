# Plan de trabajo

## Fases

| Fase | Actividades | Responsable inicial | Estado |
|---|---|---|---|
| Especificación | Sector, roles, regla propia y criterios de aceptación | Ambos | En progreso |
| Laravel | Migraciones, seeders, autenticación y panel | Integrante 1 | Pendiente |
| API | JWT, middleware, clientes, oportunidades y reportes | Integrante 2 | Pendiente |
| SPA | Login, cartera, embudo, actividades y resumen mensual | Integrante 2 | Pendiente |
| Integración | Conectar panel, API, SPA y MySQL | Ambos | Pendiente |
| Verificación | Pruebas, seguridad, Postman y documentación | Ambos | Pendiente |

## División por ramas

### Rama `clau`: panel y base de datos

- Crear y configurar el proyecto Laravel.
- Crear las migraciones del esquema común.
- Crear `VerificationSeeder` y `SectorSeeder`.
- Implementar autenticación con Breeze y roles.
- Implementar el panel del administrador y supervisor.
- Implementar catálogo, clientes, equipos, oportunidades y reportes del panel.

### Rama `wil`: API y SPA

- Crear y configurar la API Express.
- Implementar login JWT y middleware de autenticación.
- Implementar clientes, cartera, oportunidades, productos y actividades.
- Implementar reportes de la API.
- Crear la SPA del vendedor con HTML, Bootstrap y JavaScript modular.
- Crear y mantener la colección de Postman.

### Rama `main`: integración

- Recibir cambios probados de `clau` y `wil`.
- Verificar que Laravel, API, SPA y MySQL funcionen juntos.
- Ejecutar las pruebas de seguridad y reglas de negocio.
- Preparar la etiqueta final `entrega-final`.

La API y la SPA utilizarán el esquema definido por las migraciones de Laravel.
Por eso primero se acuerdan el contrato y las tablas, aunque ambas áreas puedan
avanzar en paralelo.

## Integrantes

- Claudia Estefanía Zorin Tajiboy - 202408031.
- Wilberth Daniel Velasquez Flores - 202408061.

## Herramienta de apoyo

Se utilizará OpenCode con ChatGPT como apoyo de inteligencia artificial. Todo
resultado será revisado, probado y comprendido por ambos integrantes.

## Acuerdos de trabajo

- Cada integrante trabajará en una rama para cambios grandes.
- Los commits deben describir un cambio concreto.
- Se revisará la integración antes de mezclar cambios.
- Ambos integrantes probarán y explicarán las tres piezas del sistema.

## Primeros entregables

1. Revisar y aprobar `docs/especificacion.md`.
2. Completar los nombres del equipo.
3. Confirmar la regla propia.
4. Crear el entorno Laravel, API y MySQL.

