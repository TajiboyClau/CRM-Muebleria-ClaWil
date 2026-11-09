# Verificación del CRM Mueblería ClaWil

## Estado de esta revisión

La revisión separa comprobaciones ejecutadas de comprobaciones manuales.
La suite Laravel, la API contra PostgreSQL, el panel HTTP, la concurrencia y la
revisión visual de la SPA fueron ejecutados.

| Comprobación | Resultado actual | Evidencia |
|---|---|---|
| Sintaxis API Node | APROBADA | `cd api; npm run check` |
| Sintaxis SPA | APROBADA | `cd frontend; npm run check` |
| Colección Postman válida | APROBADA | JSON v2.1 con rutas del contrato |
| Suite Laravel | APROBADA: 42 pruebas, 135 aserciones | `docker run ... artisan test` |
| Migraciones en PostgreSQL | APROBADA: las 11 migraciones aparecen como `Ran`, incluida la restricción `CHECK` del teléfono | `php artisan migrate:status` y consulta de `pg_constraint` |
| Datos compartidos | APROBADA: 9 usuarios, 10 productos, 19 clientes, 13 oportunidades, 43 historiales y 5 actividades | Consulta de conteos en PostgreSQL |
| VerificacionSeeder aislado | APROBADA: 7 usuarios, 5 productos, 21 clientes, 11 oportunidades, 43 historiales y 4 actividades | PostgreSQL temporal + migración nueva + `migrate:fresh --seeder=VerificacionSeeder` |
| Login, propiedad y cartera en API | APROBADA: 200, 401, 403 y 404 comprobados | Peticiones locales autenticadas |
| Cifras de control | APROBADA para Valeria: 2, Q9,250.00 y Q2,512.88 | `GET /api/reportes/resumen?mes=2026-10` |
| Cierre y regla sectorial | APROBADA: cierre normal; 422 sin medición; 200 con medición | Flujo API temporal y limpieza posterior |
| Pruebas simultáneas | APROBADA: una toma 200 y la otra 409 | Dos vendedores tomaron el mismo cliente temporal |

## Comandos reproducibles

### Panel y base

Desde `laravel/`, con PostgreSQL configurado en `.env`:

```text
php artisan migrate:fresh --seed
php artisan test
```

Para la semilla exacta del enunciado:

```text
php artisan migrate:fresh --seeder=VerificacionSeeder
```

### API

Desde `api/`:

```text
npm ci
npm run check
npm start
```

Después se importa `docs/api.postman_collection.json` y se ejecutan primero
`Login vendedor correcto` y luego las operaciones protegidas. La API usa
PostgreSQL, SQL parametrizado y no contiene migraciones.

### SPA

Desde `frontend/`:

```text
npm ci
npm run check
```

Abra `frontend/index.html` con Live Server o con cualquier servidor estático
que escuche en el origen configurado.

El portal debe abrirse desde el origen configurado en `SPA_ORIGIN`, por
ejemplo `http://127.0.0.1:5500`.

## Matriz de aceptación

| Requisito | Prueba/evidencia | Resultado |
|---|---|---|
| L1 Esquema y semillas | Migraciones; dos seeders; estado PostgreSQL y conteos | Aprobado |
| L2 Cuentas y roles | `PanelAccessTest`, `AuthenticationTest`, `ReportAndUserManagementTest` | 42 pruebas aprobadas |
| L3 Catálogo | `ProductoManagementTest`; desactivación y precio positivo | 42 pruebas aprobadas |
| L4 Clientes | `ClienteManagementTest`; normalización, búsqueda, filtros, cupo y borrado seguro | 42 pruebas aprobadas |
| L5 Equipos | `ClienteController`; filtros por supervisor y cartera del equipo | 42 pruebas aprobadas + revisión HTTP |
| L6 Oportunidades/reportes | `OportunidadManagementTest`, `ReportAndUserManagementTest` y cifras de semilla | 42 pruebas + API aprobadas |
| L7 Validación | FormRequests y reglas de API | Aprobado por suite y peticiones API |
| A1-A2 Sesión | JWT HS256, `bcryptjs`, usuario activo leído en cada petición | Aprobado por API local |
| A3 Cartera | Cliente disponible, máximo 10 y bloqueo transaccional | Aprobado; concurrencia 200/409 |
| A4 Oportunidades | Líneas con precio copiado, historial y cierre transaccional | Aprobado por flujo API |
| A5 Actividades | Filtro, propiedad, ISO 8601 y vencimiento | Aprobado por código y API |
| A6 Resumen | Octubre 2026 y conversión Guatemala UTC-6 | Aprobado: cifras de Valeria |
| A7 Errores | 400/401/403/404/409/422/500 JSON | Rutas, middleware, validadores y colección revisados; casos de autenticación, propiedad, conflicto y regla sectorial ejecutados |
| A8 Documentación | Colección con cada endpoint y errores | `docs/api.postman_collection.json` |
| F1-F6 SPA | Sesión, cartera, búsqueda, embudo por columnas, historial, actividades, resumen, XSS y responsive | Sintaxis y revisión visual móvil aprobadas |
| S1-S8 Seguridad | Secretos ignorados, SQL parametrizado, CORS exacto y propiedad | Revisión de código y concurrencia aprobadas |
| P1-P5 Proceso | Especificación, plan, matriz, AGENTS y bitácora | Documentos presentes |

## Cifras de control esperadas

Con `VerificacionSeeder` únicamente, usando octubre de 2026:

| Alcance | Ventas | Total vendido | Pronóstico | Cartera |
|---|---:|---:|---:|---:|
| Valeria López | 2 | Q9,250.00 | Q2,512.88 | 3 de 10 |
| Diego Castillo | 1 | Q6,000.00 | Q112.50 | 2 de 10 |
| Carlos Ajú | 1 | Q7,200.00 | Q4,125.75 | 9 de 10 |
| Equipo de Sergio Ruiz | 3 | Q15,250.00 | Q2,625.38 | - |
| Equipo de Sandra Pérez | 1 | Q7,200.00 | Q4,125.75 | - |
| Toda la empresa | 4 | Q22,450.00 | Q6,751.13 | - |

La respuesta de Valeria debe contener números JSON, no cadenas:

```json
{
  "mes": "2026-10",
  "ventas": 2,
  "total": 9250,
  "pronostico": 2512.88,
  "cartera": { "clientes": 3, "maximo": 10 }
}
```

La prueba aislada con `VerificacionSeeder` produjo exactamente las cifras de
Valeria para octubre y noviembre; el reporte de octubre fue 2 ventas,
Q9,250.00 y Q2,512.88, y el de noviembre fue 1 venta, Q3,600.00 y
Q2,512.88.

## Seguridad revisada

- `.env` está ignorado en `laravel/` y `api/`; se entrega `.env.example` sin
  credenciales reales.
- Laravel crea los hashes; la API solo los compara con `bcryptjs`.
- Las consultas de la API usan parámetros de PostgreSQL; los nombres de
  columnas dinámicos se limitan a una lista interna.
- El middleware vuelve a consultar al usuario y rechaza cuentas inactivas o
  que ya no sean vendedores.
- Las rutas filtran por `vendedor_id` y devuelven 404 para recursos ajenos.
- CORS usa el origen exacto de `SPA_ORIGIN` y nunca `*`.
- La SPA usa `textContent` o `escapeHtml` para datos recibidos de la API y
  regresa al login cuando una petición autenticada responde 401.
- Las operaciones de tomar cliente, cupo y cambio de etapa usan transacciones
  y bloqueos.
- PostgreSQL rechaza directamente teléfonos no normalizados mediante la
  restricción `chk_clientes_telefono`; la prueba con `12345678` fue rechazada.

## Bitácora de IA y correcciones

| Caso | Propuesta o defecto detectado | Cómo lo detectó el equipo | Corrección |
|---|---|---|---|
| 1 | El cambio de etapa de la API tenía `WHERE id =` sin el parámetro de la oportunidad | Revisión manual de la consulta antes de probar el cierre | Se agregó `WHERE id = $4` en `oportunidades.js` |
| 2 | La documentación inicial decía que la verificación estaba completa, pero no distinguía pruebas ejecutadas de pendientes | Comparación con la sección P3 del enunciado | Se rehizo `docs/verificacion.md` con estados y evidencia |
| 3 | El panel solo permitía crear y editar clientes al administrador, aunque el enunciado también permite esa tarea al supervisor dentro de su equipo | Matriz de roles L4/L5 contra rutas y FormRequests | Se habilitó el flujo para supervisores con filtrado de visibilidad y vendedores del equipo |
| 4 | La sesión de la SPA permanecía visible después de recibir 401 | Revisión del criterio F1 | Se limpia la sesión y se muestra nuevamente el login mediante el evento `crm:unauthorized` |
| 5 | El contador de cartera usaba el número de resultados después de una búsqueda y podía mostrar `1 de 10` aunque la cartera tuviera 3 clientes | Prueba visual de búsqueda en la SPA | El indicador usa `summary.cartera.clientes` y conserva el cupo real |

Los cambios anteriores deben conservarse en commits identificables del
historial antes de crear la etiqueta final.

## Cierre de la verificación visual

Se abrió la SPA con un viewport móvil, se inició sesión con la semilla, se
probó la búsqueda de cartera, el tablero de oportunidades, el resumen y el
formulario de actividades. El contenido se reorganizó en columnas responsivas
y no apareció desplazamiento horizontal. Las comprobaciones de código, datos,
API, panel, seguridad, concurrencia y presentación fueron ejecutadas.
