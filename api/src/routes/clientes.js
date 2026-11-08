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
    const { rows: inserted } = await db.query(
      'INSERT INTO clientes (vendedor_id, nombre, nit, telefono, correo, created_at, updated_at) VALUES ($1, $2, $3, $4, $5, NOW(), NOW()) RETURNING id',
      [req.user.id, nombre, nit, telefono, correo],
    );
    const { rows } = await db.query('SELECT id, nombre, nit, telefono, correo, vendedor_id FROM clientes WHERE id = $1', [inserted[0].id]);
    res.status(201).json({ dato: rows[0] });
  } catch (error) {
    if (error.code === '23505') return res.status(409).json({ mensaje: 'NIT o teléfono ya registrado.' });
    next(error);
  }
});

module.exports = router;
