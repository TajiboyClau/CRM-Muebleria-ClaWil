const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);

router.get('/resumen-mensual', async (req, res, next) => {
  try {
    const [summary] = await db.execute(
      `SELECT COUNT(*) AS total_oportunidades,
              SUM(etapa = 'ganada') AS ganadas,
              SUM(etapa = 'perdida') AS perdidas,
              SUM(etapa NOT IN ('ganada', 'perdida')) AS abiertas
       FROM oportunidades WHERE vendedor_id = ? AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND MONTH(created_at) = MONTH(CURRENT_DATE())`,
      [req.user.id],
    );
    const [activities] = await db.execute(
      `SELECT COUNT(*) AS total_actividades, SUM(completada = 1) AS completadas
       FROM actividades WHERE vendedor_id = ? AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND MONTH(created_at) = MONTH(CURRENT_DATE())`,
      [req.user.id],
    );
    res.json({ periodo: new Date().toISOString().slice(0, 7), oportunidades: summary[0], actividades: activities[0] });
  } catch (error) { next(error); }
});

module.exports = router;
