const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);

router.get('/', async (req, res, next) => {
  try {
    const { rows } = await db.query(
      `SELECT a.id, a.cliente_id, c.nombre AS cliente, a.tipo, a.descripcion, a.vence_en, a.completada
       FROM actividades a JOIN clientes c ON c.id = a.cliente_id
       WHERE a.vendedor_id = $1 ORDER BY a.completada, a.vence_en`,
      [req.user.id],
    );
    res.json({ datos: rows });
  } catch (error) { next(error); }
});

router.post('/', async (req, res, next) => {
  try {
    const { cliente_id, tipo, descripcion, vence_en = null } = req.body || {};
    if (!cliente_id || !['llamada', 'reunion', 'tarea'].includes(tipo) || !descripcion) return res.status(422).json({ mensaje: 'Cliente, tipo y descripción son obligatorios.' });
    const { rows: clients } = await db.query('SELECT id FROM clientes WHERE id = $1 AND vendedor_id = $2', [cliente_id, req.user.id]);
    if (!clients[0]) return res.status(403).json({ mensaje: 'El cliente no pertenece a tu cartera.' });
    const { rows: inserted } = await db.query('INSERT INTO actividades (cliente_id, vendedor_id, tipo, descripcion, vence_en, completada, created_at, updated_at) VALUES ($1, $2, $3, $4, $5, FALSE, NOW(), NOW()) RETURNING id', [cliente_id, req.user.id, tipo, descripcion, vence_en]);
    res.status(201).json({ id: inserted[0].id, mensaje: 'Actividad creada.' });
  } catch (error) { next(error); }
});

router.patch('/:id/completar', async (req, res, next) => {
  try {
    const { rows } = await db.query('UPDATE actividades SET completada = $1, updated_at = NOW() WHERE id = $2 AND vendedor_id = $3 RETURNING id', [Boolean(req.body?.completada), req.params.id, req.user.id]);
    if (!rows[0]) return res.status(404).json({ mensaje: 'Actividad no encontrada.' });
    res.json({ mensaje: 'Actividad actualizada.' });
  } catch (error) { next(error); }
});

module.exports = router;
