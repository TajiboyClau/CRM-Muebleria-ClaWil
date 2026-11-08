# Especificación del CRM para Mueblería ClaWil

## 1. Propósito

El sistema permitirá administrar clientes, productos, oportunidades de venta,
actividades y reportes de una tienda de muebles desde dos puertas de acceso:
un panel para administración y supervisión, y una SPA para vendedores.

## 2. Sector

El sector seleccionado es una tienda de muebles. El catálogo puede incluir
salas, comedores, camas, roperos, escritorios, sillas y muebles fabricados a
medida.

## 3. Roles

- **Administrador:** administra cuentas, catálogo, clientes, oportunidades y
  reportes de toda la empresa.
- **Supervisor:** consulta su equipo, clientes disponibles y reportes de su
  equipo; asigna y reasigna clientes.
- **Vendedor:** administra su cartera, oportunidades y actividades desde la
  SPA usando la API.

## 4. Regla propia del sector

Una oportunidad cuyo título contenga la expresión **"a medida"** no puede
pasar a la etapa `ganada` si no tiene una actividad completada de tipo
`reunion` cuya descripción contenga `medicion` o `medición`.

### Criterios de aceptación

1. Una oportunidad normal puede ganarse cumpliendo las reglas generales del
   CRM.
2. Una oportunidad "a medida" sin reunión de medición debe ser rechazada con
   un error de validación.
3. Una reunión de medición pendiente no es suficiente.
4. Una reunión completada permite avanzar a `ganada`.
5. La regla se cumple tanto en el panel como en la API cuando corresponda.

## 5. Equipo y apoyo de IA

- Claudia Estefanía Zorin Tajiboy - 202408031.
- Wilberth Daniel Velasquez Flores - 202408061.
- Herramienta de apoyo: OpenCode con ChatGPT.

El equipo revisará, probará y comprenderá el código generado o sugerido con
ayuda de inteligencia artificial. Cada integrante podrá explicar las
decisiones y modificaciones realizadas.


