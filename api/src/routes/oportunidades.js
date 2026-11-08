const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const STAGES = ['prospecto', 'calificado', 'propuesta', 'negociacion', 'ganada', 'perdida'];
const router = express.Router();
router.use(requireVendor);

async function canWinCustomOpportunity(clienteId, titulo) {
  if (!String(titulo).toLowerCase().includes('a medida')) return true;
  const [rows] = await db.execute(
    `SELECT a.id FROM actividades a
     WHERE a.cliente_id = ? AND a.tipo = 'reunion' AND a.completada = 1
     AND (LOWER(a.descripcion) LIKE '%medicion%' OR LOWER(a.descripcion) LIKE '%medición%') LIMIT 1`,
    [clienteId],
  );
  return Boolean(rows[0]);
}

router.get('/', async (req, res, next) => {
  try {
    const [rows] = await db.execute(
      `SELECT o.id, o.cliente_id, c.nombre AS cliente, o.titulo, o.etapa, o.motivo_perdida,
              o.fecha_cierre, o.created_at, o.updated_at
       FROM oportunidades o JOIN clientes c ON c.id = o.cliente_id
       WHERE o.vendedor_id = ? ORDER BY o.updated_at DESC`,
      [req.user.id],
    );
    res.json({ datos: rows });
  } catch (error) { next(error); }
});

router.post('/', async (req, res, next) => {
  try {
    const { cliente_id, titulo, etapa = 'prospecto', motivo_perdida = null, fecha_cierre = null } = req.body || {};
    if (!cliente_id || !titulo || !STAGES.includes(etapa)) return res.status(422).json({ mensaje: 'Cliente, título y etapa válida son obligatorios.' });
    const [clients] = await db.execute('SELECT id FROM clientes WHERE id = ? AND (vendedor_id = ? OR vendedor_id IS NULL)', [cliente_id, req.user.id]);
    if (!clients[0]) return res.status(403).json({ mensaje: 'El cliente no pertenece a tu cartera.' });
    if (etapa === 'perdida' && !motivo_perdida) return res.status(422).json({ mensaje: 'Una oportunidad perdida necesita motivo.' });
    if (['ganada', 'perdida'].includes(etapa) && !fecha_cierre) return res.status(422).json({ mensaje: 'Una oportunidad cerrada necesita fecha de cierre.' });
    if (etapa === 'ganada' && !(await canWinCustomOpportunity(cliente_id, titulo))) return res.status(422).json({ mensaje: 'Una oportunidad a medida necesita una reunión de medición completada.' });

    const connection = await db.getConnection();
    try {
      await connection.beginTransaction();
      const [result] = await connection.execute(
        `INSERT INTO oportunidades (cliente_id, vendedor_id, titulo, etapa, motivo_perdida, fecha_cierre, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())`,
        [cliente_id, req.user.id, titulo, etapa, etapa === 'perdida' ? motivo_perdida : null, ['ganada', 'perdida'].includes(etapa) ? fecha_cierre : null],
      );
      await connection.execute('INSERT INTO historial_etapas (oportunidad_id, usuario_id, etapa_anterior, etapa_nueva, created_at) VALUES (?, ?, NULL, ?, NOW())', [result.insertId, req.user.id, etapa]);
      await connection.commit();
      res.status(201).json({ id: result.insertId, mensaje: 'Oportunidad creada.' });
    } catch (error) { await connection.rollback(); throw error; } finally { connection.release(); }
  } catch (error) { next(error); }
});

router.patch('/:id/etapa', async (req, res, next) => {
  try {
    const { etapa, motivo_perdida = null, fecha_cierre = null } = req.body || {};
    if (!STAGES.includes(etapa)) return res.status(422).json({ mensaje: 'Etapa inválida.' });
    const [rows] = await db.execute('SELECT * FROM oportunidades WHERE id = ? AND vendedor_id = ?', [req.params.id, req.user.id]);
    const opportunity = rows[0];
    if (!opportunity) return res.status(404).json({ mensaje: 'Oportunidad no encontrada.' });
    if (etapa === 'perdida' && !motivo_perdida) return res.status(422).json({ mensaje: 'Una oportunidad perdida necesita motivo.' });
    if (['ganada', 'perdida'].includes(etapa) && !fecha_cierre) return res.status(422).json({ mensaje: 'Una oportunidad cerrada necesita fecha de cierre.' });
    if (etapa === 'ganada' && !(await canWinCustomOpportunity(opportunity.cliente_id, opportunity.titulo))) return res.status(422).json({ mensaje: 'Una oportunidad a medida necesita una reunión de medición completada.' });
    await db.execute('UPDATE oportunidades SET etapa = ?, motivo_perdida = ?, fecha_cierre = ?, updated_at = NOW() WHERE id = ?', [etapa, etapa === 'perdida' ? motivo_perdida : null, ['ganada', 'perdida'].includes(etapa) ? fecha_cierre : null, opportunity.id]);
    await db.execute('INSERT INTO historial_etapas (oportunidad_id, usuario_id, etapa_anterior, etapa_nueva, created_at) VALUES (?, ?, ?, ?, NOW())', [opportunity.id, req.user.id, opportunity.etapa, etapa]);
    res.json({ mensaje: 'Etapa actualizada.' });
  } catch (error) { next(error); }
});

module.exports = router;
