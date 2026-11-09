# Especificación del CRM Mueblería ClaWil

## 1. Alcance y sector

ClaWil es una tienda de muebles que vende productos terminados y muebles
fabricados a medida. El CRM centraliza cuentas, catálogo, clientes,
oportunidades, actividades y reportes sobre una sola base de datos.

La solución tiene tres piezas:

- `laravel/`: panel Blade para administración y supervisión.
- `api/`: API REST Express para vendedores, autenticada con JWT.
- `frontend/`: SPA sin framework, con HTML, Bootstrap y módulos JavaScript.

El enunciado original propone MySQL y `mysql2`; por decisión del equipo, la
implementación usa PostgreSQL y `pg`. Se conserva el mismo contrato, modelo,
semántica de fechas, restricciones y reglas de negocio. Laravel continúa
siendo el único dueño del esquema.

## 2. Roles y permisos

| Rol | Puerta | Puede hacer | No puede hacer |
|---|---|---|---|
| Administrador | Panel | Administrar cuentas, catálogo, clientes, oportunidades y reportes de toda la empresa | Entrar a la API; borrar cuentas |
| Supervisor | Panel | Ver su equipo, clientes disponibles, cartera del equipo, oportunidades y reportes del equipo; crear, editar, asignar, reasignar o liberar clientes de su equipo | Ver otros equipos; administrar cuentas o catálogo; entrar a la API |
| Vendedor | SPA/API | Trabajar su cartera, oportunidades, productos, actividades y resumen mensual | Entrar al panel o consultar datos de otro vendedor |

Las cuentas y productos se desactivan, no se borran. Un usuario desactivado
deja de autenticarse y una sesión existente pierde acceso en la siguiente
petición.

## 3. Regla propia de muebles a medida

Una oportunidad cuyo título contiene `a medida`, ignorando mayúsculas, solo
puede pasar a `ganada` si existe una actividad del cliente que cumpla todas
estas condiciones:

- tipo `reunion`;
- `completada = true`;
- descripción que contiene `medicion` o `medición`.

### Criterios de aceptación

| ID | Dado | Cuando | Entonces |
|---|---|---|---|
| S-MED-01 | Una oportunidad normal con producto | Se solicita ganarla | Se acepta si cumple las reglas generales |
| S-MED-02 | Una oportunidad con título “Cocina a medida” sin reunión de medición | Se solicita ganarla | El servidor responde 422 y no cambia la etapa |
| S-MED-03 | Existe una reunión de medición pendiente | Se solicita ganarla | El servidor responde 422 |
| S-MED-04 | Existe una reunión de medición completada | Se solicita ganarla | Se permite el cierre si hay al menos un producto |
| S-MED-05 | Se intenta la misma operación desde una puerta distinta | Se procesa la petición | La regla produce el mismo resultado en API y panel |

La regla se valida en el servidor y se prueba con Laravel; la API también la
aplica en `PATCH /api/oportunidades/:id/etapa`.

## 4. Modelo de datos y decisiones

Todas las tablas son creadas por migraciones de Laravel. Las fechas se guardan
en UTC. La API no ejecuta `CREATE` ni `ALTER` y no usa ORM.

| Tabla | Campos principales | Reglas implementadas |
|---|---|---|
| `users` | `name`, `email`, `password`, `rol`, `supervisor_id`, `activo` | Rol válido; vendedor con supervisor; cuenta activa; contraseña con hash de Laravel |
| `productos` | `nombre`, `precio`, `activo` | Nombre único; precio decimal mayor que cero; desactivación en vez de borrado |
| `clientes` | `vendedor_id`, `nombre`, `nit`, `telefono`, `correo` | NIT y teléfono únicos; consumidor final como `NULL`; teléfono normalizado y protegido por `CHECK` en PostgreSQL (`^[2-9][0-9]{7}$`) |
| `oportunidades` | `cliente_id`, `vendedor_id`, `titulo`, `etapa`, `motivo_perdida`, `fecha_cierre` | Etapas válidas; motivo solo al perder; fecha solo al cerrar |
| `oportunidad_producto` | `oportunidad_id`, `producto_id`, `cantidad`, `precio_unitario` | Producto único por oportunidad; cantidad 1-999; precio decimal copiado del catálogo |
| `historial_etapas` | `oportunidad_id`, `usuario_id`, `etapa_anterior`, `etapa_nueva`, `created_at` | Registro de creación y de cada cambio dentro de la misma transacción |
| `actividades` | `cliente_id`, `vendedor_id`, `tipo`, `descripcion`, `vence_en`, `completada` | Tipo válido; descripción de hasta 500; fechas ISO con zona y salida UTC |

La cartera máxima de un vendedor es de 10 clientes. La API y el panel bloquean
la fila del vendedor antes de contar y asignar; tomar un cliente bloquea
primero la fila del cliente. Así se conserva la regla ante peticiones
simultáneas.

El NIT se compacta, se convierte a mayúsculas, se quitan ceros iniciales y se
valida con módulo 11. `CF` se guarda como `NULL` y se devuelve como `CF`. El
teléfono elimina separadores y el prefijo guatemalteco `+502` o `502`, y se
guarda como ocho dígitos.

La migración `2026_11_09_000008_add_client_phone_check` refuerza en la base de
datos que el teléfono normalizado tenga ocho dígitos y empiece entre 2 y 9;
la API y el panel mantienen además la validación y los mensajes de usuario.

Las oportunidades nacen en `prospecto`; se avanza una etapa abierta a la vez.
Se puede perder desde `propuesta` o `negociacion`, ganar desde `negociacion`,
y una oportunidad cerrada no vuelve a cambiar. Ganar exige un producto y
perder exige un motivo.

Los reportes calculan en SQL: las ventas se agrupan por el mes de Guatemala
(UTC-6) de `fecha_cierre`, y el pronóstico suma total por probabilidad
(10%, 25%, 50% y 75%) sin filtrar oportunidades abiertas por mes. Los precios
se toman de las líneas guardadas, no del catálogo actual.

## 5. Contrato de la API

Base local: `http://localhost:3000/api`. Todas las rutas, excepto el login,
requieren `Authorization: Bearer <token>`. Las respuestas son JSON.

| Método | Ruta | Cuerpo/parámetros | Éxito |
|---|---|---|---|
| POST | `/auth/login` | `{ correo, clave }` | 200 `{ token, usuario }` |
| GET | `/productos` | - | 200 productos activos |
| GET | `/clientes?q=` | búsqueda opcional | 200 cartera del vendedor |
| POST | `/clientes` | `nombre, nit, telefono, correo` | 201 cliente asignado |
| GET | `/clientes/disponibles` | - | 200 solo `{ id, nombre }` |
| POST | `/clientes/:id/tomar` | - | 200 cliente tomado |
| GET | `/clientes/:id` | - | 200 detalle de cliente |
| PATCH | `/clientes/:id` | campos modificados | 200 cliente actualizado |
| POST | `/clientes/:id/actividades` | `tipo, descripcion, vence_en` | 201 actividad |
| GET | `/actividades?estado=` | `pendiente` o `completada` | 200 actividades de la cartera |
| PATCH | `/actividades/:id` | `{ completada }` | 200 actividad |
| GET | `/oportunidades?etapa=` | filtro opcional | 200 resúmenes propios |
| POST | `/oportunidades` | `{ cliente_id, titulo }` | 201 detalle en prospecto |
| GET | `/oportunidades/:id` | - | 200 detalle con productos e historial |
| PATCH | `/oportunidades/:id` | `{ titulo }` | 200 detalle actualizado |
| PATCH | `/oportunidades/:id/etapa` | `{ etapa, motivo_perdida }` | 200 detalle cerrado o avanzado |
| POST | `/oportunidades/:id/productos` | `{ producto_id, cantidad }` | 201 detalle |
| PATCH | `/oportunidades/:id/productos/:lineaId` | `{ cantidad }` | 200 detalle |
| DELETE | `/oportunidades/:id/productos/:lineaId` | - | 204 sin cuerpo |
| GET | `/reportes/resumen?mes=AAAA-MM` | mes opcional | 200 resumen numérico |

La API usa estos estados: 200 correcto, 201 creado, 204 sin cuerpo, 400
petición inválida, 401 credenciales/token inválidos, 403 rol no permitido,
404 recurso ajeno o inexistente, 409 conflicto de estado o unicidad, 422
validación y 500 error inesperado genérico.

Los errores de validación tienen `{ "errores": { "campo": "mensaje" } }`;
los demás tienen `{ "error": "mensaje" }`. La API nunca devuelve hashes,
llaves internas de propietario ni registros de otro vendedor.

## 6. Criterios verificables por pieza

| Código | Criterio de aceptación | Evidencia |
|---|---|---|
| L1 | Migraciones, restricciones y seeders reproducibles | `laravel/database/migrations`, `VerificacionSeeder`, `SectorSeeder` |
| L2 | Panel con roles, sesión, cuentas y desactivación | `routes/web.php`, middleware, pruebas de acceso |
| L3 | Catálogo con precio decimal y desactivación | `ProductoController`, FormRequests y vistas |
| L4 | Clientes, normalización, búsqueda, filtros, asignación y borrado seguro | `ClienteController`, FormRequests y pruebas |
| L5 | Visibilidad del supervisor y cartera del equipo | consultas filtradas y resumen de equipo |
| L6 | Oportunidades de consulta y reportes por mes, equipo y producto | `ReporteController` y vistas |
| L7 | Validación del servidor con mensajes en español | FormRequests, reglas de API y pruebas |
| A1-A7 | JWT, propiedad, cartera, embudo, actividades, reportes y errores | rutas Express, `pg` parametrizado y colección Postman |
| A8 | Colección con rutas principales y errores | `docs/api.postman_collection.json` |
| F1-F6 | SPA, sesión, cartera, embudo, actividades, resumen y responsive | `frontend/index.html`, `frontend/js`, `styles.css` |
| S1-S8 | Hashes, secretos, SQL parametrizado, privacidad, CORS, XSS y concurrencia | `.gitignore`, `.env.example`, revisión y pruebas |

## 7. Decisiones y alternativas descartadas

- Se eligió PostgreSQL con `pg` porque es la base solicitada por el equipo;
  se descartó mezclar una base para Laravel y otra para la API.
- Laravel usa Eloquent y la API usa SQL parametrizado; se descartaron ORM y
  query builders en la API para conservar la separación exigida.
- La SPA guarda únicamente token y usuario de sesión en `localStorage`; se
  descartó persistir clientes, oportunidades o credenciales.
- Las oportunidades se trabajan en la SPA; el panel las consulta en modo
  solo lectura para evitar dos lógicas de edición distintas.
- Los registros importantes no se eliminan: cuentas y productos se
  desactivan; clientes solo se eliminan si no tienen oportunidades.

## 8. Datos de verificación y sector

`VerificacionSeeder` conserva las cuentas, productos, clientes, oportunidades,
actividades, historial y cifras del enunciado. `SectorSeeder` agrega datos
creíbles de muebles y una oportunidad a medida para demostrar la regla propia.
La contraseña pública de prueba es `Ventas2026` y solo se usa en semillas,
README, Postman y pruebas locales.
