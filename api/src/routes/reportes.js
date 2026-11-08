const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);

router.get('/resumen-mensual', async (req, res, next) => {
  try {
    const { rows: summary } = await db.query(
      `SELECT COUNT(*) AS total_oportunidades,
              COUNT(*) FILTER (WHERE etapa = 'ganada') AS ganadas,
              COUNT(*) FILTER (WHERE etapa = 'perdida') AS perdidas,
              COUNT(*) FILTER (WHERE etapa NOT IN ('ganada', 'perdida')) AS abiertas
       FROM oportunidades
       WHERE vendedor_id = $1 AND created_at >= date_trunc('month', CURRENT_DATE)
         AND created_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'`,
      [req.user.id],
    );
    const { rows: activities } = await db.query(
      `SELECT COUNT(*) AS total_actividades,
              COUNT(*) FILTER (WHERE completada = TRUE) AS completadas
       FROM actividades
       WHERE vendedor_id = $1 AND created_at >= date_trunc('month', CURRENT_DATE)
         AND created_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'`,
      [req.user.id],
    );
    res.json({ periodo: new Date().toISOString().slice(0, 7), oportunidades: summary[0], actividades: activities[0] });
  } catch (error) { next(error); }
});

module.exports = router;
