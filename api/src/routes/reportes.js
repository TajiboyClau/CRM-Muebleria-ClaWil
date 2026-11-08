const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);

router.get(['/resumen', '/resumen-mensual'], async (req, res, next) => {
  const mes = req.query.mes ?? new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'America/Guatemala', year: 'numeric', month: '2-digit',
  }).format(new Date());
  if (typeof mes !== 'string' || !/^(?!0000)\d{4}-(0[1-9]|1[0-2])$/.test(mes)) {
    return res.status(400).json({ error: 'El mes debe tener formato AAAA-MM.' });
  }

  try {
    const { rows: [summary] } = await db.query(
      `WITH montos AS (
         SELECT o.etapa, o.fecha_cierre, COALESCE(SUM(l.cantidad * l.precio_unitario), 0) AS monto
         FROM oportunidades o LEFT JOIN oportunidad_producto l ON l.oportunidad_id = o.id
         WHERE o.vendedor_id = $1 GROUP BY o.id
       ), ventas_mes AS (
         SELECT monto FROM montos WHERE etapa = 'ganada'
           AND fecha_cierre >= ($2 || '-01')::timestamp + INTERVAL '6 hours'
           AND fecha_cierre < ($2 || '-01')::timestamp + INTERVAL '1 month 6 hours'
       )
       SELECT (SELECT COUNT(*)::int FROM ventas_mes) AS ventas,
              (SELECT COALESCE(SUM(monto), 0)::float8 FROM ventas_mes) AS total,
              (SELECT ROUND(COALESCE(SUM(monto * CASE etapa
                 WHEN 'prospecto' THEN 0.10 WHEN 'calificado' THEN 0.25
                 WHEN 'propuesta' THEN 0.50 WHEN 'negociacion' THEN 0.75 ELSE 0 END), 0), 2)::float8 FROM montos) AS pronostico,
              (SELECT COUNT(*)::int FROM clientes WHERE vendedor_id = $1) AS clientes`,
      [req.user.id, mes],
    );
    const { clientes, ...totals } = summary;
    res.json({ mes, ...totals, cartera: { clientes, maximo: 10 } });
  } catch (error) { next(error); }
});

module.exports = router;
