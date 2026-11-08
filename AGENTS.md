# Instrucciones del proyecto

## Contexto

Este repositorio contiene el proyecto final de Programación Web: un CRM para
la tienda de muebles ClaWil.

El equipo utiliza OpenCode con ChatGPT como apoyo durante el desarrollo. La IA
es una herramienta de asistencia; las decisiones, revisiones, pruebas y
responsabilidad del código pertenecen al equipo.

## Reglas principales

- Respetar el contrato de la API definido por el enunciado.
- Las tablas se crean y modifican únicamente mediante migraciones de Laravel.
- La API no debe ejecutar `CREATE` ni `ALTER`.
- No guardar secretos ni archivos `.env` en el repositorio.
- Validar las reglas de negocio en el servidor.
- Toda funcionalidad nueva debe documentarse y probarse.
- Cada integrante debe comprender el código que se entregue.

## Estructura

- `laravel/`: panel administrativo.
- `api/`: servicio REST.
- `frontend/`: SPA del vendedor.
- `docs/`: especificación, plan, verificación y colección de Postman.

