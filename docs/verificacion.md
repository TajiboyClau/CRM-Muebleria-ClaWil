# Verificación del CRM Mueblería ClaWil

## Pruebas del panel Laravel

Desde `laravel/` se ejecuta:

```text
php artisan test
```

La suite cubre autenticación, relaciones del CRM, permisos por rol, catálogo,
clientes, oportunidades, actividades, reportes, administración de usuarios y
la regla de medición para muebles a medida.

## Pruebas de la API

Desde `api/` se ejecutan:

```text
npm run check
npm start
```

Con la API levantada se comprueban `/health`, el login de vendedor, la cartera
de clientes y `/api/reportes/resumen-mensual`. Las consultas utilizan `pg` con
parámetros y la API no contiene migraciones.

## Pruebas de la SPA

La carpeta `frontend/` contiene una SPA estática con módulos JavaScript para:

- Inicio de sesión con JWT.
- Cartera de clientes.
- Creación de clientes.
- Embudo y cambios de etapa.
- Actividades y marcado de completadas.
- Resumen mensual.

El panel administrativo también incluye reportes por etapa y administración de
cuentas, mientras que el supervisor consulta indicadores de su equipo.

## Regla propia validada

Una oportunidad con título que contiene `a medida` no puede pasar a `ganada`
sin una actividad `reunion` completada cuya descripción contenga `medicion` o
`medición`. La regla se prueba en Laravel y se aplica también en la API.

## Datos de demostración

Los seeders crean usuarios, productos, clientes, oportunidades, actividades e
historial de etapas conforme al enunciado. Las contraseñas de demostración solo
se utilizan en el entorno local de pruebas y no se guardan en archivos `.env`.
