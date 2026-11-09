# CRM Mueblería ClaWil

CRM de ventas para una tienda de muebles. El sistema comparte una base
PostgreSQL entre un panel Laravel para administración/supervisión, una API
Express para vendedores y una SPA HTML/Bootstrap/JavaScript.

## Equipo

- Claudia Estefanía Zorin Tajiboy - 202408031.
- Wilberth Daniel Velasquez Flores - 202408061.

El equipo utiliza OpenCode con ChatGPT como apoyo. Ambos integrantes revisan,
prueban y comprenden el código entregado.

## Estructura

- `laravel/`: panel Blade, autenticación, migraciones, seeders y reportes.
- `api/`: servicio REST con JWT, `pg` y SQL parametrizado.
- `frontend/`: SPA del vendedor sin framework.
- `docs/`: especificación, plan, verificación y colección de Postman.

## Requisitos

- PHP 8.4 y Composer.
- Node.js 24 LTS y npm.
- PostgreSQL 16.
- Docker Desktop es opcional, pero se usa para ejecutar PostgreSQL y las
  pruebas del panel de forma reproducible.

## Configuración local

1. Cree una base `crm_muebleria` y un usuario con permisos para Laravel. No
   suba archivos `.env`.
2. En `laravel/`, copie `.env.example` a `.env`, configure PostgreSQL, genere
   la clave y prepare dependencias:

   ```text
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan migrate:fresh --seed
   ```

3. En otra terminal, desde `api/`:

   ```text
   npm ci
   copy .env.example .env
   npm run check
   npm start
   ```

   Configure `JWT_SECRET` con al menos 32 caracteres y `SPA_ORIGIN` con el
   origen exacto desde donde se servirá la SPA.

4. Sirva `frontend/` con un servidor estático. Si usa VS Code Live Server,
   configure el puerto/origen para que coincida con `SPA_ORIGIN`.

   ```text
   npm run check
   ```

5. Abra el panel en `http://localhost:8000`, la API en
   `http://localhost:3000/health` y el portal del vendedor en el origen de la
   SPA.

## Semillas

Para cargar la semilla común del enunciado:

```text
cd laravel
php artisan migrate:fresh --seeder=VerificacionSeeder
```

Para cargar la semilla común y los datos de muebles:

```text
php artisan migrate:fresh --seed
```

La contraseña pública de demostración es `Ventas2026`. Úsela únicamente en
el entorno local de la semilla.

## Pruebas y verificación

```text
cd laravel
php artisan test

cd ../api
npm run check

cd ../frontend
npm run check
```

Importe `docs/api.postman_collection.json` para comprobar el contrato completo de
la API. Las cifras esperadas de octubre de 2026 y la matriz de aceptación se
encuentran en `docs/verificacion.md`.

## Git y entrega

El trabajo se organiza en `clau`, `wil` y `main`. Cada cambio debe tener un
commit descriptivo y `main` debe contener la versión integrada. Antes de
entregar, ejecute las pruebas, actualice la etiqueta `entrega-final` al commit
verificado y entregue el enlace del repositorio junto con el identificador de
esa etiqueta.
