# Verificación del CRM Mueblería ClaWil

## Pruebas del panel Laravel

Desde `laravel/` se ejecuta:

```text
php artisan test
```

La suite cubre autenticación, relaciones del CRM, permisos por rol, catálogo,
clientes, oportunidades, actividades, reportes, administración de usuarios y
la regla de medición para muebles a medida.

El supervisor solo ve oportunidades de sus vendedores. Puede asignar clientes
disponibles o de su equipo a vendedores de su equipo y liberar sus clientes.
Si intenta asignar o liberar un cliente de otro equipo, el panel devuelve 404.

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
La cartera admite hasta 10 clientes por vendedor. El panel valida altas y asignaciones, y la API devuelve 409 al intentar superar el límite. Editar un cliente sin cambiar su vendedor no consume otro cupo.
La SPA separa la cartera de los clientes disponibles. La lista de disponibles expone solo id y nombre. Si dos vendedores toman el mismo cliente a la vez, la API asigna el cliente a uno y devuelve 409 al otro.
En la SPA, las oportunidades nacen en prospecto. Se avanza una etapa a la vez y se permite perder desde propuesta o negociación. El cierre guarda fecha e historial en la misma transacción. Ganar exige al menos un producto y conserva la regla de medición.

El resumen mensual usa la hora de Guatemala y los precios guardados en las
líneas. Con VerificacionSeeder, Valeria obtiene 2 ventas por Q9,250.00 y un
pronóstico de Q2,512.88 en octubre de 2026.

La API recibe correo y clave. Configure JWT_SECRET con al menos 32 caracteres
aleatorios y SPA_ORIGIN con el origen exacto del portal. El panel deja las
oportunidades en consulta y las cuentas se administran mediante edición y
desactivación. Panel y API normalizan el NIT y el teléfono antes de guardar.
