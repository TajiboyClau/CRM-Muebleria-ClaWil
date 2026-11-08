const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);

router.get('/', async (req, res, next) => {
  try {
    const { rows } = await db.query(
      `SELECT id, nombre, nit, telefono, correo, vendedor_id, created_at, updated_at
       FROM clientes WHERE vendedor_id = $1 OR vendedor_id IS NULL ORDER BY nombre`,
      [req.user.id],
    );
    res.json({ datos: rows });
  } catch (error) { next(error); }
});

router.post('/', async (req, res, next) => {
  try {
    const { nombre, nit = null, telefono, correo = null } = req.body || {};
    if (!nombre || !/^\d{8}$/.test(String(telefono || ''))) {
      return res.status(422).json({ mensaje: 'Nombre y teléfono de 8 dígitos son obligatorios.' });
    }
    const connection = await db.connect();
    try {
      await connection.query('BEGIN');
      // Laravel locks the same seller row before assigning a client.
      await connection.query('SELECT id FROM users WHERE id = $1 FOR UPDATE', [req.user.id]);
      const { rows: [cartera] } = await connection.query(
        'SELECT COUNT(*) AS total FROM clientes WHERE vendedor_id = $1',
        [req.user.id],
      );
      if (Number(cartera.total) >= 10) {
        await connection.query('ROLLBACK');
        return res.status(409).json({ mensaje: 'Su cartera ya tiene 10 clientes, el máximo permitido.' });
      }

      const { rows } = await connection.query(
        'INSERT INTO clientes (vendedor_id, nombre, nit, telefono, correo, created_at, updated_at) VALUES ($1, $2, $3, $4, $5, NOW(), NOW()) RETURNING id, nombre, nit, telefono, correo, vendedor_id',
        [req.user.id, nombre, nit, telefono, correo],
      );
      await connection.query('COMMIT');
      res.status(201).json({ dato: rows[0] });
    } catch (error) {
      await connection.query('ROLLBACK');
      throw error;
    } finally {
      connection.release();
    }
  } catch (error) {
    if (error.code === '23505') return res.status(409).json({ mensaje: 'NIT o teléfono ya registrado.' });
    next(error);
  }
});

module.exports = router;
