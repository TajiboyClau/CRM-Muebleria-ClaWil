const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);

router.get('/', async (req, res, next) => {
  try {
    const [rows] = await db.execute(
      `SELECT id, nombre, nit, telefono, correo, vendedor_id, created_at, updated_at
       FROM clientes WHERE vendedor_id = ? OR vendedor_id IS NULL ORDER BY nombre`,
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
    const [result] = await db.execute(
      'INSERT INTO clientes (vendedor_id, nombre, nit, telefono, correo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
      [req.user.id, nombre, nit, telefono, correo],
    );
    const [rows] = await db.execute('SELECT id, nombre, nit, telefono, correo, vendedor_id FROM clientes WHERE id = ?', [result.insertId]);
    res.status(201).json({ dato: rows[0] });
  } catch (error) {
    if (error.code === 'ER_DUP_ENTRY') return res.status(409).json({ mensaje: 'NIT o teléfono ya registrado.' });
    next(error);
  }
});

module.exports = router;
