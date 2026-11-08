# CRM Mueblería ClaWil

Sistema CRM de ventas para una tienda de muebles.

## Objetivo

Centralizar clientes, oportunidades de venta, actividades, productos y reportes
para que administradores, supervisores y vendedores trabajen con la misma
información.

## Arquitectura prevista

- `laravel/`: panel web para administrador y supervisor.
- `api/`: API REST en Express para vendedores.
- `frontend/`: SPA del vendedor con HTML, Bootstrap y JavaScript modular.
- PostgreSQL 16: base de datos compartida por las tres piezas.

## Estado del proyecto

- [x] Repositorio creado.
- [x] Especificación inicial.
- [x] Plan de trabajo inicial.
- [x] Panel Laravel con autenticación, catálogo, clientes, oportunidades y actividades.
- [x] API REST con JWT, cartera, oportunidades, actividades y resumen mensual.
- [x] SPA del vendedor con Bootstrap y JavaScript modular.
- [x] Pruebas automatizadas, colección de Postman y verificación técnica.

La documentación del proyecto se encuentra en `docs/`.

## Ejecución local

1. Preparar PostgreSQL 16 y configurar las variables de entorno de Laravel y la API.
2. En `laravel/`, instalar dependencias, ejecutar migraciones y seeders, y levantar el panel.
3. En `api/`, copiar `.env.example` a `.env`, instalar dependencias y ejecutar `npm start`.
4. Abrir `frontend/index.html` con un servidor estático y usar las credenciales de un vendedor creado por los seeders.

La API consulta el esquema creado por Laravel mediante `pg`; no contiene
sentencias `CREATE` ni `ALTER`.

## Equipo

- Claudia Estefanía Zorin Tajiboy - 202408031.
- Wilberth Daniel Velasquez Flores - 202408061.

## Herramienta de apoyo

El desarrollo utilizará OpenCode con ChatGPT como apoyo de inteligencia
artificial. El equipo revisará, probará y podrá explicar todo el código que se
entregue.

