const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);

router.get('/', async (req, res, next) => {
  try {
    const [rows] = await db.execute(
      `SELECT a.id, a.cliente_id, c.nombre AS cliente, a.tipo, a.descripcion, a.vence_en, a.completada
       FROM actividades a JOIN clientes c ON c.id = a.cliente_id
       WHERE a.vendedor_id = ? ORDER BY a.completada, a.vence_en`,
      [req.user.id],
    );
    res.json({ datos: rows });
  } catch (error) { next(error); }
});

router.post('/', async (req, res, next) => {
  try {
    const { cliente_id, tipo, descripcion, vence_en = null } = req.body || {};
    if (!cliente_id || !['llamada', 'reunion', 'tarea'].includes(tipo) || !descripcion) return res.status(422).json({ mensaje: 'Cliente, tipo y descripción son obligatorios.' });
    const [clients] = await db.execute('SELECT id FROM clientes WHERE id = ? AND vendedor_id = ?', [cliente_id, req.user.id]);
    if (!clients[0]) return res.status(403).json({ mensaje: 'El cliente no pertenece a tu cartera.' });
    const [result] = await db.execute('INSERT INTO actividades (cliente_id, vendedor_id, tipo, descripcion, vence_en, completada, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())', [cliente_id, req.user.id, tipo, descripcion, vence_en]);
    res.status(201).json({ id: result.insertId, mensaje: 'Actividad creada.' });
  } catch (error) { next(error); }
});

router.patch('/:id/completar', async (req, res, next) => {
  try {
    const [result] = await db.execute('UPDATE actividades SET completada = ?, updated_at = NOW() WHERE id = ? AND vendedor_id = ?', [req.body?.completada ? 1 : 0, req.params.id, req.user.id]);
    if (!result.affectedRows) return res.status(404).json({ mensaje: 'Actividad no encontrada.' });
    res.json({ mensaje: 'Actividad actualizada.' });
  } catch (error) { next(error); }
});

module.exports = router;
